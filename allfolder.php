<?php

/**
 * Allfolder (allfolder)
 *
 * Based on Mark As Junk sample plugin.
 *
 * @version 2.9 - 16.09.2013
 * @author Andre Rodier, Thomas Bruederli, Roland 'rosali' Liebl
 * @website http://myroundcube.googlecode.com 
 */
 
/**
 *
 * Usage: http://mail4us.net/myroundcube/
 *
 **/
  
class allfolder extends rcube_plugin
{
  public $task = 'mail|settings';
  
  private $done = false;
  
  /* unified plugin properties */
  static private $plugin = 'allfolder';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = '<a href="http://myroundcube.com/myroundcube-plugins/allfolder-plugin" target="_new">Documentation</a>';
  static private $download = 'http://myroundcube.googlecode.com';
  static private $version = '1.0';
  static private $date = '10-25-2013';
  static private $licence = 'GPL';
  static private $requirements = array(
    'Roundcube' => '0.9',
    'PHP' => '5.2.1',
  );
  static private $prefs = array('all_mbox');
  static private $config_dist = 'config.inc.php.dist';

  function init()
  {
    $rcmail = rcmail::get_instance();
    if(!in_array('global_config', $rcmail->config->get('plugins'))){
      $this->load_config();
    }

    $this->add_texts('localization/');
    $this->register_action('plugin.allmail', array($this, 'request_action'));

    if ($rcmail->task == 'mail' && ($rcmail->action == '' || $rcmail->action == 'show')
        && ($allmail_folder = $rcmail->config->get('all_mbox'))) 
    {
        
        $skin_path = $this->local_skin_path();
        $this->add_hook('render_mailboxlist', array($this, 'render_mailboxlist'));

        // set env variable for client
        $rcmail->output->set_env('all_folder', $allmail_folder);
        $rcmail->output->set_env('all_folder_icon', $this->url($skin_path.'/foldericon.png'));
          
        $this->include_stylesheet($skin_path . '/allfolder.css');


    }
    else if ($rcmail->task == 'settings') {
      $dont_override = $rcmail->config->get('dont_override', array());
      if (!in_array('all_mbox', $dont_override)) {
        $this->add_hook('preferences_sections_list', array($this, 'allfoldersection'));
        $this->add_hook('preferences_list', array($this, 'prefs_table'));
        $this->add_hook('preferences_save', array($this, 'save_prefs'));
      }
    }
  }
  
  static public function about($keys = false)
  {
    $requirements = self::$requirements;
    foreach(array('required_', 'recommended_') as $prefix){
      if(is_array($requirements[$prefix.'plugins'])){
        foreach($requirements[$prefix.'plugins'] as $plugin => $method){
          if(class_exists($plugin) && method_exists($plugin, 'about')){
            /* PHP 5.2.x workaround for $plugin::about() */
            $class = new $plugin(false);
            $requirements[$prefix.'plugins'][$plugin] = array(
              'method' => $method,
              'plugin' => $class->about($keys),
            );
          }
          else{
             $requirements[$prefix.'plugins'][$plugin] = array(
               'method' => $method,
               'plugin' => $plugin,
             );
          }
        }
      }
    }
    $rcmail_config = array();
    if(is_string(self::$config_dist)){
      if(is_file($file = INSTALL_PATH . 'plugins/' . self::$plugin . '/' . self::$config_dist))
        include $file;
      else
        write_log('errors', self::$plugin . ': ' . self::$config_dist . ' is missing!');
    }
    $ret = array(
      'plugin' => self::$plugin,
      'version' => self::$version,
      'date' => self::$date,
      'author' => self::$author,
      'comments' => self::$authors_comments,
      'licence' => self::$licence,
      'download' => self::$download,
      'requirements' => $requirements,
    );
    if(is_array(self::$prefs))
      $ret['config'] = array_merge($rcmail_config, array_flip(self::$prefs));
    else
      $ret['config'] = $rcmail_config;
    if(is_array($keys)){
      $return = array('plugin' => self::$plugin);
      foreach($keys as $key){
        $return[$key] = $ret[$key];
      }
      return $return;
    }
    else{
      return $ret;
    }
  }
  
  function allfoldersection($args)
  {
    $skin = rcmail::get_instance()->config->get('skin');
    if($skin != 'larry'){
      $this->add_texts('localization');  
      $args['list']['folderslink']['id'] = 'folderslink';
      $args['list']['folderslink']['section'] = $this->gettext('allfolder.folders');
    }
    return $args;
  }

  function render_mailboxlist($p)
  {

    if($this->done){
      return $p;
    }
    
    $this->done = true;
    
    $this->include_script('allfolder.js');

    $rcmail = rcmail::get_instance();
    if ($rcmail->task == 'mail' && ($rcmail->action == '' || $rcmail->action == 'show') && ($all_folder = $rcmail->config->get('all_mbox', false))) {   

      // add all folder to the list of default mailboxes
      if (($default_folders = $rcmail->config->get('default_folders')) && !in_array($all_folder, $default_folders)) {
        $default_folders[] = $all_folder;
        $rcmail->config->set('default_folders', $default_folders);
      }
      
    }

    // set localized name for the configured all folder  
    if ($all_folder) {  
        //print_r($p['list']);
        //exit();
      if (isset($p['list'][$all_folder]))  
      {
        $p['list'][$all_folder]['name'] = $this->gettext('allfolder.allfolder');  
      }
      else // search in subfolders  
      {
        $this->_mod_folder_name($p['list'], $all_folder, $this->gettext('allfolder.allfolder'));  
      }
    }  
    return $p;
  }
  
  function _mod_folder_name(&$list, $folder, $new_name)  
  {  
    foreach ($list as $idx => $item) {  
      if ($item['id'] == $folder) {  
        $list[$idx]['name'] = $new_name;  
        return true;  
      } else if (!empty($item['folders']))  
        if ($this->_mod_folder_name($list[$idx]['folders'], $folder, $new_name))  
          return true;  
    }  
    return false;  
  }  

  function request_action()
  {
    $this->add_texts('localization');
    $uids = get_input_value('_uid', RCUBE_INPUT_POST);
    $mbox = get_input_value('_mbox', RCUBE_INPUT_POST);
    $rcmail = rcmail::get_instance();
    
    if (($all_mbox = $rcmail->config->get('all_mbox')) && $mbox != $all_mbox) {
      $rcmail->output->command('move_messages', $all_mbox);
    }
    
    $rcmail->output->send();
  }

  function prefs_table($args)
  {
    if ($args['section'] == 'folders') {
      $this->add_texts('localization');
      
      $rcmail = rcmail::get_instance();
      $select = rcmail_mailbox_select(array('noselection' => '---', 'realnames' => true, 'maxlength' => 30));
      
      $args['blocks']['main']['options']['all_mbox']['title'] = Q($this->gettext('allfolder'));
      $args['blocks']['main']['options']['all_mbox']['content'] = $select->show($rcmail->config->get('all_mbox'), array('name' => "_all_mbox"));
    }
    if ($args['section'] == 'folderslink') {
      $args['blocks']['main']['options']['folderslink']['title']    = $this->gettext('folders') . " ::: " . $_SESSION['username'];
      $args['blocks']['main']['options']['folderslink']['content']  = "<script type='text/javascript'>\n";
      $args['blocks']['main']['options']['folderslink']['content'] .= "  parent.location.href='./?_task=settings&_action=folders'\n";
      $args['blocks']['main']['options']['folderslink']['content'] .= "</script>\n";
    }
    return $args;
  }

  function save_prefs($args)
  {
    if ($args['section'] == 'folders') {  
      $args['prefs']['all_mbox'] = get_input_value('_all_mbox', RCUBE_INPUT_POST);  
      return $args;  
    }
  }

}
?>
