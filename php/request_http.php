<?php
/* 
 * Compiled by Eugene Mirotin [http://blog.mirotin.net]
 * based on http://wezfurlong.org/blog/2006/nov/http-post-from-php-without-curl
 * and http://nadeausoftware.com/articles/2007/07/php_tip_how_get_web_page_using_fopen_wrappers
 */

function request_http($url, $params = null, $verb = 'GET')
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
      $cparams['http']['header'] = "Content-type: application/x-www-form-urlencoded\r\n";
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
  
  if ( $page !== false )
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
?>