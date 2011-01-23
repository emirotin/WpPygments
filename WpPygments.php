<?php
/* 
Plugin Name: WpPygments
Plugin URI: http://blog.mirotin.net
Version: 0.3  
Author: <a href="http://blog.mirotin.net">Eugene Mirotin</a> 
Description: Colorizes code in WP posts with Pygments.
*/

define("SERVICE_URL", 'http://pygmentizer.appspot.com/');

require_once('php/phpQuery.php');
require_once('php/request_http.php');

function _callPygmentizeService($url, $code, $lang = '') {
  $res = request_http($url, array('lang' => $lang, 'code' => $code), 'POST');
  if ($res !== null && $res['status'] == '200')
    return $res['content'];
  return false;
}

global $WpPygments_db_version;
$WpPygments_db_version = "1.1";

function WpPygments_table_name(){
  global $wpdb;
  return $wpdb->prefix . "pygments_cache";
}

function WpPygments_install_db() {
  global $wpdb;
  global $WpPygments_db_version;  
  
  $table_name = WpPygments_table_name();
  if($wpdb->get_var("show tables like '$table_name'") != $table_name)
    add_option("WpPygments_db_version", "0");
  $installed_ver = get_option( "WpPygments_db_version" );

  if ($installed_ver != $WpPygments_db_version)  
  {      
    $sql = "CREATE TABLE " . $table_name . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      lang VARCHAR(55) NOT NULL,
      code_hash VARCHAR(40) NOT NULL,
      pygments text NOT NULL,
      last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    update_option("WpPygments_db_version", $WpPygments_db_version);
  }
}
                              
if (!class_exists("WpPygments")) {
  class WpPygments {
    function WpPygments() { //constructor
      $this->styles = array('autumn', 'borland', 'bw', 'colorful', 'default', 
                                  'emacs', 'friendly', 'fruity', 'manni', 'monokai', 
                                  'murphy', 'native', 'pastie', 'perldoc', 'tango',
                                  'trac', 'vim', 'vs');
                                  
      $this->cache_ttl = array(
                               'never clean' => null,
                               '6 months'    => '6 MONTH',
                               '3 months'    => '2 MONTH',
                               '30 days'     => '30 DAY',
                               '15 days'     => '15 DAY',
                               '10 days'     => '10 DAY',
                               '5 days'      => '5 DAY',
                               '2 days'      => '2 DAY',
                               '1 day'       => '1 DAY'
                              );
    }
    
    function init() {
      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_script('wppygments.zeroclipboard', get_bloginfo('wpurl') . '/wp-content/plugins/WpPygments/js/ZeroClipboard.js', 
        array(), '1.0.7');
      wp_enqueue_script('wppygments.printarea', get_bloginfo('wpurl') . '/wp-content/plugins/WpPygments/js/jquery.PrintArea.js', 
        array('jquery'), '1.0.7');
      wp_enqueue_script('wppygments.tools', get_bloginfo('wpurl') . '/wp-content/plugins/WpPygments/js/pygments-tools.js.php', 
        array('jquery', 'wppygments.zeroclipboard', 'wppygments.printarea'), '0.4');
    }
   
    
    function processHeader() {
      $style_name = 'emacs';

      echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . 
        '/wp-content/plugins/WpPygments/css/jquery-ui-1.8.8.custom.css" />' . "\n";
      echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . 
        '/wp-content/plugins/WpPygments/css/_common.css" />' . "\n";
      echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . 
        '/wp-content/plugins/WpPygments/css/pygments/' . $style_name . '.css" />' . "\n";
    }

    static function _getPygmentizedCode($el) {
      $el = pq($el);
      $lang = $el->attr('lang');
      if ($lang === null)
        $lang = '';
      $lang = str_replace('\"', '', $lang);
      
      $code = $el->text();
      
      $hash = sha1($code);
      $table_name = WpPygments_table_name();
      global $wpdb;
      $res = $wpdb->get_row($wpdb->prepare('SELECT id, pygments from ' . $table_name . ' WHERE lang=%s AND code_hash=%s', $lang, $hash));
      if ($res !== null) {
        // force timestamp update
        $wpdb->get_var('UPDATE ' . $table_name . ' SET `last_accessed` = NULL WHERE `id` = ' . $res->id);
        $res = $res->pygments;
      }
      else {
        $res = _callPygmentizeService(SERVICE_URL, $code, $lang);
        $wpdb->insert($table_name, array('lang' => $lang, 'code_hash' => $hash, 'pygments' => $res));  
      }
      return array('res' => $res, 'lang' => $lang);      
    }

    static function _processCodeNode($el) {
      $res = self::_getPygmentizedCode($el);
      
      if ($res['res'] !== false) {
        $el = pq($el);
        $el->parent()->wrap('<div class="highlight-wrapper ' . $res['lang'] . '"></div>');
        $wrap = $el->parent()->parent();
        $el->parent()->addClass('raw');
        $wrap->prepend('<div class="tools">' .
          '<a href="#" class="show-raw">raw</a>' .
          '<a href="#" class="show-colored">highlighted</a>'.
          '<a href="#" class="to-clipboard">copy</a>'.
          '<a href="#" class="print">print</a>'.
          '<a href="#" class="about">?</a>'.
        '</div>');
        $wrap->append('<div class="highlighted">' . $res['res'] . '</div>');
      }
    }

    function _processContent($content = '', $alter_content = true) {
      $doc = phpQuery::newDocumentHTML($content);

      if (!$alter_content) {
        $doc->find('pre>code:not(.nocolor)')->each(array('WpPygments', '_getPygmentizedCode'));
        return $content;
      }

      $doc->find('pre>code:not(.nocolor)')->each(array('WpPygments', '_processCodeNode'));
      // marking line number as literals for coloring
      $doc->find('.linenodiv pre')->addClass('nl');
      return $doc->htmlOuter();
    }
    
    function preShowContent($content = '') {
      return $this->_processContent($content, true);
    }

    function preShowComment($comment = '') {
      if (is_admin())
        return $comment;
      return $this->_processContent($comment, true);
    }

    function preSaveContent($content = '') {
      return $this->_processContent($content, false);
    }

    function preSaveComment($comment = '') {
      return $this->_processContent($comment, false);
    }

    /**
     * Based on http://www.coranac.com/2009/08/filter-juggling-and-comment-preview/
     * Pre-encode HTML entities. Should come before wp_kses.
     */
    function filterDeEntity($content)
    {
        $content = preg_replace(
            '#(<pre><code.*?>)(.*?)(</code></pre>)#msie',
            '"\\1" . str_replace(
                array("<", ">", "&"),
                array("[|LT|]", "[|GT|]", "[|AMP|]"),
                \'\\2\') . "\\3";',
            $content);
        $content = str_replace('\"', '"', $content);
        
        return $content;
    }
    /**
     * Decode HTML entities. Should come after wp_kses.
     */
    function filterReEntity($content)
    {
        if(strstr($content, "[|"))
        {
            $content = preg_replace(
                '#(<pre><code.*?>)(.*?)(</code></pre>)#msie',
                '"\\1" . str_replace(
                    array("[|LT|]", "[|GT|]", "[|AMP|]"),
                    array("&lt;", "&gt;", "&amp;"),
                    \'\\2\') . "\\3";',
                $content);
            $content = str_replace('\"', '"', $content);
        }
       
        return $content;
    }

    /*
     *  clean obsolete cache records
     */
    function clean_old_cache() {      
      $ttl = 60; $unit = 'SECOND'; // TODO: read from settings
      $clear_old = true;
      
      if (!$clear_old)
        return;
        
      global $wpdb;
      $table_name = WpPygments_table_name();
      $wpdb->get_var('DELETE FROM `' . $table_name . '` WHERE last_accessed < DATE_SUB(NOW() , INTERVAL ' . $ttl . ' ' . $unit . ')');
    }
    
    function register_cron() {
      wp_schedule_event(time(), 'daily', 'clean_old_cache');
    }
    
    function unregister_cron() {
      wp_clear_scheduled_hook('clean_old_cache');
    }
    
    /*
     * Options
     */
    function add_options_page() {
      add_options_page('WpPygments Options', 'WpPygments', 'manage_options', 'WpPygments', array($this, 'create_options_page'));
    }

    function create_options_page() {
    ?>
    <div class="wrap">
    <h2>WpPygments Settings</h2>
    <form action="options.php" method="post">
    <?php settings_fields('WpPygments_options'); ?>
    <?php do_settings_sections('WpPygments'); ?>
    <p class="submit">
      <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </p>
    </form>
    </div>
    <?php
    }
    
    function validate_options($input) {
      $options = get_option('WpPygments_options');
      
      $style = $input['pygments_style'];
      if (!in_array($style, $this->styles))
        $style = 'default';
      $options['pygments_style'] = $style;
      
      $options['pygments_toolbar_show_raw'] = $input['pygments_toolbar_show_raw'] === 'on' ? 'on' : 'off';
      $options['pygments_toolbar_show_copy'] = $input['pygments_toolbar_show_copy'] === 'on' ? 'on' : 'off';
      $options['pygments_toolbar_show_print'] = $input['pygments_toolbar_show_print'] === 'on' ? 'on' : 'off';
      return $options;
    }
   
    
    function admin_init() {
      register_setting('WpPygments_options', 'WpPygments_options', array($this, 'validate_options'));

      add_settings_section('WpPygments_style', 'Style', array($this, 'options_style_section_desc'), 'WpPygments');
      add_settings_field('pygments_style', 'Style', array($this, 'style_option'), 'WpPygments', 'WpPygments_style');

      add_settings_section('WpPygments_toolbar', 'Toolbar', array($this, 'options_toolbar_section_desc'), 'WpPygments');
      add_settings_field('pygments_toolbar_show_raw', 'Show Raw / Highlighted Switcher', array($this, 'toolbar_raw_option'), 'WpPygments', 'WpPygments_toolbar');
      add_settings_field('pygments_toolbar_show_copy', 'Show Copy Link', array($this, 'toolbar_copy_option'), 'WpPygments', 'WpPygments_toolbar');
      add_settings_field('pygments_toolbar_show_print', 'Show Print Link', array($this, 'toolbar_print_option'), 'WpPygments', 'WpPygments_toolbar');
    }
    
    function options_style_section_desc() {
      echo '<p>Choose on of the styles provided by the <a href="http://pygments.org">Pygments</a> library.';
    }
    function style_option() {
      $options = get_option('WpPygments_options');
      echo "<select id='pygments_style' name='WpPygments_options[pygments_style]'>";
      foreach ($this->styles as $style) {
        echo "<option value='{$style}'";
        if ($style == $options['pygments_style'])
          echo " selected='selected'";
        echo ">{$style}</option>"; 
      }
      echo "</select>";
    }

    function options_toolbar_section_desc() {
      echo '<p>Configure various toolbar links.';
    }
    function toolbar_raw_option() {
      $options = get_option('WpPygments_options');
      echo "<input id='pygments_toolbar_raw' name='WpPygments_options[pygments_toolbar_show_raw]' type='checkbox'";
      if (!isset($options['pygments_toolbar_show_raw']) || $options['pygments_toolbar_show_raw'] === 'on')
        echo " checked='checked'";
      echo " />";
    }
    function toolbar_copy_option() {
      $options = get_option('WpPygments_options');
      echo "<input id='pygments_toolbar_copy' name='WpPygments_options[pygments_toolbar_show_copy]' type='checkbox'";
      if (!isset($options['pygments_toolbar_show_copy']) || $options['pygments_toolbar_show_copy'] === 'on')
        echo " checked='checked'";
      echo " />";
    }
    function toolbar_print_option() {
      $options = get_option('WpPygments_options');
      echo "<input id='pygments_toolbar_print' name='WpPygments_options[pygments_toolbar_show_print]' type='checkbox'";
      if (!isset($options['pygments_toolbar_show_print']) || $options['pygments_toolbar_show_print'] === 'on')
        echo " checked='checked'";
      echo " />";
    }


  } //End Class WpPygments
}

if (class_exists("WpPygments")) {
  $wp_pygments = new WpPygments();
}

//Actions and Filters 
if (isset($wp_pygments)) {
  // Hooks
  register_activation_hook(__FILE__,'WpPygments_install_db');
  register_activation_hook(__FILE__, array(&$wp_pygments, 'register_cron'));
  register_deactivation_hook(__FILE__, array(&$wp_pygments, 'unregister_cron')); 
  
  //Actions
  add_action('wp_head', array(&$wp_pygments, 'processHeader'), 1);
  add_action('init',  array(&$wp_pygments, 'init'));
  add_action('admin_menu', array(&$wp_pygments, 'add_options_page'));
  add_action('admin_init', array(&$wp_pygments, 'admin_init'));
  add_action('clean_old_cache', array(&$wp_pygments, 'clean_old_cache'));
  
  //Filters
  add_filter('content_save_pre', array(&$wp_pygments, 'preSaveContent'), 1); 

  add_filter('pre_comment_content', array(&$wp_pygments, 'filterDeEntity'), 1);
  add_filter('pre_comment_content', array(&$wp_pygments, 'filterReEntity'), 49);
  add_filter('pre_comment_content', array(&$wp_pygments, 'preSaveComment'), 50); 
    
  add_filter('the_content', array(&$wp_pygments, 'preShowContent'), 1); 
  add_filter('comment_text', array(&$wp_pygments, 'preShowComment'), 1);  
}
?>