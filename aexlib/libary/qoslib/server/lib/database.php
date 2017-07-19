<?php

class database {

	public $conn;
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
    * connect() Establishes a connection to the database
    *
    * @access public
    * @return {boolean}
	 */
	public function connect(){
      //try{
         $this->conn = $this->os->billing_db;
         //$this->conn->setAttribute(PDO::ATTR_ERRMODE, $error_mode);

         return true;
      //}catch(Exception $e){
         //print $e->getMessage();
      //}
      //return false;
   } // end connect()
}
?>