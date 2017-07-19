<?php
/*
 * qWikiOffice Desktop 1.0
 * Copyright(c) 2007-2008, Integrated Technologies, Inc.
 * licensing@qwikioffice.com
 *
 * http://www.qwikioffice.com/license
 */

class log {
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
    * error() Inserts an error log into the the qo_log table.
    *
    * @access public
    * @param {array/string} $v An array or a string.
    */
   public function error($v){
      $this->log('ERROR', $v);
   } // end error()

   /**
    * warning() Inserts a warning log into the the qo_log table.
    *
    * @access public
    * @param {array/string} $v An array or a string.
    */
   public function warning($v){
      $this->log('WARNING', $v);
   } // end warning()

   /**
    * message() Inserts a message log into the the qo_log table.
    *
    * @access public
    * @param {array/string} $v An array or a string.
    */
   public function message($v){
      $this->log('MESSAGE', $v);
   } // end message()

   /**
    * audit() Insertss an audit log into the the qo_log table.
    *
    * @access public
    * @param {array/string} $v An array or string.
    */
   public function audit($v){
      $this->log('AUDIT', $v);
   } // end audit()

   /**
    * log() Inserts log(s) into the the qo_log table.
    *
    * @access private
    * @param $level Level of the log e.g. ERROR, WARNING, MESSAGE, AUDIT
    * @param {array/string} $v An array or a string.
    */
   private function log($level, $v){
      // was an array passed in?
      if(is_array($v)){
         for($i = 0, $len = count($v); $i < $len; $i++){
            $this->query($level, $v[$i]);
         }
      }

      // was a string passed in?
      else if(is_string($v)){
         $this->query($level, $v);
      }
   } // end log()

   /**
    * query() Executes the query.
    *
    * @access private
    * @param {string} $level
    * @param {string} $text
    * @return {boolean}
    */
   private function query($level, $text){
      if(!isset($level, $text) || $level == '' || $text == ''){
         return false;
      }

      $sql = 'INSERT INTO ez_login_db.login_log (level, text, timestamp) VALUES ($1, $2, $3)';
	  $this->os->billing_db->exec_proc($sql,array($level,$text,date('Y-m-d H:i:s')));
      return true;
   } // end query()
}
?>