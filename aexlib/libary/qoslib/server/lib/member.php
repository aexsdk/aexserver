<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */


function member_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class member {
	public $rows;
	private $os;
	private $errors;

   /** __construct()
    *
    * @access public
    * @param {class} $os The os.
    */
	public function __construct($os){
		$this->os = $os;
		$this->errors = array();
	} // end __construct()

   /**
    * get_data()
    * Returns an associative array containing name/value pairs.
    *
    * @param {Integer} $member_id
    */
   public function get_data($member_id){
      if(isset($member_id) && $member_id != ''){
         $sql = "SELECT * FROM ez_login_db.login_accounts WHERE la_account_id = ".$member_id;

         $this->rows = array();
         $this->os->billing_db->exec_query($sql,array($member_id),member_handle_append_row,$this);
         return $this->rows;
      }
      return null;
   } // end get_data()

   /**
    * get_field_value()
    * Returns the members record value for the passed in field.
    *
    * @param {Integer} $member_id
    * @param {String} $field
    */
   public function get_field_value($member_id, $field){
      if(isset($member_id, $field) && $member_id != '' && $field != ''){
         $sql = sprintf("SELECT %s FROM ez_login_db.login_account WHERE la_account_id = $1",$field);

         $r = $this->os->billing_db->exec_db_sp($sql,array($member_id));
         if(is_array($r))
         	return $r[$field];
      }

      return null;
   } // end get_field_value()

   /**
    * exits() Returns the member id if a record exists for the passed in email address.
    *
    * @access public
    * @param {string} $email The members email address
    * @return {string} The id on success.  False on failure.
    */
   public function exists($email){
      if(isset($email) && $email != ''){
         $sql = "SELECT la_account_id as id from ez_login_db.login_account where la_active_email = $1";

         $r = $this->os->billing_db->exec_db_sp($sql,array($email));
         if(is_array($r))
         	return $r['id'] != '';
      }

      return false;
   } // end exits()

   /**
    * is_active()
    *
    * @access public
    * @param {string} $email The members email address
    * @return {boolean}
    */
   public function is_active($email){
      if(isset($email) && $email != ''){
         $sql = "SELECT la_active as active from ez_login_db.login_account where la_active_email = $1";

         $r = $this->os->billing_db->exec_db_sp($sql,array($email));
         if(is_array($r))
         	return $r['active'] == '1';
      }
      return false;
   } // end is_active()

   /**
    * get_id() Returns the member id.
    *
    * @access public
    * @param {string} $email The member email address.
    * @param {string} $password (optional) The member password.
    * @param {boolean} $is_encrypted (optional) True if the password passed in is already encrypted.
    * @return {integer}
    */
   public function get_id($email, $password = null, $is_encrypted = false){
		$sql = "SELECT la_account_id as id from ez_login_db.login_account where la_active_email = $1 and la_password = $password ";
		if($is_encrypted == false){
               $this->os->load('security');
               // pass the member's current encrypted password as salt
               $password = $this->os->security->encrypt($password, $this->get_password($email));
        }
		$params = array(
			$email,
			$password
			);
		$r = $this->os->billing_db->exec_db_sp($sql,$params);
		if(is_array($r))
			return $r['id'] != '';
		return null;
   } // end get_id()

	/**
    * get_name() Returns the name of a member.
	 *
    * @access public
	 * @param {integer} $member_id The id of the member.
    * @return {string}
	 */
	public function get_name($member_id){
      	return $this->get_field_value($member_id,'la_active_name');
   } // end get_name()

	/**
    * get_locale() Returns the locale of a member.
	 *
    * @access public
	 * @param {integer} $member_id
    * @return {string}
	 */
	public function get_locale($member_id){
      if(!isset($member_id) || $member_id == ''){
         return null;
      }
      $lang = $_REQUEST['lang'];
      if(empty($lang))
      	$lang = $this->os->sessions['lang']; //$lang = $this->get_field_value($member_id,'la_locale');
      
	  return $lang;
	} // end get_locale()

}
?>