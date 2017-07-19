<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

function p_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class privilege {
	public $rows;
   private $os = null;

   // public methods

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
	} // end __construct()

   /**
    * get_all() Returns the definition data for all privileges.
    *
    * @access public
    * @return {array} An associative array with the privilege id as the index.
    */
   public function get_all(){
      $sql = "SELECT lp_privileges_id as id, lp_data as data FROM ez_login_db.login_privileges";

      return $this->query($sql);
   } // end get_all()

   /**
    * get_active() Returns the definition data for all active privileges.
    *
    * @access public
    * @return {array} An associative array with the privilege id as the index.
    */
   public function get_active(){
      $sql = "SELECT lp_privileges_id as id, lp_data as data FROM ez_login_db.login_privileges WHERE lp_active = 1";

      return $this->query($sql);
   } // end get_active()

   /**
    * get_by_id() Returns the privilege definition data for the id passed in.
    *
    * @access public
    * @param {string} $id The id of the privilege.
    * @return {stdClass} The decoded data object.
    */
   public function get_by_id($id){
      if(isset($id) && $id != ''){
         $sql = "SELECT lp_privileges_id as id, lp_data as data FROM ez_login_db.login_privileges WHERE lp_privileges_id = $1";

         $result = $this->query($sql,array($id));
         if($result){
            return $result[$id];
         }
      }

      return null;
   } // end get_by_id()

   /**
    * get_record() Returns a record object with id, data and active properties
    *
    * @param {integer} $id The privilege (record) id.
    * @return {stdClass object}
    */
   public function get_record($id){
      // do we have the required param?
      if(!isset($id) || $id == ''){
         return null;
      }

      $sql = "SELECT lp_privileges_id as id, lp_data as data,lp_active as active FROM ez_login_db.login_privileges WHERE lp_privileges_id = $1";

      $row = $this->os->billing_db->exec_db_sp($sql,array($id));
      // decode the json data
      $data = json_decode($row['data']);

      if(is_object($data)){
         $record = new stdClass();
         $record->id = $id;
         $record->data = $data;
         $record->active = $row['active'];

         return $record;
      }else{
         //$errors[] = '{ "script": "privilege.php", "method": "get_record", "message": "In the qo_privileges table, row id: '.$row['id'].' has data that could not be decoded" }';
      }

      return null;
   } // end get_record()

   /**
    * is_active() Returns true if the passed in privilege is active.
    *
    * @access public
    * @param {integer} $id The privilege id.
    * @return {boolean}
    */
   public function is_active($id){
      $sql = "SELECT lp_active as active FROM ez_login_db.login_privileges WHERE id = $1";

      $row = $this->os->billing_db->exec_db_sp($sql,array($id));

      if($row && $row['active'] == 1)
         return true;

      return false;
   } // end is_active()

   /**
    * is_allowed() Return true if the module (optionally its method) has allow set to a value of 1 for the privilege.
    *
    * @access public
    * @param {integer} $privilege_id The privilege id.
    * @param {string} $module_id The module id.
    * @param {string} $method_name (optional) The method name.
    * @return {boolean}
    */
   public function is_allowed($privilege_id, $module_id, $method_name = null){
      // have required params?
      if(!isset($privilege_id, $module_id) || $privilege_id == '' || $module_id == ''){
         return false;
      }

      // get the simplified privilege data
      foreach ($privilege_id as $p){
		$data = $this->simplify($p['id']);
		// if the optional $method_name param was passed in?
		if(isset($method_name) && $method_name != ''){
		   if(isset($data[$module_id][$method_name]) && ($data[$module_id][$method_name] == 1 || $data[$module_id][$method_name] == '1')){
		      return true;
		   }
		}else{
		   if(isset($data[$module_id]) && (is_object($data[$module_id]) || $data[$module_id] != 0 || $data[$module_id] != '0')){
		      return true;
		   }
		}
      }

      return false;
   } // end is_allowed()

   /**
    * is_allowed() Return true if the module (optionally its method) has allow set to a value of 1 for the privilege.
    *
    * @access public
    * @param {integer} $privilege_id The privilege id.
    * @param {string} $module_id The module id.
    * @param {string} $method_name (optional) The method name.
    * @return {boolean}
    *
   public function is_allowed($privilege_id, $module_id, $method_name = null){
      // have required params?
      if(!isset($privilege_id, $module_id) || $privilege_id == '' || $module_id == ''){
         return false;
      }

      // get the privilege data
      $privilege = $this->get_by_id($privilege_id);

      // does the privilege data have a modules (array) property?
      if(!isset($privilege->modules) || !is_array($privilege->modules) || count($privilege->modules) == 0){
         return false;
      }

      // find the module data
      $module = null;
      foreach($privilege->modules as $m){
         if($m->id == $module_id){
            $module = $m;
            break;
         }
      }

      // does the module data have an allow property set to a value of 1?
      if(!isset($module->allow) || $module->allow != '1' || $module->allow != 1){
         return false;
      }

      // if the optional $method_name param was not passed in?
      if(!isset($method_name) || $method_name == ''){
         return true;
      }

      // find the method by its name
      $method = null;
      foreach($module->methods as $m){
         if($m->name == $method_name){
            $method = $m;
            break;
         }
      }

      // does the method have an allow property set to a value of 1?
      if(!isset($method->allow) || $method->allow != '1' || $method->allow != 1){
         return false;
      }

      return true;
   } // end is_allowed() */

   /**
    * simplify() Returns the privilege data simplified into an associative array that contains the modules/methods
    * that have the allow property set to 1.
    *
    * @access public
    * @param {integer/object} $id The privilege id or the privilege data to simplify.
    * @return {array) An associative array.
    */
   public function simplify($param){
      // do we have the id?
      if(!isset($param) || $param == ''){
         return null;
      }

      // was the id passed in?
      if(is_numeric($param)){
         $privilege = $this->get_by_id($param);
      }

      // or was the privilege data passed id?
      else if(is_object($param)){
         $privilege = $param;
      }

	  //echo sprintf("/* privilege=%s*/",json_encode($privilege));
      if(!$privilege){
         return null;
      }

      // does the privilege data have a modules (array) property?
      if(!isset($privilege->modules) || !is_array($privilege->modules) || count($privilege->modules) == 0){
         return null;
      }

      $data = array();

      // loop through the modules
      foreach($privilege->modules as $module){
         // does the module data have an allow property set to a value of 1?
         if(isset($module->allow) && ($module->allow == 1 || $module->allow == '1')){
            // does the module data have a methods (array) property?
            if(isset($module->methods) && is_array($module->methods) && count($module->methods) > 0){
               // loop through the the methods
               foreach($module->methods as $method){
                  // does the method data have an allow property set to a value of 1?
                  if(isset($method->allow) && ($method->allow == 1 || $method->allow == '1')){
                     $data[$module->id][$method->name] = 1;
                  }
               }
            }
            // if the module did not have any allowed methods but did have allow set to 1
            if(!isset($data[$module->id])){
               $data[$module->id] = 1;
            }
         }
      }

      if(count($data) == 0){
         return null;
      }

      return $data;
   } // end simplify()

   // private methods

   /**
    * query() Run a select query against the database.
    *
    * @access private
    * @param {string} $sql The select statement.
    * @return {array} An associative array with the definition id as the index.
    */
   private function query($sql,$params=array()){
      if(isset($sql) && $sql != ''){
      	 $this->rows = array();
         $this->os->billing_db->exec_query($sql,$params,p_handle_append_row,$this);
         return $this->parse_result($this->rows);
      }

      return null;
   } // end query()

   /**
    * parse_result() Parses the query result.  Expects 'id' and 'data' fields.
    *
    * @access private
    * @param {PDOStatement} $result The result set as a PDOStatement object.
    * @return {array} An associative array with the definition id as the index.
    */
   private function parse_result($result){
      $response = array();

      if($result){
         $errors = array();

		 foreach ($result as $row){
		 	$decoded = json_decode($row['data']);
		 	if(!is_object($decoded)){
               $errors[] = "Script: privilege.php, Method: parse_result, Message: 'qo_privileges' table, 'id' ".$row['id']." has 'data' that could not be decoded";
		 		continue;
            }
            $response[$row['id']] = $decoded;
		 }

         // errors to log?
         if(count($errors) > 0){
            $this->os->load('log');
            $this->os->log->error($errors);
         }
      }

      return count($response) > 0 ? $response : null;
   } // end parse_result()
}
?>