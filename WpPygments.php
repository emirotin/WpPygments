<?php
/* 
Plugin Name: WpPygments
Plugin URI: http://blog.mirotin.net
Version: 0.1.  
Author: <a href="http://blog.mirotin.net">Eugene Mirotin</a> 
Description: Colorizes code in WP posts with Pygments.
*/

$SERVICE_URL = 'http://pygmentizer.appspot.com/';

require_once('php/phpQuery.php');

// based on http://wezfurlong.org/blog/2006/nov/http-post-from-php-without-curl
// and http://nadeausoftware.com/articles/2007/07/php_tip_how_get_web_page_using_fopen_wrappers
function http_request($url, $params = null, $verb = 'GET')
{
  $cparams = array(
    'http' => array(
      'method' => $verb,
      'ignore_errors' => true
    )
  );
  if ($params !== null) {
    $params = http_build_query($params);
    if ($verb == 'POST') {
      $cparams['http']['content'] = $params;
    } else {
      $url .= '?' . $params;
    }
  }
  
  $result = array();

  $context = stream_context_create($cparams);
  $fp = fopen($url, 'rb', false, $context);
  if (!$fp) {
    return null;
  } else {
    $page = stream_get_contents($fp);
  }
  
  //$page    = @file_get_contents( $url, false, $context );

  if ( $page != false )
    $result['content'] = $page;
  else if ( !isset( $http_response_header ) )
    return null;    // Bad url, timeout

  // Save the header
  $result['header'] = $http_response_header;

  // Get the *last* HTTP status code
  $nLines = count( $http_response_header );
  for ( $i = $nLines-1; $i >= 0; $i-- )
  {
    $line = $http_response_header[$i];
    if ( strncasecmp( "HTTP", $line, 4 ) == 0 )
    {
      $response = explode( ' ', $line );
      $result['status'] = $response[1];
      break;
    }
  }
 
  return $result;
}

function _callPygmentizeService($url, $code, $lang = '') {
  return http_request($url, array('lang' => $lang, 'code' => $code), 'POST');
}


if (!class_exists("WpPygments")) {
  class WpPygments {
    function WpPygments() { //constructor
      
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
        
    static function _processCodeNode($el) {
      global $SERVICE_URL;
      $el = pq($el);
      $lang = $el->attr('lang');
      if ($lang === null)
        $lang = '';
      
      $code = $el->text();
      $res = _callPygmentizeService($SERVICE_URL, $code, $lang);
      if ($res !== null && $res['status'] == '200') {  
        $el->parent()->wrap('<div class="highlight-wrapper ' . $lang . '"</div>');
        $wrap = $el->parent()->parent();
        $el->parent()->addClass('raw');
        $wrap->prepend('<div class="tools">' .
          '<a href="#" class="show-raw">raw</a>' .
          '<a href="#" class="show-colored">highlighted</a>'.
          '<a href="#" class="to-clipboard">copy</a>'.
          '<a href="#" class="print">print</a>'.
          '<a href="#" class="about">?</a>'.
        '</div>');
        $wrap->append('<div class="highlighted">' . $res['content'] . '</div>');
      }
    }
    
    function processContent($content = '') {
      $dialog = '<div id="about-dialog">' .
        '<p>WpPygments is a Wordpress plugin that colorizes your code snippets using the web-service created around a popular Pygments library.</p>' .
        '<p>Both the service and the plugin are developed by Eugene Mirotin. See <a href="http://google.com" target="_blank">here</a> how to add it to your blog.</p>' .
      '</div>';
      
      $doc = phpQuery::newDocumentHTML($content);
      $doc->find('pre>code:not(.nocolor)')->each(array('WpPygments', '_processCodeNode'));
      // marking line number as literals for coloring
      $doc->find('.linenodiv pre')->addClass('nl'); 
      return $dialog . $doc->htmlOuter();
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
  add_filter('the_content', array(&$wp_pygments, 'processContent'),1); 
}

?>