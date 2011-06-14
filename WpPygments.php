<?php
/* 
Plugin Name: WpPygments
Plugin URI: http://blog.mirotin.net
Version: 0.1.  
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


if (!class_exists("WpPygments")) {
  class WpPygments {
    function WpPygments() { //constructor
      $this->styles = array('autumn', 'borland', 'bw', 'colorful', 'default', 
                            'emacs', 'friendly', 'fruity', 'manni', 'monokai', 
                            'murphy', 'native', 'pastie', 'perldoc', 'tango',
                            'trac', 'vim', 'vs');
    }
    
    function init() {
      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_script('wppygments.zeroclipboard', get_bloginfo('wpurl') . '/wp-content/plugins/WpPygments/js/ZeroClipboard.js', array(), '1.0.7');
      wp_enqueue_script('wppygments.printarea', get_bloginfo('wpurl') . '/wp-content/plugins/WpPygments/js/jquery.PrintArea.js', array('jquery'), '1.0.7');
      wp_enqueue_script('wppygments.tools', get_bloginfo('wpurl') . '/wp-content/plugins/WpPygments/js/pygments-tools.js.php', 
        array('jquery', 'wppygments.zeroclipboard', 'wppygments.printarea'), '0.3');
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
      
      $code = $el->text();
      $res = _callPygmentizeService(SERVICE_URL, $code, $lang);
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
      if(is_admin())
        return $comment;
      return $this->_processContent($comment, true);
    }

    function preSaveContent($content = '') {
      return $this->_processContent($content, false);
    }

    function preSaveComment($comment = '') {
      return $this->_processContent($comment, false);
    }

  } //End Class WpPygments
}

if (class_exists("WpPygments")) {
  $wp_pygments = new WpPygments();
}

//Actions and Filters 
if (isset($wp_pygments)) {
  //Actions
  add_action('wp_head', array(&$wp_pygments, 'processHeader'), 1);
  add_action('init',  array(&$wp_pygments, 'init'));
  
  //Filters
  add_filter('content_filtered_save_pre', array(&$wp_pygments, 'preSaveContent'), 1); 
  add_filter('pre_comment_content', array(&$wp_pygments, 'preSaveComment'), 1); 
    
  add_filter('the_content', array(&$wp_pygments, 'preShowContent'), 1); 
  add_filter('comment_text', array(&$wp_pygments, 'preShowComment'), 1);
     
}
?>