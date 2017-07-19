<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */



function get_message_callback($api_obj, $context, $msg) {
	return sprintf ( $msg, $api_obj->return_code );
}

/*
	写回应信息的回调函数，设置了此函数会覆盖默认的输出方式，此函数只需要返回结果，输出由调用函数负责。
*/
function write_response_callback($api_obj, $context) {
	//$api_obj->write_trace(0,'Run here');
	$success = $api_obj->return_code > 0;
	$api_obj->push_return_data ( 'success', $success );
	$api_obj->push_return_data ( 'message', $api_obj->get_error_message ( $api_obj->return_code ), '' );
	
	$resp = $api_obj->write_return_params_with_json ();
	return $resp;
}


function qa_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class QoAdmin {
	private $os;
	public $rows;
	public $api_obj;
   
   /**
    * __construct()
    *
    * @access public
    * @param {class} $os The os.
    */
   public function __construct(os $os){
      if(!$os->session_exists()){
         die('Session does not exist!');
      }

		$this->os = $os;
		$this->api_obj = $this->os->log_object;
		//加载多国语言
		$os->log_object->load_error_xml(sprintf("%s.xml",'QoAdmin.xml'));
   } // end __construct()
	
	// begin public module actions

   // members

   /**
    * viewAllMembers()
    *
    * @access public
    */
	public function viewAllMembers(){
		$response = '{qo_members: []}';

		$sql = 'SELECT la_account_id as id, la_account as first_name,la_active_name as last_name,la_active_email as email_address, la_group as group, la_resaler as resaler, la_active as active, la_locale as locale FROM ez_login_db.login_account order by last_name ASC';
		$this->rows = array();
        $this->os->billing_db->exec_query($sql,array(),qa_handle_append_row,$this);
		if($this->rows){
			$items = array();
			foreach ($this->rows as $row){
				$row['active'] = $row['active'] == 1 ? true : false;
				$items[] = $row;
			}

			$response = '{"qo_members":'.json_encode($items).'}';
		}

		print $response;
	} // end viewAllMembers()

   /**
    * viewMember
    *
    * @access public
    */
   public function viewMember(){
      $response = '{success:false}';
      $member_id = $_POST['memberId'];

      if(isset($member_id) && $member_id != '' && is_numeric($member_id)){
         $sql = 'SELECT la_account_id as id, la_account as first_name,la_active_name as last_name,la_active_email as email_address, la_group as group, la_resaler as resaler, la_active as active, la_locale as locale FROM ez_login_db.login_account  WHERE la_account_id = $1';
		 $row = $this->os->billing_db->exec_sp_db($sql,array($member_id));
         if($row){
            $row['active'] = $row['active'] == 1 ? true : false;
            $response = '{"success": true, "data":'.json_encode($row).'}';
         }
      }

      print $response;
   } // end viewMember()

   /**
    * addMember()
    *
    * @access public
    */
   public function addMemberOld(){
      $response = "{success:false}";

      // make all the strings safe
      $first_name = $_REQUEST['first_name'];
      $last_name = $_REQUEST['last_name'];
      $email_address = $_REQUEST['email_address'];
      $password = $_REQUEST['password'];
      $locale = $_REQUEST['locale'];
      $active = $_REQUEST['active'] == 'true' ? 1 : 0;

      $a = $this->add_member($first_name, $last_name, $email_address, $password, $locale, $active);

      if($a["success"] == "true" && $a["id"] != ""){
         $response = "{success:true, id: ".$a["id"]."}";
      }

      print $response;
   } // end addMember()

   /**
    * addMember()
    *
    * @access public
    */
	public function addMember(){
   		$response = '{success:false}';
   		// Example: {"mawc_welcome":{},"mawc_account":{"first_name":"asd","last_name":"asd","email":"asd@eztor.com"},"mawc_password":{"password":"1","confirm_password":"1"},"mawc_group":{"id":"1"},"mawc_resaler":{"agent_id":""},"mawc_finish":{}}
      	$data = $_REQUEST['data'];
      	if(isset($data) && $data != ''){
         	// decode the data array
         	$data = get_object_vars(json_decode($data));
         	$mawc_account = get_object_vars($data['mawc_account']);
         	$mawc_password = get_object_vars($data['mawc_password']);
         	$mawc_group = get_object_vars($data['mawc_group']);
         	$mawc_resaler = get_object_vars($data['mawc_resaler']);
 	
			if(is_array($mawc_account) && count($mawc_account) > 0  && 
         		is_array($mawc_password) && count($mawc_password) > 0  &&
         		is_array($mawc_group) && count($mawc_group) > 0 ){

	            $result = new stdClass();
	            $result->saved = array();
	            $result->failed = array();
            // loop thru each data object
            	$array = array(
            		'first_name' => $mawc_account['first_name'],
					'last_name' => $mawc_account['last_name'], 
					'email_address' => $mawc_account['email'], 
					'password' => $mawc_password['password'],
					'group' => $mawc_group['id'],
					'resaler' =>$mawc_resaler['agent_id'],
					'locale' => $this->os->sessions['lang']
            		,'active' => true
            	);
            	
               	$this->add_member($array);
               	return;
			}	
		}
		print $response;
	} // end addMember()

   /**
    * add_member() Returns the new member id on success.
    *
    * @access private
    * @param {string} $first_name
    * @param {string} $last_name
    * @param {string} $email_address
    * @param {string} $password
    * @param {string} $locale
    * @param {integer} $active
    * @return {array}
    */
	private function add_member($array){
		$first_name = $array['first_name'];
		$last_name = $array['last_name'];
		$email_address = $array['email_address'];
		$password = $array['password'];
		$group = $array['group'];
		$resaler = $array['resaler'];
		$locale = $array['locale'];
		$active = $array['active'];
		
		// encrypt the password
        $this->os->load('security');
        $password = $this->os->security->encrypt($password);

      if(isset($first_name, $last_name, $email_address, $locale) && ($active === true || $active === false)){
         // encrypt the password
         if(isset($this->os->log_object->config->encrypt_pass) && $this->os->log_object->config->encrypt_pass)
         {
         	$this->os->load('security');
         	$password = $this->os->security->encrypt($password);
         }

         $sql = "select * from ez_login_db.sp_n_add_member($1)";
         $param = array(
         	'first_name' => $first_name, 
         	'last_name' => $last_name, 
         	'email_address' => $email_address, 
         	'password' => $password, 
         	'group' => $group,
         	'resaler' => intval($resaler),
         	'locale' => $locale,
         );
         // prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
         $row = $this->os->billing_db->exec_proc($sql, array(array_to_string("\r\n",$param)));
         $code = $row['p_return'];
         if($code > 0){
         	$newid = $row['p_return'];
            //$response = array('success' => 'true', 'id' => $newid);
            $this->os->log_object->push_return_data('success','true');
            $this->os->log_object->push_return_data('id',$newid);
            $this->os->log_object->write_response();
         }else{
            $this->errors[] = 'Script: QoAdmin.php, Method: add_member, Message: PDO error code - '.$code;
            $this->os->load('log');
            $this->os->log->error($this->errors);
            $this->os->log_object->push_return_data('success','false');
            $this->os->log_object->push_return_data('msg',$this->os->lang_tr('Add_member_error'));
            $this->os->log_object->write_response();
         }
      }else{
			$this->os->log_object->push_return_data('success','false');
			$this->os->log_object->push_return_data('msg',$this->os->lang_tr('Add_member_error'));
			$this->os->log_object->write_response();
      }
      //return $response;
	} // end add_member()

   /**
    * editMember()
    *
    * @access public
    */
   public function editMember(){
      $response = '{success:false}';

      // Example: [{"last_name":"User","id":"2"}]
      $data = $_POST['data'];

      if(isset($data) && $data != ''){
      	 $this->os->load('security');
         // decode the data array
         $data = json_decode($data);
         if(is_array($data) && count($data) > 0){

            // track results
            $results = new stdClass();
            $results->saved = array();
            $results->failed = array();

            // loop thru each data object
            for($i = 0, $len = count($data); $i < $len; $i++){
               // loop thru the objects key/values to build sql
               $params = array();
               foreach($data[$i] AS $key => $value){
                  switch($key){
                  	case 'password':
                  		$value = $this->os->security->encrypt($value);
                  		break;
                  }
                  $params[$key] = $value;
               }
               $sql = "select * from ez_login_db.sp_n_modify_account($1,$2)";

               // build sql
               //$sql = 'UPDATE ez_login_db.login_account SET '.$temp.' WHERE la_account_id = '.$data[$i]->id;
               // prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
               $this->os->billing_db->exec_proc($sql,array($data[$i]->id,array_to_string(",",params)));
               $results->saved[] = $data[$i]->id;
            }

            $results->success = count($results->failed) > 0 ? false : true;
            $response = json_encode($results);
         }
      }

      print $response;
   } // end editMember()

   /**
    * addMemberToGroup()
    *
    * @access public
    */
   public function addMemberToGroup(){
      $response = "{'success': false}";

      $group_id = $_POST["groupId"];
      $member_id = $_POST["memberId"];

      if($group_id != "" && $member_id != ""){
         $active = "true";
         $admin = "false";
         if($this->add_member_to_group($member_id, $group_id, $active, $admin)){
            $response = "{'success': true}";
         }
      }

      print $response;
   } // end addMemberToGroup()

   /**
    * deleteMemberFromGroup
    *
    * @access public
    */
   public function deleteMemberFromGroup(){
      $response = "{'success': false}";

      $group_id = $_POST["groupId"];
      $member_id = $_POST["memberId"];

      if($group_id != "" && $member_id != ""){
         $sql = "DELETE FROM ez_login_db.login_account WHERE la_account_id = $1 AND la_group = $2";
         $this->os->billing_db->exec_proc($sql,array($member_id,$group_id));
         $response = "{'success': true}";
      }

      print $response;
   } // deleteMemberFromGroup()

   /**
    * deleteMembers()
    *
    * @access public
    */
	public function deleteMembers(){
		$member_ids = $_POST['memberIds'];
		$member_ids = json_decode(stripslashes($member_ids));

		$r = array();
		$k = array();

      if(is_array($member_ids) && count($member_ids) > 0){
         foreach($member_ids as $id){
            $success = false;

            // delete the member from any preferences
            if($this->delete_preference('member', $id)){
               // delete the member from any groups
               if($this->delete_group_member_relationship('member', $id)){
                  // delete the member from any sessions
                  if($this->delete_members_session($id)){
                     // delete the member
                     if($this->delete_member($id)){
                        $success = true;
                     }
                  }
               }
            }

            if($success){
               $r[] = $id;
            }else{
               $k[] = $id;
            }
         }
      }

      // return ids of removed and kept
      print '{r: '.json_encode($r).', k: '.json_encode($k).'}';
   } // end deleteMembers()

   public function get_groups($member_id=''){
      $this->rows = array();
      if($member_id != ''){
         $sql = "select lg_group_id as id,lg_name as name,lg_description as description,lg_importance as importance,lg_active as active from ez_login_db.login_groups"
				."where lg_group_id in (SELECT lg_group_id FROM ez_login_db.login_groups_has_account where la_account_id = $1) order by lg_group_id";
      	 $this->os->billing_db->exec_query($sql,array($member_id),qa_handle_append_row,$this);
      }else{ 
         $sql = "select lg_group_id as id,lg_name as name,lg_description as description,lg_importance as importance,lg_active as active from ez_login_db.login_groups  order by lg_group_id";
      	 $this->os->billing_db->exec_query($sql,array(),qa_handle_append_row,$this);
      }
      //echo sprintf("Sql=%s\r\nGroups:%s\r\n",$sql,array_to_string(",",$this->rows));
      return $this->rows;
   }
	/**
    * loadGroupsCombo()
    * If memberId is passed in, returns only the groups the member is not currently assigned to
    *
    * @access public
    */
   public function loadGroupsCombo(){
      $response = "{'success': false, 'total': 0, 'results': []}";

      $member_id = $_POST['memberId'];
      $groups = $this->get_groups($member_id);
      if(count($groups) > 0){
         $response = sprintf('{"success": true, "total": %d, "groups":%s}',count($groups),json_encode($groups));
      }

      print $response;
   } // end loadGroupsCombo()
   public function get_privileges($group_id=''){
	$this->rows = array();
	if($group_id == ''){
	  	$sql = "SELECT lp_privileges_id as id, lp_data as data,lp_active as active  FROM ez_login_db.login_privileges order by lp_privileges_id";
		//echo sprintf("Sql=%s\r\privileges:%s\r\n",$sql,array_to_string(",",$this->rows));
	    $this->os->billing_db->exec_query($sql,array(),qa_handle_append_row,$this);
	}else{
		$sql = "SELECT lp_privileges_id as id, lp_data as data,lp_active as active  FROM ez_login_db.login_privileges"
			." where lp_privileges_id in (select lp_privileges_id from ez_login_db.login_groups_has_roles_privileges where lg_group_id = $1) order by lp_privileges_id";
		//echo sprintf("Sql=%s\r\privileges:%s\r\n",$sql,array_to_string(",",$this->rows));
		$this->os->billing_db->exec_query($sql,array($group_id),qa_handle_append_row,$this);
	}
	$privileges = array();
	foreach ($this->rows as $row){
		$data = json_decode($row['data']);
		if(is_object($data)){
			$privileges[] = array(
				'id' => $row['id'],
				"active" => $row['active'] == 1 ? true : false,
				'name' => $data->name,
				'description' => $data->description,
				'data' => $data
				);
		}
	}
	
	return $privileges;
   }

   /**
    * loadPrivilegesCombo()
    * If groupId is passed in, returns only the privileges the group is not currently assigned to
    *
    * @access public
    */
   public function loadPrivilegesCombo(){
      $response = "{'success': false, 'total': 0, 'results': []}";

      $group_id = $_POST['groupId'];
      $privileges = $this->get_privileges($group_id);
      if(count($privileges) > 0){
         $response = sprintf('{"success": true, "total": %d, "privileges":%s}',count($privileges),json_encode($privileges));
      }

      print $response;
   } // end loadPrivilegesCombo()

   /**
    * viewAllPrivileges()
    *
    * @access public
    */
	public function viewAllPrivileges(){

		$privileges = $this->get_privileges();
		$response = '{"qo_privileges":'.json_encode($privileges).'}';
		print $response;
	} // end viewAllPrivileges()

   /**
    * viewAllGroups() Returns all the groups.
    *
    * @access public
    */
   public function viewAllGroups(){
      $response = "{'qo_groups': []}";

      $groups = $this->get_groups();
      $items = array();
      foreach ($groups as $row){
      	$row['active'] = $row['active'] == 1 ? true : false;
        $items[] = $row;
      }
      $response = '{"qo_groups":'.json_encode($items).'}';

      print $response;
   } // end viewAllGroups()

   /**
    * viewGroup
    *
    * @access public
    */
   public function viewGroup(){
      $response = '{success:false}';
      $group_id = $_POST['groupId'];

      if(isset($group_id) && $group_id != ''){
         $sql = "select lg_group_id as id,lg_name as name,lg_description as description,lg_active as active from ez_login_db.login_groups where lg_group_id = $1";
         $row = $this->os->billing_db->exec_db_sp($sql,array($group_id));
         if($row){
            $row['active'] = $row['active'] == 1 ? true : false;
            $response = '{"success": true, "data":'.json_encode($row).'}';
         }
      }

      print $response;
   } // end viewGroup()

   /**
    * addGroup()
    *
    * @access public
    */
   public function addGroup(){
      $response = '{success:false}';

      $name = $_POST['name'];
      $description = $_POST['description'];
      $active = $_POST['active'];

      $a = $this->add_group($name, $description, $active);

      if($a['success'] == 'true' && $a['id'] != ''){
         $response = '{success:true, id:'.$a['id'].'}';
      }

      print $response;
   } // end addGroup()

   /**
    * editGroup()
    *
    * @access public
    */
   public function editGroup(){
      $response = '{success:false}';

      $group_id = $_POST['groupId'];
      $field = $_POST['field'];
      $value = isset($_POST['value']) ? $_POST['value'] : '';

      if(isset($field, $group_id) && $field != '' && $group_id != ''){
         if($field == 'all'){
            $name = $_POST['name'];
            $description = $_POST['description'];
            $active = $_POST['active'];

            // convert active field to a 1 or 0
            if($active == 'true'){
               $active = 1;
            }else{
               $active = 0;
            }

            $sql = 'UPDATE ez_login_db.login_groups SET lg_name = $1, lg_description = $2, lg_active = $3 WHERE lg_group_id = $4';

            // prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
            $this->os->billing_db->exec_proc($sql,array($name,$description,$active,$group_id));
            $response = '{success:true}';
         }else if(isset($value) && $value != ''){
            // convert active field to a 1 or 0
            if($field == 'active'){
               if($value == 'true'){
                  $value = 1;
               }else{
                  $value = 0;
               }
            }

            $sql = "UPDATE ez_login_db.login_groups SET lg_".$field." = ? WHERE lg_group_id = ".$group_id;

            // prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
            $this->os->billing_db->exec_proc($sql,array());
            $response = '{success: true}';
         }
      }

      print $response;
   } // end editGroup()

   /**
    * deleteGroups()
    *
    * @access public
    */
	public function deleteGroups(){
		$group_ids = $_POST['groupIds'];
		$group_ids = json_decode(stripslashes($group_ids));

		$r = array();
		$k = array();

      if(is_array($group_ids) && count($group_ids) > 0){
         foreach($group_ids as $id){
            $success = false;

            // delete the group from any preferences
            if($this->delete_preference('group', $id)){
               // delete the group from any members
               if($this->delete_group_member_relationship('group', $id)){
                  // delete the group from any privileges
                  if($this->delete_group_privilege_relationship('group', $id)){
                     // delete the group
                     if($this->delete_group($id)){
                        $success = true;
                     }
                  }
               }
            }

            if($success){
               $r[] = $id;
            }else{
               $k[] = $id;
            }
         }
      }

      // return ids of removed and kept
      print '{r: '.json_encode($r).', k: '.json_encode($k).'}';
   } // end deleteGroups()

   /**
    * changeGroupPrivilege()
    *
    * @access public
    */
   public function changeGroupPrivilege(){
      $response = "{'success': false}";

      $group_id = $_POST['groupId'];
      $privilege_id = $_POST['privilegeId'];

      if(isset($group_id, $privilege_id) && $group_id != '' && $privilege_id != ''){
         // delete existing
         $sql = "select * from ez_login_db.sp_n_change_group_privilege($1,$2) ";
         $row = $this->os->billing_db->exec_db_sp($sql,array($group_id,$privilege_id));
         if(is_array($row) && $row['p_return']>0){
         	$response = "{'success': true}";
         }
      }

      print $response;
   } // end changeGroupPrivilege()

   /**
    * viewModuleMethods()
    */
   public function viewModuleMethods(){
      $response = '{success:false}';

      // get all the module data
      $this->os->load('module');
      $modules = $this->os->module->get_all();

      if(!isset($modules) || !is_array($modules) || count($modules) == 0){
         print $response;
         return false;
      }

      $nodes = array();

      // loop through each module
      foreach($modules as $module_id => $module){
         $module_node = new stdClass();

         $module_node->checked = false;
         $module_node->moduleId = $module->id;
         $module_node->iconCls = 'qo-admin-app';
         $module_node->id = $module->id;
         $module_node->text = $module->about->name;

         $children = array();

         // does the module have server methods?
         if(isset($module->server->methods) && is_array($module->server->methods) && count($module->server->methods) > 0){
            foreach($module->server->methods as $method){
               if(!isset($method->name)){
                  continue;
               }

               $method_node = new stdClass();

               $method_node->checked = false;
               $method_node->iconCls = 'qo-admin-method';
               $method_node->id = $method->name;
               $method_node->leaf = true;
               $method_node->text = $method->name;

               $children[] = $method_node;
            }
         }

         if(count($children) > 0){
            $module_node->children = $children;
         }else{
            $module_node->leaf = true;
         }

         $nodes[] = $module_node;
      }

      if(count($nodes) == 0){
         print $response;
         return false;
      }

      print json_encode($nodes);
   } // end viewModuleMethods()

   /**
    * viewGroupPrivileges() Returns data to load an Ext.tree.TreePanel
    *
    * @access public
    */
	public function viewGroupPrivileges(){
      $response = '{success:false}';
      $group_id = $_POST['groupId'];

      // do we have the group id?
      if(!isset($group_id) || $group_id == ''){
         print $response;
         return false;
      }

      // get the privilege id associated with the group
      $this->os->load('group');
      $privilege_id = $this->os->group->get_privilege_id($group_id);
      //var_dump($privilege_id);

      if(!$privilege_id){
         print $response;
         return false;
      }

      // get the privilege nodes
      $nodes = $this->build_privilege_nodes($privilege_id);
      //var_dump($nodes);

      if(count($nodes) == 0){
         print $response;
         return false;
      }

      print json_encode($nodes);
      return true;
   } // end viewGroupPrivileges()

   /**
    * viewMemberGroups() Returns data to load an Ext.tree.TreePanel
    *
    * @access public
    */
   public function viewMemberGroups(){
      $response = '{success:false}';
      $member_id = $_POST['memberId'];

      // do we have the member id?
      if(!isset($member_id) || $member_id == ''){
         print $response;
         return false;
      }

      $this->os->load('group');

      // get all of the groups the member is assigned to
      $groups = $this->os->group->get_by_member_id($member_id);
      if(!isset($groups) || !is_array($groups) || count($groups) == 0){
         print $response;
         return false;
      }

      $data = $this->build_group_nodes($groups);

      if(count($data) == 0){
         print $response;
         return false;
      }

      print json_encode($data);
      return true;
   } // end viewMemberGroups()

   /**
    * viewPrivilegeModules() Returns data to load an Ext.tree.TreePanel
    *
    * @access public
    */
   public function viewPrivilegeModules(){
      $response = '{success:false}';
      $privilege_id = $_POST['privilegeId'];

      // do we have the privilege id?
      if(!isset($privilege_id) || $privilege_id == ''){
         print $response;
         return false;
      }

      // get the privilege nodes
      $nodes = $this->build_privilege_nodes($privilege_id);
      //var_dump($nodes);
      // we only need the privilege child nodes
      if(count($nodes) == 0 || !isset($nodes[0]->children) || !is_array($nodes[0]->children)){
         print $response;
         return false;
      }

      print json_encode($nodes[0]->children);
      return true;
   } // end viewPrivilegeModules()

   /**
    * build_group_nodes() Return group data to build an Ext.tree.Node
    *
    * @acess private
    * @param {array} $groups
    * @return {array}
    */
   private function build_group_nodes($groups){
      $nodes = array();

      // do we have the required param?
		if(!isset($groups) || !is_array($groups) || count($groups) == 0){
         return $nodes;
      }

      // loop through each group
      foreach($groups as $group){
         // build the node data
         $data = new stdClass();

         $data->active = $group['active'];
         $data->groupId = $group['id'];
         $data->iconCls = 'qo-admin-group';
         $data->text = $group['name'];
         $data->uiProvider = 'col';

         // node children data?
         $children = null;

         // get the group privilege id
         $privilege_id = $this->os->group->get_privilege_id($group['id']);
         if($privilege_id){
            $children = $this->build_privilege_nodes($privilege_id);
         }

         if($children){
            $data->children = $children;
         }else{
            $data->leaf = true;
         }

         $nodes[] = $data;
      }

      return $nodes;
	} // end build_group_nodes()

   /**
    * build_privilege_nodes() Returns an array of data (config for an Ext.tree.Node)
    *
    * @acess private
    * @param {integer/array} $param A privilege id or an array of privilege ids.
    * @return {array}
    */
   private function build_privilege_nodes($param){
      $nodes = array();

      $ids = null;

      // if the param is an integer (id) then create an array
      if(is_array($param) && count($param) > 0){
         $ids = $param;
      }else{
      	$ids = array('id'=>$param);
      }

      if(!$ids){
         return $nodes;
      }

      $this->os->load('privilege');

      // loop through each privilege id
      foreach($ids as $id){
         // get the privilege record
         if(is_array($id))
         	$id = $id['id'];		//传进来的是数据表时，每一行索然只有一个字段，但是也是数组
         $privilege = $this->os->privilege->get_record($id);
         //var_dump($privilege);

         // if a record was not returned then continue
         if(!isset($privilege)){
            continue;
         }

         // build the privilege node data
         $privilege_node = new stdClass();

         $privilege_node->active = $privilege->active;
         $privilege_node->iconCls = 'qo-admin-privilege';
         $privilege_node->privilegeId = $privilege->id;
         $privilege_node->text = $privilege->data->name;
         $privilege_node->uiProvider = 'col';

         $privilege_children = array();

         // does the privilege have any children (modules)
         if(isset($privilege->data->modules) && is_array($privilege->data->modules) && count($privilege->data->modules) > 0){
            foreach($privilege->data->modules as $module){
               if(!isset($module->id)){
                  continue;
               }

               // get the module record data
               $module_record = $this->os->module->get_record($module->id);
               //var_dump($module_record);
               if(!$module_record){
                  continue;
               }

               $module_node = new stdClass();

               $module_node->active = $module_record->active;
               $module_node->iconCls = 'qo-admin-app';
               $module_node->moduleId = $module->id;
               $module_node->text = $module_record->data->about->name;
               $module_node->uiProvider = 'col';

               $module_children = array();

               // does the module have any children (methods)
               if(isset($module->methods) && is_array($module->methods) && count($module->methods) > 0){
                  foreach($module->methods as $method){
                     if(!isset($method->name)){
                        continue;
                     }

                     $method_node = new stdClass();

                     //$method_node->active = $module_record->active;
                     $method_node->iconCls = 'qo-admin-method';
                     $method_node->methodId = $method->name;
                     $method_node->text = $method->name;
                     $method_node->uiProvider = 'col';
                     $method_node->leaf = true;

                     $module_children[] = $method_node;
                  }
               }

               if(count($module_children)){
                  $module_node->children = $module_children;
               }else{
                  $module_node->leaf = true;
               }

               $privilege_children[] = $module_node;
            }
         }

         if(count($privilege_children)){
            $privilege_node->children = $privilege_children;
         }else{
            $privilege_node->leaf = true;
         }

         $nodes[] = $privilege_node;
      }

      return $nodes;
	} // end build_privilege_nodes()

   /**
    * build_module_nodes() Return module data to build an Ext.tree.Node
    *
    * @acess private
    * @param {array} $modules The modules property (array) from a privilege definition data
    * @return {array}
    */
   private function build_module_nodes($modules){
      if(!is_array($modules)){
         return null;
      }

      $this->os->load('group');
      $this->os->load('privilege');

		// get the group privilege id
      $privilege_id = $this->os->group->get_privilege_id($modules);

      // get the simplified privilege data
      $privilege = $this->os->privilege->simplify($privilege_id);

      $nodes = array();

      if(isset($privilege)){
         $this->os->load('module');

         // loop through the allowed modules
         foreach($privilege as $module_id => $methods){
            // get the module data
            $module = $this->os->module->get_by_id($module_id);

            if(isset($module)){
               $data = new stdClass();

               $data->active = $this->os->module->is_active($module_id) ? 1 : 0;
               $data->iconCls = 'qo-admin-app';
               $data->moduleId = $module->id;
               $data->text = $module->about->name;
               $data->uiProvider = 'col';

               $children = $this->build_method_nodes($methods);
               if($children){
                  $data->children = $children;
               }else{
                  $data->leaf = true;
               }

               $nodes[] = $data;
            }
         }
      }

      if(count($nodes) == 0){
         return null;
      }

      return $nodes;
	} // end build_module_nodes()

   /**
    * build_method_nodes() Return method data to build an Ext.tree.Node
    *
    * @acess private
    * @param <type> $methods
    * @return {array}
    */
   private function build_method_nodes($methods){
		if(!isset($methods) || !is_array($methods) || count($methods) == 0){
         return null;
      }

      $nodes = array();

      foreach($methods as $method => $allow){
         // methods
         $data = new stdClass();

         $data->iconCls = 'qo-admin-method';
         $data->methodId = $method;
         $data->leaf = true;
         $data->text = $method;
         $data->uiProvider = 'col';

         $nodes[] = $data;
      }

      if(count($nodes) == 0){
         return null;
      }

      return $nodes;
	} // end build_method_nodes()
	
	// end public module actions

   /**
    * add_group() Returns the new member id on success.
    *
    * @access private
    * @param {string} $name
    * @param {string} $description
    * @param {integer} $active
    * @return {array}
    */
	private function add_group($name, $description, $active){
		$response = array('success' => 'false');
		$active = ($active == true or $active == 'true' or $active == '1') ? 1:0;
	    $sql = "select * from ez_login_db.sp_n_add_group($1, $2,$3)";
		$row = $this->os->billing_db->exec_db_sp($sql,array($name,$description,$active));
		if(is_array($row) && $row['p_return']>0){
			$id = $row['p_id'];
			$response = array('success' => 'true', 'id' => $id);
		}
	    return $response;
	} // end add_group()

   /**
    * add_member_to_group()
    *
    * @access private
    * @param {integer} $member_id
    * @param {integer} $group_id
    * @param {integer} $active
    * @param {integer} $admin
    * @return {array}
    */
   private function add_member_to_group($member_id, $group_id, $active, $admin){
      $response = false;

      if($group_id != '' && $member_id != '' && $active != '' && $admin != ''){
         $sql = "INSERT INTO ez_login_db.login_groups_has_account (la_account_id, lg_group_id, lag_active, lag_admin) VALUES ($1, $2,$3, $4)";
         $this->os->billing_db->exec_proc($sql,array($member_id,$group_id,$active,$admin));
         $response = true;
      }

      return $response;
   } // end add_member_to_group()

   /**
    * delete_group()
    *
    * @access private
    * @param {integer} $id
    * @return {boolean}
    */
   private function delete_group($id){
      // do we have the required param?
      if(!isset($id) || $id == ''){
         return false;
      }
      $sql = "delete from ez_login_db.login_groups where lg_group_id = $1";
      $this->os->billing_db->exec_proc($sql,array($id));
      return true;
   } // end delete_group()

   /**
    * delete_member()
    *
    * @access private
    * @param {integer} $id
    * @return {boolean}
    */
   private function delete_member($id){
      // do we have the required param?
      if(!isset($id) || $id == ''){
         return false;
      }

      $sql = "delete from ez_login_db.login_account where la_account_id = $1";
      $this->os->billing_db->exec_proc($sql,array($id));
      return true;
   } // end delete_member()

   /**
    * delete_group_member_relationship()
    *
    * @access private
    * @param {string} $option
    * @param {integer} $id
    * @return {boolean}
    */
   private function delete_group_member_relationship($option, $id){
      $response = false;

      // do we have the required params?
      if(!isset($option, $id) || $id == ''){
         return false;
      }

      $sql = null;

      // delete where group id = $id?
      if($option == 'group'){
         $sql = "DELETE FROM ez_login_db.login_groups_has_account WHERE lg_group_id = $1";
      }

      // delete where group id = $id?
      else if ($option == 'member'){
         $sql = "DELETE FROM ez_login_db.login_groups_has_account WHERE la_account_id = $1";
      }

      if(!$sql){
         return false;
      }

      $this->os->billing_db->exec_proc($sql,array($id));
      return true;
   } // end delete_group_member_relationship()

   /**
    * delete_group_privilege_relationship()
    *
    * @access private
    * @param {string} $option
    * @param {integer} $id
    * @return {boolean}
    */
   private function delete_group_privilege_relationship($option, $id){
      // do we have the required params?
      if(!isset($option, $id) || $option == '' || $id == ''){
         return false;
      }

      // do we have the required params?
      if(!isset($option, $id) || $id == ''){
         return false;
      }

      $sql = null;

      // delete where group id = $id?
      if($option == 'group'){
         $sql = "DELETE FROM ez_login_db.login_groups_has_roles_privileges WHERE lg_group_id = $1";
      }

      // delete where privilege id = $id?
      else if ($option == 'privilege'){
         $sql = "DELETE FROM ez_login_db.login_groups_has_roles_privileges WHERE lp_privileges_id = $1";
      }

      if(!$sql){
         return false;
      }
      $this->os->billing_db->exec_proc($sql,array($id));
      return true;
    } // end delete_group_privilege_relationship()

	private function delete_members_launchers($id){
		$response = false;
		
		/*if($id != ""){			
			$st = $this->os->db->conn->prepare("DELETE FROM qo_members_has_module_launchers WHERE qo_members_id = ".$id);
			$st->execute();
			
			$code = $st->errorCode();
			if($code == '00000'){
				$response = true;
			}else{
				$this->errors[] = "Script: QoAdmin.php, Method: delete_members_launchers, Message: PDO error code - ".$code;
				$this->os->load('log');
				$this->os->log->error($this->errors);
			}
		}*/
		return $response;
	}
	
	
	
	private function delete_members_session($id){
		$response = false;
		
		/*if($id != ""){			
			$st = $this->os->db->conn->prepare("DELETE FROM qo_sessions WHERE qo_members_id = ".$id);
			$st->execute();
			
			$code = $st->errorCode();
			if($code == '00000'){
				$response = true;
			}else{
				$this->errors[] = "Script: QoAdmin.php, Method: delete_members_session, Message: PDO error code - ".$code;
				$this->os->load('log');
				$this->os->log->error($this->errors);
			}
		}*/
		return $response;
	}

   /**
    * delete_preference()
    *
    * @access private
    * @param {string} $option 
    * @param {integer} $id
    * @return {boolean}
    */
   private function delete_preference($option, $id){
      // do we have the required params?
      if(!isset($id) || $id == ''){
         return false;
      }
      if($option == 'group'){
      	$sql = 'delete from ez_login_db.login_preferences where lp_groups_id = $1';
      }else if($option == 'member'){
      	$sql = 'delete from ez_login_db.login_preferences where lp_members_id = $1';
      }
      $this->os->billing_db->exec_proc($sql,array($id));
      return true;
   } // end delete_preference()

   //获取代理商名称，通过carrier id获取该运营商下的代理商信息
	public function get_agent_name() {
		$domain = $_REQUEST['domain'];				//
		$carrier_id= $_REQUEST['filtter'];			//
		try {
			$this->os->log_object->set_callback_func ( get_message_callback, write_response_callback, $this );
			
			$resaler = $this->os->sessions['resaler'];
			$rdata = $this->os->billing_ms_db->billing_get_agent_name ( $resaler );
			if (is_array ( $rdata )) {
				$list_array = array ();
				//遍历数组
				for($i = 0; $i < count ( $rdata ); $i ++) {
					$r_data = array ('agent_id' => $rdata [$i] ['AgentID'], 'agent_name' => $rdata [$i] ['Agent_Name'] );
					array_push ( $list_array, $r_data );
				}
				$this->os->log_object->return_data ['totalCount'] = $this->os->billing_db->total_count;
				$this->os->log_object->return_data ['data'] = $list_array;
			} else {
				//echo '$rdata is not a array';
				$this->os->log_object->set_return_code ( - 101 );
			}
			$this->os->log_object->write_response ();
		} catch ( Exception $e ) {
			$rows = $e->getMessage ();
		}
		
	}
}
?>