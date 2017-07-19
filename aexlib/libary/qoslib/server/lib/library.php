<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

function library_handle_append_row($context,$index,$row){
	array_push($context->rows,$row);
	//echo sprintf("Row[%d]=%d<br>",$index,count($context->rows));
	//var_dump($context);
}

class library {

   //private $errors = array();
	private $library_dir = null;
   private $os = null;
	
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

      //$this->document_root = $os->get_document_root();
		$this->library_dir = $os->get_library_dir();
      $this->os = $os;
	} // end __construct()

   /**
    * get_all() Get all library definitions.
    *
    * @access public
    * @return {array} An associative array with the library id as the index.
    */
   public function get_all(){
      $sql = "SELECT ll_id as id,ll_data as data FROM ez_login_db.login_libraries";

      return $this->query($sql);
   } // end get_all()

   /**
    * get_active() Get active library definitions.
    *
    * @access public
    * @return {array} An associative array with the library id as the index.
    */
   public function get_active(){
      $sql = "SELECT ll_id as id , ll_data as data  FROM ez_login_db.login_libraries  WHERE ll_active = 1";

      return $this->query($sql);
   } // end get_active()

   /**
    * get_by_id()
    *
    * @param {string} $id The library id.
    * @return {stdClass} A data object
    */
   public function get_by_id($id){
      if(isset($id) && $id != ''){
         $sql = "SELECT ll_id as id,ll_data as data FROM ez_login_db.login_libraries WHERE ll_id = $1 ";

         $result = $this->query($sql,array($id));
         if($result){
            return $result[$id];
         }
      }

      return null;
   } // end get_by_id()

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
         $this->os->billing_db->exec_query($sql,$params,library_handle_append_row,$this);
         if(is_array($this->rows)){
            return $this->parse_result($this->rows);
         }
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
               $errors[] = "Script: library.php, Method: parse_result, Message: 'login_libraries' table, 'id' ".$row['id']." has 'data' that could not be decoded";
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

   /**
    * get_dependencies() Returns an array of dependency objects.
    *
    * @param {string} $library_id
    * @return {array}
    */
   public function get_dependencies($library_id){
      // do we have the required params?
      if(!isset($library_id) || $library_id == ''){
         return null;
      }

      $library = $this->get_by_id($library_id);
      if(!$library || !isset($library->dependencies) || !is_array($library->dependencies)){
         return null;
      }

      return $library->dependencies;
   } // end get_dependencies()

   /** get_client_files() Returns an array with the files in the order listed ( load order ) in the library definition data.
     * The client files are expected to be listed in the library definition data like so:
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
     * @param {string} $library_id The library id.
     * @param {string} $key The key to access (.e.g. 'css' or 'javascript').
     * @return {array/null} An array of the file paths on success.  Null on failure.
     **/
   public function get_client_files($library_id, $key){
      // do we have the required params
      if(!isset($library_id) || $library_id == '' || !isset($key) || $key == ''){
         return null;
      }

      $library = $this->get_by_id($library_id);
      if(!$library || !isset($library->client->$key) || !is_array($library->client->$key)){
         return null;
      }

      $file_groups = $library->client->$key;
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

}
?>