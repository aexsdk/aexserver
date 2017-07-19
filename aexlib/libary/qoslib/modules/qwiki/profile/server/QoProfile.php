<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

class QoProfile {

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

	// begin public module methods

   /**
    * loadProfile()
    */
   public function loadProfile(){
      $response = '{"success":false}';

      $member_id = $this->os->get_member_id();
      if(isset($member_id) && $member_id != '' && is_numeric($member_id)){
         $sql = 'SELECT la_account AS field1, la_active_name AS field2, la_active_email AS field3 FROM ez_login_db.login_account WHERE la_account_id = $1';

         $row = $this->os->billing_db->exec_db_sp($sql,array($member_id));
         if($row){
            $response = '{"success":true,"data":'.json_encode($row).'}';
         }
      }

      print $response;
   } // end loadProfile()

   /**
    * saveProfile()
    */
   public function saveProfile(){
      $response = '{success:false}';

      $member_id = $this->os->get_member_id();
      if(isset($member_id) && $member_id != '' && is_numeric($member_id)){
         // get post data
         $field1 = (!empty($_POST['field1']) ? $_POST['field1'] : NULL);
         $field2 = (!empty($_POST['field2']) ? $_POST['field2'] : NULL);
         $field3 = (!empty($_POST['field3']) ? $_POST['field3'] : NULL);
         // valid data
         if(isset($field1, $field2, $field3)){
            $sql = 'UPDATE ez_login_db.login_account SET la_account = $1, la_active_name = $2, la_active_email = $3 WHERE la_account_id = $4';

            // prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
            $this->os->billing_db->exec_proc($sql,array($field1, $field2, $field3,$member_id));
            $response = '{"success":true}';
         }
      }

      print $response;
   } // end saveProfile()

   /**
    * savePwd()
    */
   public function savePwd(){
      $response = '{success:false}';

      $member_id = $this->os->get_member_id();
      if(isset($member_id) && $member_id != '' && is_numeric($member_id)){
         // get post data
         $field1 = (!empty($_POST['field1']) ? $_POST['field1'] : NULL);
         $field2 = (!empty($_POST['field2']) ? $_POST['field2'] : NULL);
         // valid data
         if(isset($field1, $field2) && $field1 == $field2){
            $this->os->load('security');
            $pwd = $this->os->security->dataHash($field1);
            $sql = 'UPDATE ez_login_db.login_account SET password = $1 WHERE la_account_id = $2';

            // prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
            $this->os->billing_db->exec_proc($sql,array($pwd,$member_id));
            $response = '{"success":true}';
         }
      }

      print $response;
   } // end savePwd()
}
?>