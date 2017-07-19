<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

class session {

	private $os;

   /**
    * __construct()
    *
    * @access public
    * @param {class} $os The os.
    */
	public function __construct($os){
		$this->os = $os;
	} // end __construct()

	/**
    * get_id() Returns the session id.
	 *
	 * @access private
    * @return {string}
	 */
	public function get_id(){
		if(isset($_COOKIE['sessionId'])){
		   return $_COOKIE['sessionId'];
		}
	
	    if(isset($_REQUEST['sessionId'])){
	       if(!isset($_COOKIE['sessionId'])){
	          setCookie('sessionId', $_REQUEST['sessionId'], 0, '/');
	       }
	       return $_REQUEST['sessionId'];
	    }

      return null;
	} // end get_id()

   /**
    * add() Adds a new session record.
    *
    * @access public
    * @param {string} $session_id The session id.
    * @param {integer} $member_id The member id.
    * @param {integer} $group_id The group id.
    * @return {boolean}
    */
   public function add($session_id, $member_id, $group_id){
      if(isset($session_id, $member_id, $group_id) && $session_id != '' && $member_id != '' && $group_id != ''){
		$sql = "INSERT INTO ez_login_db.login_sessions (ls_id, la_account_id, lg_group_id, ls_ip, ls_date ,ls_expired_time ) VALUES ($1,$2,$3,$4,$5,$6) ";
		$this->os->billing_db->exec_proc($sql,array(
			$session_id,
			$member_id,
			$group_id,
			$_SERVER['REMOTE_ADDR'],
			date("Y-m-d H:i:s"),
			//isset($this->os->config->session_expired)?$this->os->config->session_expired:30,
			date("Y-m-d H:i:s")
			));
		return true;
      }

      return false;
   } // end add()

	/**
    * delete() Deletes the record(s) for either the passed in session id or member id.
	 *
	 * @access public
    * @param {string} $session_id (optional) The session id.
    * @param {integer} $member_id (optional) The member id.
    * @return {boolean}
	 */
	public function delete($session_id = null, $member_id = null){
		if(!isset($session_id) && !strlen($session_id) && !isset($member_id) && !strlen($member_id)){
		   return false;
		}
		if(isset($session_id) && $session_id != '')
			$session_id = '%';
		if(isset($member_id) && $member_id != '')
			$member_id = '%';
		$sql = "DELETE from  ez_login_db.login_sessions where ls_id like $1 and la_account_id like $2 ";
		$this->os->billing_db->exec_proc($sql,array($session_id,$member_id));
		return true;
	} // end delete()

   /**
    * exists() Returns true/false depending on if the session is found.
    *
    * @access public
    * @param $session_id string
    * @return {boolean}
    */
   public function exists(){
      $session_id = $this->get_id();
	  //echo sprintf("Session->exists:sessionId=%s",$session_id);
      if($session_id != ''){
		$id = $this->get_member_id();
		//echo sprintf("Session->exists:userId=%s",$id);
		if(!empty($id) && $id != '')
			return true; 
      }

      return false;
   } // end exists()

   /**
    * get_group_id() Returns the member's group id for this session.
    *
    * @access public
    * @return {integer}
    */
   public function get_group_id(){
      $session_id = $this->get_id();

      if(isset($session_id) && $session_id != ''){
         $sql = "select lg_group_id as id from  ez_login_db.login_sessions where ls_id = $1";
		 
         $r = $this->os->billing_db->exec_db_sp($sql,array($session_id));
         if(is_array($r)){
         	return $r['id'];
         }
      }

      return null;
   } // end get_group_id()

   /**
    * get_member_id() Returns the member id for this session.
    *
    * @access public
    * @return {integer}
    */
   public function get_member_id(){
      $session_id = $this->get_id();

      if($session_id != ''){
         $sql = "select la_account_id from  ez_login_db.login_sessions where ls_id = $1";
		 //echo $sql;
         $r = $this->os->billing_db->exec_db_sp($sql,array($session_id));
         if(is_array($r)){
         	return $r['la_account_id'];
         }
      }

      return null;
   } // end get_member_id()

   /** get_data() Returns session data.
     *
     * @access public
     * @param {string} $path A list of data keys seperated by forward ( / ) slashes ( optional ).
     *
     * Example: 'modules/qo-preferences'
     **/
	 public function get_data($path = null){
		// session id?
		$session_id = $this->get_id();
		if(!isset($session_id) || $session_id == ''){
		   return null;
		}
		
		$base = null;
		
		$sql = "select ls_data from  ez_login_db.login_sessions where ls_id = $1";
		
		$r = $this->os->billing_db->exec_db_sp($sql,array($session_id));
		if(is_array($r)){
			$decoded = json_decode($r['ls_data']);
			if(is_object($decoded)){
				$base = $decoded;
			}
		}
		
		if(!$base){
		   return null;
		}

		// was a data path passed in?
		if(isset($path) && $path != ''){
		   $keys = explode('/', $path);
		
		   if(count($keys) > 0){
		      foreach($keys as $key){
		         // array?
		         if(is_array($base)){
		            if(!isset($base[$key])){
		               return null;
		            }
		            $base = $base[$key];
		         }
		         // object?
		         else if(is_object($base)){
		            if(!isset($base->$key)){
		               return null;
		            }
		            $base = $base->$key;
		         }
		      }
		   }
		}
		
		return $base;
   } // end get_data()

   /**
    * set_data() Sets the data for the session.
    *
    * @access public
    * @param {array/object} $data (optional) The data object
    * @return {boolean}
    */
   public function set_data($data = null){
      // session?
      $session_id = $this->get_id();
      if(!isset($session_id) || $session_id == ''){
         return false;
      }

      $data_string = '';

      // do we have the optional param?
      if(is_object($data) || is_array($data)){
         // can encode the data?
         $data_string = json_encode($data);
         if(!is_string($data_string)){
            return false;
         }
      }

      // update the data field
      $sql = "UPDATE ez_login_db.login_sessions SET ls_data = $1 WHERE ls_id = $2";

      $r = $this->os->billing_db->exec_proc($sql,array(
      	$data_string,
      	$session_id
      	));

      return true;	//如果发生产错误，exec_db_send_sp就已经处理了
   } // end set_data()
}

?>