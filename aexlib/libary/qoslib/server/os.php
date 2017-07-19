<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

require('kernal.php');

class os extends kernal {
	public $config;
	public $LIBRARIES_DIR = 'modules/common/libraries/';
	public $MODULES_DIR = 'modules/';
	public $PLUGINS_DIR = 'plugins/';
	public $THEMES_DIR = 'resources/';
	public $WALLPAPERS_DIR = 'resources/wallpapers/';
	/**
	 * __construct()
	 *
	 * @access public
	 */
	public function __construct($config){
		parent::__construct($config);
		$this->LIBRARIES_DIR = $this->config->OS_ROOT_DIR.'modules/common/libraries/';
		$this->MODULES_DIR = $this->config->OS_ROOT_DIR.'modules/';
		$this->PLUGINS_DIR = $this->config->OS_PLUGIN_DIR;
		$this->THEMES_DIR = $this->config->OS_ROOT_DIR.'resources/';
		$this->WALLPAPERS_DIR = $this->config->OS_ROOT_DIR.'resources/wallpapers/';
		//echo sprintf("/* %s\r\n%s*/",$this->THEMES_DIR,$this->WALLPAPERS_DIR);
	}
	/*
	 * 主类析构函数
	*/
	function __destruct() {
		parent::__destruct();
    }
	
	/**
	 * init() Initial page load or refresh has occured.
	 * Called from index.php.
	 *
	 * @access public
	*/
	public function init(){
	     // clear the session data
		$this->load('session');
		$this->session->set_data();
	}

   // get the login url

   /**
    * get_login_url()
    *
    * @access public
    */
   public function get_login_url(){
      return sprintf("http://%s:%s%s?act=login",$_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT'],$_SERVER['SCRIPT_NAME']);//$this->config->LOGIN_URL;
   } // end get_login_url()

   // get directories

   /**
    * get_library_dir()
    *
    * @access public
    */
   public function get_library_dir(){
      return $this->LIBRARIES_DIR;
   } // end get_library_dir()
   
   public function get_plugin_dir(){
		return $this->PLUGINS_DIR;
   }

   /**
    * get_module_dir()
    *
    * @access public
    */
   public function get_module_dir(){
      return $this->MODULES_DIR;
   } // end get_module_dir()

   /**
    * get_theme_dir()
    *
    * @access public
    */
   public function get_theme_dir(){
      return $this->THEMES_DIR;
   } // end get_theme_dir()

   /**
    * get_wallpaper_dir()
    *
    * @access public
    */
   public function get_wallpaper_dir(){
      return $this->WALLPAPERS_DIR;
   } // end get_wallpaper_dir()

   // session data

   /**
    * session_exists() Checks if a session exists.
    *
    * @return {boolean}
    */
   public function session_exists(){
      $this->load('session');
      return $this->session->exists();
   } // end session_exists()

   /**
    * get_group_id() Returns the current group id for the session.
    *
    * @access public
    * @return {integer}
    */
   public function get_group_id(){
      $this->load('session');
      return $this->session->get_group_id();
   } // end get_group_id()

   /**
    * get_member_id() Returns the current member id for the session.
    *
    * @access public
    * @return {integer}
    */
   public function get_member_id(){
      $this->load('session');
      return $this->session->get_member_id();
   } // end get_member_id()

   // privileges

   /**
    * is_group_allowed() Returns true if a group has allow privilege to the module (optionally its method).
    *
    * @param {integer} $group_id The group id
    * @param {integer} $module_id The module id
    * @param {string} $method_name (optional) The method name
    * @return {boolean}
    */
   public function is_group_allowed($group_id, $module_id, $method_name = null){
      // do we have the required params?
		if(!isset($group_id, $module_id) || $group_id == '' || $module_id == ''){
         return false;
      }

      // get the privilege id for the group
      $this->load('group');
      $privilege_id = $this->group->get_privilege_id($group_id);
	  //echo sprintf("/*p_id=%s*/",json_encode($privilege_id));
      if(!$privilege_id){
         return false;
      }

      // return true if allowed
      $this->load('privilege');
      if($this->privilege->is_allowed($privilege_id, $module_id, $method_name)){
         return true;
      }

      return false;
   } // end is_group_allowed()

   public function print_library(){
   	$this->load('library');
   	$ls = $this->library->get_active();
   	if(is_object($ls)){
   		$resp = '';
   		foreach ($ls as $id=>$data){
			if(isset($data->is_plugin)){
				$module_dir = $this->get_plugin_dir();
			}else{
				$module_dir = $this->get_module_dir();
			}
   			$file_groups = $data->client->css;
			// loop through the file groups
			foreach($file_groups as $group){
			   $directory = path_to_url($module_dir.$group->directory);
			   $files = $group->files;

			   if(isset($files) && is_array($files) && count($files) > 0){
				   // loop through each file
				   foreach($files as $file){
					  $resp .= sprintf("<link rel='stylesheet' type='text/css' href='%s' />\r\n",$directory.$file);
				   }
			   }
			}
			$file_groups = $data->client->javascript;
			// loop through the file groups
			foreach($file_groups as $group){
			   $directory = path_to_url($module_dir.$group->directory);
			   $files = $group->files;

			   if(isset($files) && is_array($files) && count($files) > 0){
				   // loop through each file
				   foreach($files as $file){
					  $resp .= sprintf("<script src='%s' />\r\n",$directory.$file);
				   }
			   }
			}
			return $resp;
   		}
   	}else{
   		return '';
   	}
   }
   // modules

   /**
    * get_modules() Returns an array of validated modules that the member/group has access to.
    *
    * @access public
    * @return {array} An associative array of modules.
    */
   public function get_modules(){
      $this->load('module');

      // do we have the valid module ids already in session data?
      $ids = $this->get_valid_module_ids();
      if($ids){
         $valid_modules = array();

         foreach($ids as $id){
            $module = $this->module->get_by_id($id);
            if($module){
               $valid_modules[$id] = $module;
            }
         }
		 //echo sprintf("/* valid_modules=%s */",array_to_string("\r\n",$valid_modules));
         return $valid_modules;
      }

      // we do not have them in session data, get all active modules and validate them
      $active_modules = $this->module->get_active();
      if(isset($active_modules) && is_array($active_modules) && count($active_modules) > 0){
         $arg = new stdClass();
         $valid_ids = array();
         $valid_modules = array();

         foreach($active_modules as $id => $module){
            $arg->id = $id;
            $arg->type = 'module';

			//echo sprintf("/* id=%s,module=%s */",$arg->id,json_encode($module));
            $success = $this->validate($arg);
            if($success){
               $valid_ids[] = $id;
               $valid_modules[$id] = $module;
            }
         }

         // set the valid module ids for quick lookup
         if(count($valid_ids) > 0){
            $this->set_valid_module_ids($valid_ids);
         }
		//echo sprintf("/* active_modules=%s */",array_to_string("\r\n",$valid_modules));
         return $valid_modules;
      }

      return null;
   } // end get_modules()

   // print css

   /**
    * print_module_css() Prints all the css link tags for the theme and the modules (and their dependencies) that the member can load
    *
    * @access public
    */
   public function print_module_css(){
      $arg = new stdClass();
      $modules = $this->get_modules();

      $resp = '';
      if(isset($modules) && is_array($modules) && count($modules) > 0){
         foreach($modules as $id => $module){
            $arg->id = $id;
            $arg->type = 'module';

            $resp .= $this->print_css($arg);
         }
      }
      return $resp;
   } // end print_module_css()

   // load module

   /**
    * load_module()
    *
    * @access public
    * @param {string} $module_id The module id.
    */
   public function load_module($module_id){
      if(isset($module_id) && $module_id != ''){
         $arg = new stdClass();
         $arg->id = $module_id;
         $arg->type = 'module';
         $this->print_javascript($arg);
      }
   } // end load_module()

   // module requests

   /** make_request() Will check the group privileges of the member and call the requested method if allowed.
	  *
	  * @param {string} $module_id The module id.
	  * @param {string} $method_name The name of the method.
	  **/
	public function make_request($module_id, $method_name){
      // do we have the required params?
      if(!isset($module_id, $method_name) || $module_id == '' || $method_name == ''){
         die("{success: false, message: 'Missing required params!'}");
      }

      // get the group id from session
      $this->load('session');
		$group_id = $this->session->get_group_id();

		if(!isset($group_id)){
			die("{success: false, message: 'You are not currently logged in'}");
		}

      // check group privilege (is the member allowed to execute this method)
      if(!$this->is_group_allowed($group_id, $module_id, $method_name)){
         die("{success: false, message: 'You do not have the required privileges!'}");
      }

      $error_found = false;
      $error_message = '';

      // get the module data
      $this->load('module');
      $module = $this->module->get_by_id($module_id);

      // do we have the module data
      if(!isset($module)){
         $error_found = true;
         $error_message = 'Message: Missing data for module: '.$module_id;
      }

      // do we have the required server data
      if(!isset($module->server, $module->server->class, $module->server->file)){
         $error_found = true;
         $error_message = 'Message: missing server data for module : '.$module_id;
      }

      //$document_root = $this->get_document_root();
		if(isset($module->is_plugin)){
			$module_dir = $this->get_plugin_dir();
		}else{
			$module_dir = $this->get_module_dir();
		}
      //$module_dir = $this->get_module_dir();
      $file = $module_dir.$module->server->file;
      $class = $module->server->class;

      // does the file exist and is a regular file
      if(!is_file($file)){
         $error_found = true;
         $error_message = 'Message: File ('.$file.') not found for module: '.$module_id;
      }

      require($file);

      // does the class exist?
      if(!class_exists($class)){
         $error_found = true;
         $error_message = 'Message: '.$class.' does not exist for server module: '.$module_id;
      }

      $module = new $class($this);

      // does the method exist?
      if(!method_exists($module, $method_name)){
         $error_found = true;
         $error_message = 'Message: '.$method_name.' does not exist for server module: '.$module_id;
      }

      if(!$error_found){
         $module->$method_name();
      }

		// log errors
		if($error_found){
			$this->errors[] = 'Script: os.php, Method: call_module_method, Message: '.$error_message;
			$this->load('log');
		   $this->log->error($this->errors);
		}
	} // end make_request()

   // member information

   /**
    * get_locale() Returns the locale for the member.
    *
    * @access public
    * @param {integer} $member_id The member id.
    */
   public function get_member_locale($member_id){
      // do we have the required param?
      if(!isset($member_id)){
         return null;
      }

      $this->load('member');
      return $this->member->get_locale($member_id);
   } // end get_locale()

   /**
    * get_member_preference() Returns the id of the theme set for the group/member.
    *
    * @access public
    * @param {integer} $member_id
    * @param {integer} $group_id
    * @return {stdClass}
    */
	public function get_member_preference($member_id, $group_id){
      // do we have the required params?
      if(!isset($member_id, $group_id)){
         return null;
      }

      $this->load('preference');
      $preference = $this->preference->get($member_id, $group_id);

      // use the default?
      if(!$preference){
         $preference = $this->preference->get('0', '0');
      }

      if($preference){
         return $preference;
      }

      return null;
	} // end get_member_preference()

   // login and logout functions

   /**
    * login()
    *
    * @access public
    * @param $module string
    * @param $user string
    * @param $pass string
    */
   /*public function login($user, $pass, $group_id = ''){
      // do we have the email address?
      if(!isset($user) || !strlen($user)){
         die("{errors:[{id:'user', message:'Required Field'}]}");
      }

      // do we have the password?
      if(!isset($pass) || !strlen($pass)){
         die("{errors:[{id:'pass', message:'Required Field'}]}");
      }

      $this->load('member');

      // does the member exist?
      if(!$this->member->exists($user)){
         die("{errors:[{id:'user', message:'Member not found'}]}");
      }

      // is the member active?
      if(!$this->member->is_active($user)){
         die("{errors:[{id:'user', message:'This account is not active'}]}");
      }

      // do we have a successful login?
      $member_id = $this->member->get_id($user, $pass, false); // pass in false to flag that $pass is not encrypted
      if(!$member_id){
         die("{errors:[{id:'user', message:'Invalid login'}]}");
      }

      // was a group id supplied?
      if($group_id == ''){
         $this->load('group');

         // get the active groups for the member
         $groups = $this->group->get_by_member_id($member_id, true);

         // any groups returned?
         if(!$groups){
            die("{errors:[{id:'user', message:'This account has no sign in group'}]}");
         }

         $count = count($groups);

         // if the member is assigned to more than one group, allow the member to choose which group to login under
         if($count > 1){
           die("{success:true, groups: ".json_encode($groups)."}");
         }

         // the member is assigned to only one group, login with this group id
         $group_id = $groups[0]['id'];
      }

      // get our random session id
      $this->load('utility');
      $session_id = $this->utility->build_random_id();

      $this->load('session');

      // delete any existing sessions for the member
      //$this->session->delete(null, $member_id);

      // attempt to save login session
      $success = $this->session->add($session_id, $member_id, $group_id);

      if($success){
         die("{success: true, sessionId: '".$session_id."'}");
      }

      print "{errors: [{id: 'user', message: 'Login Failed'}]}";
	} // end login()
*/
   /**
    * logout()
	 *
	 * @access public
	 */
	/*public function logout(){
      $this->load('session');
		$session_id = $this->session->get_id();

		if(isset($session_id)){
         $success = $this->session->delete($session_id);
			if($success){
            // no longer using PHP session
				//session_destroy();

				// clear the cookie
				setcookie('sessionId', '');

			    // redirect to login page
				header('Location: '.$this->get_login_url());
			}
		}
	} // end logout()
*/
   // forgot password

   /**
    * forgot_password()
	 *
    * @access public
	 * @param {string} $email
    * @return {JSON}
	 */
	/*public function forgot_password($email){
		$response = "{success: false}";

		if(function_exists('mail')){
			if(!isset($email) || !strlen($email)){
				$response = "{ errors: [{ id: 'user', message:'Required Field' }] }";
			}else if(class_exists('config')){

				$sql = "SELECT
					password
					FROM
					qo_members
					WHERE
					email_address = '".$email."'";

            $result = $this->db->conn->query($sql);
				if($result){
               $row = $result->fetch(PDO::FETCH_ASSOC);
				   if($row){
						$password = $row['password'];

						$to = $email;
						$subject = "Your ".$this->config->DOMAIN." Account";
						$from_header = "From: ".$this->config->EMAIL;
						$contents = "An 'I forgot my password' request was received from your account.\n\nYour password is: ".$password;

						if(mail($to, $subject, $contents, $from_header)){
							$response = "{success: true}";
						}
				    }else{
				       $response = "{ errors:[{ id: 'user', message: 'Member not found' }] }";
				    }
				}
			}
		}

		return $response;
	} // end forgot_password()
*/
   // signup requests

   /**
    * signup()
	 *
    * @access public
	 * @param {string} $first_name
	 * @param {string} $last_name
	 * @param {string} $email
	 * @param {string} $email_verify
	 * @param {string} $comments
	 */
	/*public function signup($first_name, $last_name, $email, $email_verify, $comments){
		$response = "{success: false}";

		if(!isset($first_name)||!strlen($first_name)){
			$response = "{errors:[{id:'first_name', message:'Your first name is required'}]}";
		}elseif(!isset($last_name)||!strlen($last_name)){
			$response = "{errors:[{id:'last_name', message:'Your last name is required'}]}";
		}elseif(!isset($email)||!strlen($email)){
			$response = "{errors:[{id:'email', message:'Your email address is required'}]}";
		}elseif(!isset($email_verify)||!strlen($email_verify)){
			$response = "{errors:[{id:'email_verify', message:'Please verify your email address again'}]}";
		}else if($email !== $email_verify){
			$response = "{errors:[{id:'email_verify', message:'Please verify your email address again'}]}";
		}else if($this->is_spam($email)){
			$response = "{errors:[{id:'email', message:'This email address has been flagged as spam'}]}";
		}else if($this->exists($email)){
			$response = "{errors:[{id:'email', message:'This email address is already in use'}]}";
		}else if($this->signup_exists($email)){
			$response = "{errors:[{id:'email', message:'This email address has already signed up'}]}";
		}else{

			$sql = "INSERT INTO qo_signup_requests (first_name, last_name, email_address, comments) VALUES (?, ?, ?, ?)";

			// prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
			$sql = $this->db->conn->prepare($sql);
			$sql->bindParam(1, $first_name);
			$sql->bindParam(2, $last_name);
			$sql->bindParam(3, $email);
			$sql->bindParam(4, $comments);
			$sql->execute();

			$code = $sql->errorCode();
			if($code == '00000'){
				$response = "{success: true}";
			}else{
				$this->errors[] = "Script: member.php, Method: signup, Message: PDO error code - ".$code;
				$this->os->load('log');
				$this->os->log->error($this->errors);
			}
		}

		return $response;
	} // end signup()
*/
}
?>