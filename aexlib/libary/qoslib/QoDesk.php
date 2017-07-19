<?php
//Header("content-type: application/x-javascript");

require_once('server/os.php');
if(!class_exists('os')){ die('os class is missing!'); }

	function print_QoDesk($billing){
		Header("content-type: application/x-javascript");
		$lang = $billing->get_lang();//get group id by session id
		$file = __EZLIB_OS__."/modules/languages/$lang.xml";
		//返回模块的多国语言
		if (!file_exists($file)){
			$lang = 'en-us';//'zh-cn';		//default use english
			$file = __EZLIB_OS__."/modules/languages/$lang.xml";
		}
		//print sprintf("/*(lang=%s) %s */\r\n",$lang,$file);
		if(file_exists($file)){
			print $billing->get_lang_from_xml($file);
		}	
		//$billing->module->load_all();	

class QoDesk {
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
   public function __construct(os $os){
      if(!$os->session_exists()){
         die('Session does not exist!');
      }

      $this->member_id = $os->get_member_id();
      $this->group_id = $os->get_group_id();

      if(!isset($this->member_id, $this->group_id)){
         die('Member/Group not found!');
      }

      $this->os = $os;
      $this->member_info = $this->get_member_info();
      $this->preferences = $this->get_preferences();
   } // end __construct()

   private function get_member_info(){
      $this->os->load('member');
      $name = $this->os->member->get_name($this->member_id);
      $this->os->load('group');
      $group = $this->os->group->get_name($this->group_id);

      $member_info = new stdClass();
      $member_info->name = $name ? $name : '';
      $member_info->group = $group ? $group : '';

      return $member_info;
   }

   private function get_preferences(){
      $member_id = $this->member_id;
      $group_id = $this->group_id;

      // get the member/group preference
      $member_preference = $this->os->get_member_preference($member_id, $group_id);

      // get the default preference
      $preference = $this->os->get_member_preference('0', '0');

      // overwrite default with any member/group preference
      foreach($member_preference as $id => $property){
         $preference->$id = $property;
      }

      // do we have the theme id
      if(isset($preference->appearance->themeId)){
         $this->os->load('theme');
         $theme = $this->os->theme->get_by_id($preference->appearance->themeId);
         $theme_dir = $this->os->get_theme_dir();

         $preference->appearance->theme = new stdClass();
         $preference->appearance->theme->id = $preference->appearance->themeId;
         $preference->appearance->theme->name = $theme->name;
         // local file?
         if(stripos($theme->file, 'http://') === false){
            $preference->appearance->theme->file = path_to_url($theme_dir.$theme->file);
         }else{
            $preference->appearance->theme->file = $theme->file;
         }

         unset($preference->appearance->themeId);
      }

      // do we have the wallpaper id
      if(isset($preference->background->wallpaperId)){
         $this->os->load('wallpaper');
         $wallpaper = $this->os->wallpaper->get_by_id($preference->background->wallpaperId);
         $wallpaper_dir = $this->os->get_wallpaper_dir();

         $preference->background->wallpaper = new stdClass();
         $preference->background->wallpaper->id = $preference->background->wallpaperId;
         $preference->background->wallpaper->name = $wallpaper->name;
         $preference->background->wallpaper->file = path_to_url($wallpaper_dir.$wallpaper->file);

         unset($preference->background->wallpaperId);
      }

      return $preference;
   }

   public function print_privileges(){
      // have a group id?
      if(!isset($this->group_id)){
         print '{}';
         return false;
      }

      // get the privilege id for the group
      $this->os->load('group');
      $privilege_id = $this->os->group->get_privilege_id($this->group_id);

      if(!$privilege_id){
         print '{}';
         return false;
      }

      // get the simplified privilege data
      $this->os->load('privilege');
      $data = array();
      //echo sprintf("/*pid=%s*/",json_encode($privilege_id));
      foreach ($privilege_id as $p){
		$data += $this->os->privilege->simplify($p['id']);
		//echo sprintf("/*data = %s*/",json_encode($data));
		if(!isset($data)){
		   print '{}';
		   return false;
		}
		
      }
	  print json_encode($data);
      return true;
   }

   public function print_modules(){
      $response = '';
      $ms = $this->os->get_modules();

      if(!isset($ms) || !is_array($ms) || count($ms) == 0){
         print '';
         return false;
      }

      foreach($ms as $id => $m){
      	 $m->client->launcher->config->text = sprintf("lang_module.mc_%s",to_regulate_action($id));
      	 $m->client->launcher->config->tooltip = sprintf("lang_module.mt_%s",to_regulate_action($id));
         $response .= '{'.
            '"id":"'.$id.'",'.
            '"type":"'.$m->type.'",'.
            '"className":"'.$m->client->class.'",'.
            '"launcher":{'.
	            '"iconCls": "'.$m->client->launcher->config->iconCls.'",'.
	            '"shortcutIconCls": "'.$m->client->launcher->config->shortcutIconCls.'",'.
	            '"text": '.$m->client->launcher->config->text.','.
	            '"tooltip": '.$m->client->launcher->config->tooltip.
	         '},'.
            '"launcherPaths":'.json_encode($m->client->launcher->paths).
         '},';
      }

      print rtrim($response, ',');
   }

   public function print_launchers(){
      print isset($this->preferences->launchers) ? json_encode($this->preferences->launchers) : "{}";
   }

   public function print_appearance(){
      print isset($this->preferences->appearance) ? json_encode($this->preferences->appearance) : "{}";
   }

   public function print_background(){
      print isset($this->preferences->background) ? json_encode($this->preferences->background) : "{}";
   }
}

//$os = new os();
$qo_desk = new QoDesk($billing);
?>
/*
 */

Ext.namespace('Ext.ux','QoDesk');

QoDesk.App = new Ext.app.App({
   init : function(){
      Ext.BLANK_IMAGE_URL = extjs_blank_url;
      Ext.QuickTips.init();
   },

   /**
    * The member's name and group name for this session.
    */
   memberInfo: {
      name: '<?php print $qo_desk->member_info->name ?>',
      group: '<?php print $qo_desk->member_info->group ?>'
   },

   /**
    * An array of the module definitions.
    * The definitions are used until the module is loaded on demand.
    */
   modules: [ <?php $qo_desk->print_modules(); ?> ],

   /**
     * The members privileges.
     */
   privileges: <?php $qo_desk->print_privileges(); ?>,

   /**
    * The desktop config object.
    */
   desktopConfig: {
      appearance: <?php $qo_desk->print_appearance(); ?>,
      background: <?php $qo_desk->print_background(); ?>,
      launchers: <?php $qo_desk->print_launchers(); ?>,
      taskbarConfig: {
         buttonScale: 'large',
         position: 'bottom',
         quickstartConfig: {
            width: 120
         },
         startButtonConfig: {
            iconCls: 'icon-qwikioffice',
            text: 'Start'
         },
         startMenuConfig: {
            iconCls: 'icon-user-48',
            title: '<?php print $qo_desk->member_info->name ?>',
            width: 320
         }
      }
   }
});
<?php
	} 
?>