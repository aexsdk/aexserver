<?php

/*
	错误处理类，一般会在程序开始创建改类以便进行响应的错误处理。
	更新列表：
		2010-4-25  Tony
			添加了对滞后调入action对应的错误列表的支持；
			修改了调入列表的逻辑，后来调入的如果有与之前代码相同的代码，则后面的覆盖前面的
			修改了get_error_message函数的参数，允许用户自定义如果没有找到代码的话，返回的默认字符串
*/
class class_api_error{
	public $error_array;
	var $api_object;
	public $lang;
	var $lang_path;
	var $common_lang_path;
	var $action;
	/**
	 * 说明：返用户提示信息。实现多国语言
	 *     错误代码在languages目录下语言代码下的common和相应action名称的xml文件中，如果语言不存在则使用英文
	 * */
	function __construct($config,$api_obj) {
		$this->error_array = array();
		$this->api_object = $api_obj;
		$this->api_object->write_trace(10,array_to_string("\r\n",$config));
		if(isset($config['lang']))
			$this->lang = strtolower($config['lang']);
		else
			$this->lang = 'zh-cn';
		if(isset($config['lang-path']))
			$this->lang_path = $config['lang-path'];
		else
			$this->lang_path = sprintf("%s",dirname(dirname(__FILE__)));
		if(isset($config['action']))
			$this->action = $config['action'];
		else
			$this->action = '';
		if(isset($config['common-lang-path']))
			$this->common_lang_path = $config['common-lang-path'];
		else
			$this->common_lang_path = '';
	
		//echo $this->common_lang_path;
		$this->load_common_error();
		if(isset($this->action) and $this->action != ''){
			$this->load_action_error();
		}
		//var_dump($this->error_array);
	}
	
	/*
		调入公共的错误代码
	*/
	public function load_common_error(){
		//下面调入公共错误代码
		//echo $this->common_lang_path;
		if($this->lang_path != ''){
			$file = $this->lang_path."/languages/".$this->lang."/common.xml";
			//echo $file."<br>";
			if(!file_exists($file))
			{
				//没有发现语言文件，默认使用英文
				$file = $this->lang_path."/languages/en-US/common.xml";
				//echo $file."<br>";
				if(file_exists($file))
				{
					$this->get_from_xml($file);
				}
			}else{
				$this->get_from_xml($file);
			}
		}
	
		//在公共路径里添加语言
		if($this->common_lang_path != '')
		{
			$file = $this->common_lang_path."/languages/".$this->lang."/common.xml";
			//echo $file."<br>";
			if(!file_exists($file))
			{
				//没有发现语言文件，默认使用英文
				$file = $this->common_lang_path."/languages/en-US/common.xml";
				//echo $file."<br>";
				if(file_exists($file))
				{
					$this->get_from_xml($file);
				}
			}else{
				$this->get_from_xml($file);
			}
		}
	}
	/*
		调入action对应的错误代码
	*/
	public function load_action_error($action = ''){
		if(isset($action) and $action != '')
			$this->action = $action;
		if(isset($this->action) and $this->action != ''){
			//调入action错误代码
			$file = $this->lang_path."/languages/".$this->lang."/".$this->action.".xml";
			//echo $file."<br>";
			if(!file_exists($file))
			{
				//没有发现语言文件，默认使用英文
				$file = $this->lang_path."/languages/en-us/".$this->action.".xml";
				//echo $file."<br>";
				if(file_exists($file))
				{
					//默认英文语言文件不存在，返回
					$this->get_from_xml($file);
				}
			}else{
				$this->get_from_xml($file);
			}
		}
	}
	
	function get_xml($file,$use_prefix=false){
		$file = sprintf("%s/languages/%s/%s",$this->lang_path,strtolower($this->lang),basename($file));
		$this->api_object->write_trace(10,sprintf("try load xml file %s ",$file));
		if(file_exists($file)){
			//打开当前项目语言文件
			$this->get_from_xml($file,$use_prefix);
		}else{
			//没有发现语言文件，默认使用英文
			$file = sprintf("%s/languages/%s/%s",$this->common_lang_path,strtolower($this->lang),basename($file));
			$this->api_object->write_trace(10,sprintf("try load xml file %s ",$file));
			if(file_exists($file)){
				//打开公共的语言文件
				$this->get_from_xml($file,$use_prefix);
			}else{
				$file = sprintf("%s/languages/en-us/%s",$this->lang_path,basename($file));
				$this->api_object->write_trace(10,sprintf("try load xml file %s ",$file));	
				if(file_exists($file))
				{
					//打开项目的默认语言文件
					$this->get_from_xml($file,$use_prefix);
				}else{
					$file = sprintf("%s/languages/en-us/%s",$this->common_lang_path,basename($file));
					$this->api_object->write_trace(10,sprintf("try load xml file %s ",$file));
					if(file_exists($file))
						$this->get_from_xml($file,$use_prefix);	//打开公共默认语言文件
				}
			}
		}
	}
	
	/*
		从文件中追加错误信息对照表
	*/
	public function get_from_xml($file,$use_prefix=false){
		if(!file_exists($file)){
			$this->get_xml($file,$use_prefix);
			return;
		}
		//echo $file."<br>";
		$fp = @fopen($file,'r') or 
			$this->api_object->write_trace(10,sprintf("Can not load file %s",$file));
		$data = fread($fp, filesize($file));//读XML
		
		$this->api_object->write_trace(10,sprintf("Load xml file from %s\r\n%s",$file,$data));
		$xml2a = new api_XMLToArray(); //初始化类，将XML转化成array
		$root_node = $xml2a->parse($data);
		$drive = array_shift($root_node["_ELEMENTS"]);
		
		for ($i= 0; $i< count($drive["_ELEMENTS"]); $i++) 
		{
			$action_array = $drive['_ELEMENTS'][$i];
			//var_dump($action_array);
			//$array_new = array(
			//  $action_array['value'] => $action_array['message'],
			//);
			//$this->error_array = $this->error_array + $array_new;
			//如果存在代码定义则更新之前的代码定义，如果没有则添加新的代码定义
			if($use_prefix)
				$key = sprintf("%s_%s",$root_node['_NAME'],$action_array['value']);
			else 
				$key = $action_array['value'];
			$this->error_array[$key] = $action_array['message'];
		}//根据相应的action获取该action的用户提示信息
	}
	/*
		通过错误代码获得错误文字的函数
		$default 如果找不到就使用这个字符串作为默认的字符串 
	*/
	function get_message($error_code,$default='')
	{
		$msg = '';
		if(isset($this->error_array["$error_code"]))
			$msg = $this->error_array["$error_code"];
		if($error_code <= 0){
			if((!isset($msg) || empty($msg))){
				//没有找到错误代码对应的错误信息
				if($default == '')
					$msg = "Your request have some error.Code is [%d].";
				else
					$msg = $default;
			}
			//$msg = sprintf($msg,$error_code);
		}else{
			if((!isset($msg) || empty($msg))){
				$msg = $default;
			}else{
				//$msg = sprintf($msg,$error_code);
			}
		}
		return $msg;
	}
}

class api_XMLToArray
{

	//-----------------------------------------
	// private variables
	var $parser;
	var $node_stack = array();

	//-----------------------------------------
	/** PUBLIC
	* If a string is passed in, parse it right away.
	*/
	function XMLToArray($xmlstring="") {
		if ($xmlstring) return($this->parse($xmlstring));
		return(true);
	}

	//-----------------------------------------
	/** PUBLIC
	* Parse a text string containing valid XML into a multidimensional array
	* located at rootnode.
	*/

	function parse($xmlstring="") {
		// set up a new XML parser to do all the work for us
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($this->parser, "startElement", "endElement");
		//xml_set_character_data_handler($this->parser, "characterData");
		
		// Build a Root node and initialize the node_stack...
		$this->node_stack = array();
		$this->startElement(null, "root", array());
		
		// parse the data and free the parser...
		xml_parse($this->parser, $xmlstring);
		xml_parser_free($this->parser);
		
		// recover the root node from the node stack
		$rnode = array_pop($this->node_stack);
		
		// return the root node...
		return($rnode);
	}
	
	//-----------------------------------------
	/** PROTECTED
	* Start a new Element. This means we push the new element onto the stack
	* and reset it's properties.
	*/
	function startElement($parser, $name, $attrs)
	{
	
		// create a new node...
		$node = array();
		$node["_NAME"] = $name;
		foreach ($attrs as $key => $value) {
			$node[$key] = $value;
		}
		
		$node["_ELEMENTS"] = array();
		
		// add the new node to the end of the node stack
		array_push($this->node_stack, $node);
	}
	
	//-----------------------------------------
	/** PROTECTED
	* End an element. This is done by popping the last element from the
	* stack and adding it to the previous element on the stack.
	*/
	function endElement($parser, $name) {
		// pop this element off the node stack
		$node = array_pop($this->node_stack);
		
		// and add it an an element of the last node in the stack...
		$lastnode = count($this->node_stack);
		array_push($this->node_stack[$lastnode-1]["_ELEMENTS"], $node);
	}
} 

?>