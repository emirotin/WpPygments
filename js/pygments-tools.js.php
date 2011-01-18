<?php
if (!function_exists('add_action')) {
  require_once('../../../../wp-config.php');
}
?>

(function($) {
	$(function(){
    // toggle raw/ highlighted views	
	  $('.highlighted').css('display', 'block');
		$('.tools .show-raw, .tools .show-colored').click(function(){
			var el = $(this);
			var tools = el.parent();
			var wrap = tools.parent();
			wrap.children('.highlighted').slideToggle();
      wrap.children('.raw').slideToggle();
			tools.children('.show-colored').toggle();
      tools.children('.show-raw').toggle();
			
			return false;
		})
    
		// clipboard
    var zeroUrl =  "<?php bloginfo('wpurl') ?>/wp-content/plugins/WpPygments/js/ZeroClipboard10.swf";
    ZeroClipboard.setMoviePath(zeroUrl);
    var i = 0;
    $('.to-clipboard').each(function(){
      var el = $(this);
      var id = "to-clipboard-" + i;
      el.attr("id", id);
      i++;
      var code = el.parent().parent().find('.raw code');
      var clip = new ZeroClipboard.Client();
      clip.setText('');
      clip.addEventListener('mouseDown', function(){
        clip.setText(code.text());
      });
      clip.glue(id);
    })
    
		// print
    $('.print').click(function(){
      var el = $(this);
      var code = el.parent().parent().find('.highlight');
			code.printArea();
			return false;
    })

    // about
		$('.about').click(function(){
		  var dialog = $('#wppygments-about-dialog');
		  if (!dialog.size()) {
        dialog = $('<div id="wppygments-about-dialog">' +
            '<p>WpPygments is a Wordpress plugin that colorizes your code snippets using the web-service created around a popular Pygments library.</p>' +
            '<p>Both the service and the plugin are developed by Eugene Mirotin. See <a href="http://google.com" target="_blank">here</a> how to add it to your blog.</p>' +
          '</div>');
        dialog.dialog({ autoOpen: false, title: 'WpPygments by Eugene Mirotin' });
      }
			dialog.dialog('open');
			return false;
		})
	})
})(jQuery)
