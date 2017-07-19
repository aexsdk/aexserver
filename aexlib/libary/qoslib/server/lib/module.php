<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

function module_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class module {
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
    * get_all() Returns the definition data for all modules.
    *
    * @access public
    * @return {array} An associative array with the module id as the index.
    */
   public function get_all(){
      $sql = "SELECT lm_module_id as id,lm_data as data FROM ez_login_db.login_modules";

      return $this->query($sql);
   } // end get_all()

   /** get_active() Get active module definitions.
    *
    * @access public
    * @return {array} An associative array with the module id as the index.
    */
   public function get_active(){
      $sql = "SELECT lm_module_id as id,lm_data as data FROM ez_login_db.login_modules WHERE lm_active = 1";

      return $this->query($sql);
   } // end get_active()

   /** get_by_id() Get a module definition by its id.
    *
    * @access public
    * @param {string} $id The id of the module.
    * @return {stdClass} The decoded data object.
    */
   public function get_by_id($id){
      if(isset($id) && $id != ''){
         $sql = "SELECT lm_module_id as id,lm_data as data FROM ez_login_db.login_modules  WHERE lm_module_id = $1";

         $result = $this->query($sql,array($id));

         if($result){
            return $result[$id];
         }
      }
      return null;
   } // end get_by_id()

   /** get_by_type() Get module definitions by type.
    *
    * @access public
    * @param {string} $type The type of the module.
    * @return {array} An associative array with the module id as the index.
    */
   public function get_by_type($type){
      if(isset($type) && $type != ''){
         $sql = "SELECT lm_module_id as id,lm_data as data FROM ez_login_db.login_modules WHERE lm_module_type = $1";
         return $this->query($sql,array($type));
      }

      return null;
   } // end get_by_id()

   /**
    * get_record() Returns a record object with id, type, data and active properties
    *
    * @param {string} $id The module (record) id.
    * @return {stdClass object}
    */
   public function get_record($id){
      // do we have the required param?
      if(!isset($id) || $id == ''){
         return null;
      }

      $sql = "SELECT lm_module_id as id,lm_data as data,lm_module_type as type,lm_active as active FROM ez_login_db.login_modules where lm_module_id=$1";
	  //echo $sql;
      $row = $this->os->billing_db->exec_db_sp($sql,array($id));
      if(is_array($row)){
	      // decode the json data
	      $data = json_decode($row['data']);
	
	      if(is_object($data)){
	         $record = new stdClass();
	         $record->id = $id;
	         $record->type = $row['type'];
	         $record->data = $data;
	         $record->active = $row['active'];
	
	         return $record;
	      }else{
	         //$errors[] = '{ "script": "module.php", "method": "get_record", "message": "In the qo_modules table, row id: '.$row['id'].' has data that could not be decoded" }';
	      }
      }

      return null;
   } // end get_record()

   /**
    * is_active()
    *
    * @access public
    * @param {string} $id The module id
    * @return {boolean}
    */
   public function is_active($id){
      if(isset($id) && $id != ''){
         $sql = "SELECT lm_active as active FROM ez_login_db.login_modules where lm_module_id = $1";
         $row = $this->os->billing_db->exec_db_sp($sql,array($id));
         if(is_array($row))
         	return $row['active'] == '1';
      }

      return false;
   } // end is_active()

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
         $this->os->billing_db->exec_query($sql,$params,module_handle_append_row,$this);
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
		 	if(!empty($row['data'])){
			 	$decoded = json_decode($row['data']);
			 	if(!is_object($decoded)){
	            	$errors[] = "Script: module.php, Method: parse_result, Message: 'qo_modules' table, 'id' ".$row['id']." has 'data' that could not be decoded.\r\n".$row['data'];
			 		continue;
	            }
	            $response[$row['id']] = $decoded;
		 	}
		 }
         // errors to log?
         if(count($errors) > 0){
            $this->os->load('log');
            $this->os->log->error($errors);
         }
      }

      return count($response) > 0 ? $response : null;
   } // end parse_result()

   // dependencies

   /**
    * get_dependencies() Returns an array of dependency objects.
    *
    * @param {string} $module_id
    * @return {array}
    */
   public function get_dependencies($module_id){
      // do we have the required params?
      if(!isset($module_id) || $module_id == ''){
         return null;
      }

	  if(is_object($module_id))
	  	$module = $module_id;
	  else
      	$module = $this->get_by_id($module_id);
      if(!$module || !isset($module->dependencies) || !is_array($module->dependencies)){
         return null;
      }

      return $module->dependencies;
   } // end get_dependencies()

   // files

   /** get_client_files() Returns an array with the directory/files in the order listed ( load order ) in the module definition data.
     * The client files are expected to be listed in the module definition data like so:
     *
     * "client": {
     *    "css": [
     *       {
     *          "directory": "demo/grid-win/client/resources/",
     *          "files": [ "styles.css" ]
     *       }
     *    ],
     *    "javascript": [
     *       {
     *          "directory": "demo/grid-win/client/",
     *          "files": [
     *            "grid-win.js"
     *          ]
     *       }
     *    ]
     * }
     *
     * @access public
     * @param {string} $module_id The module id.
     * @param {string} $key The key to access (.e.g. 'css' or 'javascript').
     * @return {array/null} An array of the file paths on success.  Null on failure.
     **/
   public function get_client_files($module_id, $key){
      // do we have the required params
      if(!isset($module_id) || $module_id == '' || !isset($key) || $key == ''){
         return null;
      }
	  if(is_object($module_id))
	  	$module = $module_id;
	  else
      	$module = $this->get_by_id($module_id);
      //echo sprintf("/*module=%s*/",json_encode($module));
      if(!$module || !isset($module->client->$key) || !is_array($module->client->$key)){
         return null;
      }

      $file_groups = $module->client->$key;
      $response = array();

      // loop through the file groups
      foreach($file_groups as $group){
         $directory = $group->directory;
         $files = $group->files;

         if(!isset($files) || !is_array($files) || !count($files) > 0){
            continue;
         }

         // loop through each file
         foreach($files as $file){
            $response[] = $directory.$file;
         }
      }

      if(!count($response) > 0){
         return null;
      }

      return $response;
   } // end get_client_files()

   /**
    * get_locale_string() Returns the locale directory/file contents for the module/language.
    *
    * @access public
    * @param {string} $module_id
    * @param {string} $language (optional)
    * @return {string}
    */
   public function get_locale_string($module_id, $language=''){
      // do we have the required params?
      if(!isset($module_id) || $module_id == ''){
         return null;
      }

	  if(is_object($module_id))
	  	$module = $module_id;
	  else
      	$module = $this->get_by_id($module_id);
      //$module = $this->get_by_id($module_id);
      if(!$module){
         return null;
      }

      // localization support?
      if(!isset($module->locale->class, $module->locale->directory, $module->locale->languages)){
         return null;
      }

      // supported languages?
      $ls = $module->locale->languages;
      if(!is_array($ls) || !count($ls) > 0){
         return null;
      }

      // default
      $locale = $ls[0];

      if($language != ''){
         foreach($ls as $l){
            if($l == $language){
               $locale = $l;
               break;
            }
         }
      }

      // get the file
      //$document_root = $this->os->get_document_root();
      if(isset($module->is_plugin)){
      	$module_dir = $this->os->get_plugin_dir();
      }else{
      	$module_dir = $this->os->get_module_dir();
      }
      $file = $module_dir.$module->locale->directory.$locale.$module->locale->extension;

      // put the file contents (JSON) into a string
      $string = file_get_contents($file);
      if(!$string){
         return null;
      }

      return $module->locale->class.' = '.$string.';';
   } // end get_locale_string()
}





class module_old {

// Todo: finish module install code

	/** install_module() install the module(s)
	  *
	  * @param {string} $path The path to the module
	  * @return {boolean}
	  **/
	public function install_module($path){
        // TODO: enforce security

        $response = true;

        //$document_root = $this->os->get_document_root();
        $modules_dir = $this->os->get_module_dir();
        //$modules_dir = $modules_dir;
        //$modules_dir = preg_replace("/\/modules\/(.)*/", "/modules/", $modules_dir);
        $module_dir =preg_replace("/(.)*modules\//", '', $path);
        $path = str_replace("//", "/", $modules_dir.$module_dir);

        // if this is a directory
        if(is_dir($path)){

            // open the directory
            $dh = opendir($path);
            if($dh){

            	// loop through each file in the directory
	            while(($file = readdir($dh)) !== false){

	            	// if the file is a directory and not the current or parent directory
	                if(is_dir($path.$file) && $file != '.' && $file != '..' && $file != '.svn'){ // .svn for testing locally

	                	// use recursive call to search sub directories
	                	$this->install_module($path.$file."/");
	                }

	                // if file is module.xml file
	                if($file == "module.xml"){
						//print 'XML Found: '.$path.$file.'<br />';
	                	// read the xml file
	                	$info = $this->read_xml($path.$file);
print '<pre>';
print_r($info);
print '</pre>';
		                // if the module is not already installed
		                if(!$this->is_installed($info["module_id"], $info["version"])){
		                	// register the module into the database
//		                	if(!$this->register($info)){
//		                		$response = false;
//		                	}
		                }else{
		                	$response = false;
		                }
					}else{
						$response = false;
					}
            	}

            	closedir($dh);
			}else{
				$response = false;
			}
        }else{
        	$response = false;
        }

        return $response;
    } // end install_module()

    /** read_xml() Reads the module.xml file
	  *
	  * @access private
	  * @param {string} $file The file path
	  * @return {array}
	  **/
    private function read_xml($file){
        $info= Array();

        $dom = new DomDocument();
        $dom->load($file);

		// info
		$info['author'] = $dom->getElementsByTagName('author')->item(0)->nodeValue;
		$info['version'] = $dom->getElementsByTagName('version')->item(0)->nodeValue;
		$info['name'] = $dom->getElementsByTagName('name')->item(0)->nodeValue;
		$info['url'] = $dom->getElementsByTagName('url')->item(0)->nodeValue;
		$info['description'] = $dom->getElementsByTagName('description')->item(0)->nodeValue;
		$info['module_type'] = $dom->getElementsByTagName('module_type')->item(0)->nodeValue;
		$info['module_id'] = $dom->getElementsByTagName('module_id')->item(0)->nodeValue;
		$info['locales'] = $dom->getElementsByTagName('locales')->item(0)->nodeValue;
		$info['locale_directory'] = $dom->getElementsByTagName('locale_directory')->item(0)->nodeValue;

		// actions
		$row = $dom->getElementsByTagName('action');
		for($i = 0, $len = $row->length; $i < $len; $i++){
			$info['actions'][$i] = $row->item($i)->nodeValue;
		}

		// dependencies
		$row = $dom->getElementsByTagName('dependency');
		for($i = 0, $len = $row->length; $i < $len; $i++){
			$info['dependencies'][$i] = $row->item($i)->nodeValue;
		}

		// files
		$count = 0;
		$dataset = $dom->getElementsByTagName('file');
		foreach($dataset as $row){
			// attributes
			$info['files'][$count]['is_stylesheet'] = 0;
			$info['files'][$count]['is_server_module'] = 0;
			$info['files'][$count]['is_client_module'] = 0;
			$info['files'][$count]['class_name'] = '';

			if($row->hasAttributes()){
				// type
				$type = $row->getAttribute('type');

				if($type == 'stylesheet'){ $info['files'][$count]['is_stylesheet'] = 1; }
				else if($type == 'server_module'){ $info['files'][$count]['is_server_module'] = 1; }
				else if($type == 'client_module'){ $info['files'][$count]['is_client_module'] = 1; }

				// class_name
				if($type == 'client_module' || $type == 'server_module'){
					$info['files'][$count]['class_name'] = $row->getAttribute('class_name');
				}
			}

			// directory
			$xml_directories = $row->getElementsByTagName('directory');
			$info['files'][$count]['directory'] = $xml_directories->item(0)->nodeValue;

			// name
			$xml_names = $row->getElementsByTagName('name');
			$info['files'][$count]['name'] = $xml_names->item(0)->nodeValue;

			$count++;
		}

		return $info;
	} // end read_xml()

	/** is_installed() checks whether a module is installed
	  *
	  * @param {string} $id The module id
	  * @param {string} $version The module version
	  * @return {boolean}
	  **/
	private function is_installed($id, $version){
		// TODO: enforce security
		$response = false;
		// get ids if needed
		//$member_id = $member_id != '' ? $member_id : $this->get_member_id();
		//$group_id = $group_id != '' ? $group_id : $this->get_group_id($member_id);

		//if($member_id != '' && $group_id != '' && $id != '' && $version != ''){
		if($id != '' && $version != ''){
			$sql = "SELECT lm_id FROM ez_login_db.login_modules WHERE m_module_id = $1  AND lm_version = $2";
			$r = $this->os->billing_db->exec_db_sp($sql,array($id,$version));
			if(is_array($r))
				$response = $r['lm_id'] != '';
		}

		return $response;
	} // end is_installed()

	/** register() registers/inserts the module data into the database
	  *
	  * @param $info array, the module info
	  * @return {boolean} Success
	  **/
	private function register($info){
		// TODO: enforce security

		$response = true;

		$this->os->load('session');
		$member_id = $this->os->session->get_member_id();
		$group_id = $this->os->session->get_group_id();

		//if($member_id != '' && $group_id != '' && is_array($info) && count($info) > 0){
		if(is_array($info) && count($info) > 0){

			//print "<pre>";
			//print_r($info);
			//print "</pre>";

			// insert into qo_modules table
			$sql = "INSERT INTO ez_login_db.login_modules (lm_author,lm_version,lm_url,lm_name,lm_description,lm_module_type,lm_module_id,lm_locales,lm_locale_directory,lm_active
				) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)";

			// prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
			$params = array(
				$info['author'],
				$info['version'],
				$info['url'],
				$info['name'],
				$info['description'],
				$info['module_type'],
				$info['module_id'],
				$info['locales'],
				$info['locale_directory'],
				1);
			$this->os->billing_db->exec_proc($sql,$params);
			// Todo: register actions
			$response = $this->register_files($info);
		}else{
			$response = false;
		}

		return $response;
	} // end register()

	private function register_files($info){
		if(!is_array($info) || !isset($info['files'])){ return false; }
		$response = true;

		if($this->is_installed($info['module_id'],$info['version'])){
			$id = $row['id'];
			$files = $info['files'];
			// insert each file into qo_modules_files table
			for($i = 0, $len = count($files); $i < $len; $i++){
				$sql = "INSERT INTO ez_login_db.login_modules_files (
					lm_modules_id,
					lmf_directory,
					lmf_file,
					lmf_is_stylesheet,
					lmf_is_server_module,
					lmf_is_client_module,
					lmf_class_name) VALUES ($1, $2, $3, $4, $5, $6, $7)";

				// prepare the statement, prevents SQL injection by calling the PDO::quote() method internally
				$params = array(
					$id,
					$files[$i]['directory'],
					$files[$i]['name'],
					$files[$i]['is_stylesheet'],
					$files[$i]['is_server_module'],
					$files[$i]['is_client_module'],
					$files[$i]['class_name']
					);
				$this->os->billing_db->exec_proc($sql,$params);
			}
		}else{
			$response = false;
		}

		return $response;
	} // end register_files()
}
?>