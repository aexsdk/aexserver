<?php

	function print_QoDesk($billing){
		Header("content-type: application/x-javascript");
?>
	<?php 
		$lang = $billing->get_lang();//get group id by session id
		$file = $billing->config->OS_ROOT_DIR."system/modules/language/$lang.xml";
		//返回模块的多国语言
		if (!file_exists($file)){
			$lang = 'en-us';//'zh-cn';		//default use english
			$file = $billing->config->OS_ROOT_DIR."system/modules/language/$lang.xml";
		}
		//print sprintf("/*(lang=%s) %s */\r\n",$lang,$file);
		if(file_exists($file)){
			print $billing->get_lang_from_xml($file);
		}	
		//$billing->module->load_all();	

	class EzDesk {
	   private $os = null;
	
	   private $member_id = null;
	   private $group_id = null;
	   public $member_info = null;
	   private $preferences = null;
	
	   /**
	    * __construct()
	    *
	    * @access public
	    * @param {class} $os The os.
	    */
	   public function __construct($os){	
	      $this->member_id = $os->session->get_member_id();
	      $this->group_id = $os->session->get_group_id();
	
	      if(!isset($this->member_id, $this->group_id)){
	         $os->write_error('Member/Group not found!');
	      }
	
	      $this->os = $os;
	      $this->member_info = $this->get_member_info();
	      $this->preferences = $this->get_preferences();
	   } // end __construct()
	
	   private function get_member_info(){
	      //$this->os->load('member');
	      $name = $this->os->sessions['user'];//$this->os->member->get_name($this->member_id);
	      //$this->os->load('group');
	      $group = $this->os->session->get_group_id();//$this->os->group->get_name($this->group_id);
			
	      $member_info = new stdClass();
	      $member_info->name = isset($name) ? $name : '';
	      $member_info->group = isset($group) ? $group : '';
	
	      return $member_info;
	   }
	
	   private function get_preferences(){
	      $member_id = $this->member_id;
	      $group_id = $this->group_id;
	      
	      $default_preference = '{"appearance":{"fontColor": "333333","themeId":3,"taskbarTransparency":"100"},"background":{"color": "f9f9f9","wallpaperId":4,"wallpaperPosition":"center"},"launchers":{"autorun":["qo-preferences"],"quickstart": [],"shortcut":["qo-preferences"]}}';
	      $default_theme = '{"about": {   "author": "Ext JS",   "version": "1.0",   "url": "www.chinautone.com"},"group": "Ext JS","name": "Gray","thumbnail": "images/xtheme-gray.gif","file": "css/xtheme-gray.css"\r\n}';
	      $default_wallpaper = '{"group": "Pattern","name": "Blue Swirl","thumbnail": "thumbnails/blue-swirl.jpg","file": "blue-swirl.jpg"\r\n}';
			
	      // get the member/group preference
	      $member_preference = $this->os->theme->get_appearance($group_id,$member_id);//$this->os->get_member_preference($member_id, $group_id);
	      // get the default preference
	      $preference = json_decode($default_preference);//$this->os->get_member_preference('0', '0');
	
	      // overwrite default with any member/group preference
	      foreach($member_preference as $id => $property){
	         $preference->$id = $property;
	      }
	      
	      //if(!isset($preference->appearance->launchers))
	      //	 $preference->appearance->launchers = json_decode($this->os->launcher->get_all());
	
	      // do we have the theme id
	      if(isset($preference->appearance->themeId)){
	         //$this->os->load('theme');
	         $theme = $this->os->theme->get_theme($preference->appearance->themeId);
	         $theme_dir = $this->os->get_theme_dir();
	
	         $preference->appearance->theme = new stdClass();
	         $preference->appearance->theme->id = $preference->appearance->themeId;
	         $preference->appearance->theme->name = $theme->name;
	         // local file?
	         if(stripos($theme->file, 'http://') === false){
	            $preference->appearance->theme->file = $theme_dir.$theme->file;
	         }else{
	            $preference->appearance->theme->file = $theme->file;
	         }
	
	         unset($preference->appearance->themeId);
	      }
	
	      // do we have the wallpaper id
	      if(isset($preference->background->wallpaperId)){
	         //$this->os->load('wallpaper');
	         $wallpaper = $this->os->theme->get_wallpaper($preference->background->wallpaperId);
	         $wallpaper_dir = $this->os->get_wallpaper_dir();
	
	         $preference->background->wallpaper = new stdClass();
	         $preference->background->wallpaper->id = $preference->background->wallpaperId;
	         $preference->background->wallpaper->name = $wallpaper->name;
	         $preference->background->wallpaper->file = $wallpaper_dir.$wallpaper->file;
	
	         unset($preference->background->wallpaperId);
	      }
			
	      return $preference;
	   }
	
	   public function print_privileges(){
	      print $this->os->privilege->get_all();
	      return true;
	   }
	
	   public function print_modules(){
	      //print $this->os->module->get_modules();//get_all();
	      $response = '';
	      $ms = $this->os->module->get_modules();
	
	      if(!isset($ms) || !is_array($ms) || count($ms) == 0){
	         print '';
	         return false;
	      }

	      foreach($ms as $id => $m){
	      	//print sprintf("/*%s ===> %s*/",$id,json_encode($m));
	         $response .= '{'.
	            '"id":"'.$id.'",'.
	            '"type":"'.$m->type.'",'.
	            '"className":"'.$m->client->class.'",'.
	            '"launcher":'.json_encode($m->client->launcher->config).','.
	            '"launcherPaths":'.json_encode($m->client->launcher->paths).
	         '},';
	      }
	
	      print rtrim($response, ',');
	   }
	
	   public function print_launchers(){
	      print $this->os->launcher->get_all();;
	   }
	
	   public function print_appearance(){
	      print isset($this->preferences->appearance) ? json_encode($this->preferences->appearance) : "{}";
	   }
	
	   public function print_background(){
	      print isset($this->preferences->background) ? json_encode($this->preferences->background) : "{}";
	   }
	   public function print_styles(){
			$this->os->preference->get_styles();
	   }
	}

	$ez_desk = new EzDesk($billing);
?>
/*
 */

Ext.namespace('Ext.ux','EzDesk');

EzDesk.App = new Ext.app.App({
   init : function(){
      Ext.BLANK_IMAGE_URL = extjs_blank_url;
      Ext.QuickTips.init();
   },

   /**
    * The member's name and group name for this session.
    */
   memberInfo: {
      name: '<?php print $ez_desk->member_info->name ?>',
      group: '<?php print $ez_desk->member_info->group ?>'
   },

   /**
    * An array of the module definitions.
    * The definitions are used until the module is loaded on demand.
    */
   modules: [ <?php $ez_desk->print_modules(); ?> ],

   /**
     * The members privileges.
     */
   privileges: <?php $ez_desk->print_privileges(); ?>,

   /**
    * The desktop config object.
    */
   desktopConfig: {
      appearance: <?php $ez_desk->print_appearance(); ?>,
      background: <?php $ez_desk->print_background(); ?>,
      launchers: <?php $ez_desk->print_launchers(); ?>,
      taskbarConfig: {
         buttonScale: 'large',
         position: 'bottom',
         quickstartConfig: {
            width: 120,
            heigh:400
         },
         startButtonConfig: {
            iconCls: 'icon-qwikioffice',
            text: 'Start'
         },
         startMenuConfig: {
            iconCls: 'icon-user-48',
            title: '<?php print $ez_desk->member_info->name ?>',
            width: 320
         }
      }
   }
});

<?php 
	}
?>
