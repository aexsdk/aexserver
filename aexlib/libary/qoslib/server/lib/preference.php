<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

class preference {
   private $os;

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

   /** get() Returns the preference object for the member/group.
    *
    * @access public
    * @param {integer} $member_id
    * @param {integer} $group_id
    * @return {stdClass}
    */
   public function get($member_id, $group_id){
      // do we have the required params?
      if(!isset($member_id, $group_id)){
         return null;
      }

      $sql = "SELECT lp_data as data FROM ez_login_db.login_preferences WHERE lp_members_id = $1 and lp_groups_id = $2";

      $row = $this->os->billing_db->exec_db_sp($sql,array($member_id,$group_id));
      if(is_array($row)){
         $decoded = json_decode($row['data']);
         // todo: log errors
         if(is_object($decoded)){
            return $decoded;
         }
      }

      return null;
   } // end get()

   /**
    * set() Set the preference for the member/group.  If a preference already exists it will be updated.
    *
    * @access public
    * @param {integer} $member_id The member id.
    * @param {integer} $group_id The group id.
    * @param {stdClass} $data An object that holds the preference data.
    */
   public function set($member_id, $group_id, $data){
      // do we have the required params?
      if(!isset($member_id, $group_id, $data) || $member_id == '' || $group_id == '' || !is_object($data)){
         return false;
      }

      // add or update?
      $preference = $this->get($member_id, $group_id);
      if(!$preference){
         return $this->add($member_id, $group_id, $data);
      }

      // update
      foreach($data as $id => $property){
         $preference->$id = $property;
      }

      return $this->update($member_id, $group_id, $preference);
   } // end set()

   /**
    * add()
    *
    * @access private
    * @param {integer} $member_id The member id.
    * @param {integer} $group_id The group id.
    * @param {stdClass} $data An object that holds the preference data.
    */
   private function add($member_id, $group_id, $data){
      // do we have the required params?
      if(!isset($member_id, $group_id, $data) || $member_id == '' || $group_id == '' || !is_object($data)){
         return false;
      }

      $data_string = json_encode($data);
      if(!is_string($data_string)){
         return false;
      }

      $sql = "INSERT INTO ez_login_db.login_preferences (lp_members_id,lp_groups_id, lp_data ) VALUES ($1, $2, $3)";

      $this->os->billing_db->exec_proc($sql,array($member_id,$group_id,$data_string));
      return true;
   } // end add()

   /**
    * update()
    *
    * @access private
    * @param {integer} $member_id The member id.
    * @param {integer} $group_id The group id.
    * @param {stdClass} $data An object that holds the preference data.
    */
   private function update($member_id, $group_id, $data){
      // do we have the required params?
      if(!isset($member_id, $group_id, $data) || $member_id == '' || $group_id == '' || !is_object($data)){
         return false;
      }

      $data_string = json_encode($data);
      if(!is_string($data_string)){
         return false;
      }

      $sql = "UPDATE ez_login_db.login_preferences SET lp_data = $3 WHERE lp_groups_id = $2 AND lp_members_id = $1";

      $this->os->billing_db->exec_proc($sql,array($member_id,$group_id,$data_string));
      return true;
   } // end update()
}
?>
