<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

function group_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class group {
	public $rows;
	private $os;

   /** __construct()
    *
    * @access public
    * @param {class} $os The os.
    */
	public function __construct(os $os){
		$this->os = $os;
	} // end __construct()

	/**
	 * exits() Returns true if a record exists for the passed in Group name.
    *
    * @access public
	 * @param {string} $name The group name.
	 * @return {boolean}
	 */
	public function exists($name){
		$response = false;
		$sql = "select lg_group_id as id from ez_login_db.login_groups where lg_name = $1";
		
		$r = $this->os->billing_db->exec_db_sp($sql,array($name));
		if(is_array($r)){
			if($r['id'] != '')
				$response = true;
		}
		return $response;
	} // end exits()

	/**
	 * is_active()
    *
    * @access public
	 * @param {string} $group_id
	 * @return {boolean}
	 */
	public function is_active($group_id){
		$response = false;
		$sql = "select lg_active as active from ez_login_db.login_groups where lg_group_id = $1";
		
		$r = $this->os->billing_db->exec_db_sp($sql,array($group_id));
		if(is_array($r)){
			if($r['active'] == 1)
				$response = true;
		}
		return $response;
	} // end is_active()

   /**
    * get_by_id() Returns the group data associated with the id.
    *
    * @access public
    * @param {integer} $id The group id.
    * @param {boolean} $active (optional) True to only return the active groups.
    * @return {array}
    */
   public function get_by_id($id, $active = false){
      if(isset($id)){
		$sql = "select lg_group_id as id,lg_name as name,lg_description as description,lg_active as active as active from ez_login_db.login_groups where lg_group_id = $1 and lg_active = $2";
		
		$r = $this->os->billing_db->exec_db_sp($sql,array(
			$id,
			$active ? 1: 0));
		if(is_array($r)){
			return $r;
		}
      }

      return null;
   } // end get_by_id()

   /**
    * get_by_member_id() Returns the groups associated with the member id.
    *
    * @access public
    * @param {integer} $member_id The member id.
    * @param {boolean} $active (optional) True to only return the active groups.
    * @return {array}
    */
   public function get_by_member_id($member_id, $active = false){
      if(isset($member_id) && strlen($member_id)){
		$sql = "SELECT a.lg_group_id as id,b.lg_name as name,b.lg_description as description,b.lg_active as active "
			."FROM ez_login_db.login_groups_has_account a , ez_login_db.login_groups AS b"
            ."WHERE b.lg_group_id = a.lg_group_id and a.la_account_id = $1 and b.lg-active = $2 order by b.name ";
		$this->rows = array();
		$this->os->billing_db->exec_query($sql,array(
			$member_id,
			$active ? 1: 0),group_handle_append_row,$this);
		if(is_array($this->rows)){
			return $this->rows;
		}
      }

      return null;
   } // end get_by_member_id()

   /**
    * get_id() Returns group id.
    *
    * @access public
    * @param {string} $name The group name.
    */
   public function get_id($name){
      if($name != ''){
		$sql = "select lg_group_id as id from ez_login_db.login_groups where lg_name = $1";
		
		$r = $this->os->billing_db->exec_db_sp($sql,array($name));
		if(is_array($r)){
			return $r['id'];
		}
      }

      return null;
   } // end get_id()

	/**
    * get_name()
    *
    * @access public
	 * @param {integer} $group_id The group id.
	 */
	public function get_name($group_id){
		if($group_id != ''){
			$sql = "select lg_name as name from ez_login_db.login_groups where lg_group_id = $1";
			
			$r = $this->os->billing_db->exec_db_sp($sql,array($group_id));
			if(is_array($r)){
				return $r['name'];
			}
		}
		return null;
	} // end get_name()

   /**
    * get_privilege_id() Returns the privilege id for the group.
    *
    * @access public
    * @param {integer} $group_id The group id.
    */
   public function get_privilege_id($group_id){
      if(isset($group_id) && $group_id != ''){
		$sql = "select lp_privileges_id as id from ez_login_db.login_groups_has_roles_privileges where lg_group_id = $1";
		//echo $sql;
		$this->rows = array();
		$this->os->billing_db->exec_query($sql,array($group_id),group_handle_append_row,$this);
		if(is_array($this->rows)){
			return $this->rows;
		}
      }

      return null;
   } // get_privilege_id()

	/**
	 * contains_member()
    *
    * @access public
	 * @param {integer} $member_id
	 * @param {string} $name The name of the group
	 * @return boolean
	 */
	public function contains_member($member_id, $group_name){
		if($member_id != '' && $group_name != ''){
			$sql = "SELECT a.la_account_id as id "
				."FROM ez_login_db.login_groups_has_account a , ez_login_db.login_groups AS b"
	            ."WHERE (b.lg_group_id = $2 or b.lg_name = $2) and a.la_account_id = $1 ";
							
			$r = $this->os->billing_db->exec_db_sp($sql,array($member_id,$group_name));
			if(is_array($r)){
				return true;
			}
		}
		return false;
	} // end contains_member()
}
?>