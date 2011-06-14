<?php
if (!function_exists('add_action')) {
  require_once('../../../../wp-config.php');
}
?>

var ZeroClipboard, jQuery;

(function($) {
	$(function(){
	  $('.tools .show-raw').show();
	  $('.tools .show-colored').hide();
	  
    // toggle raw/ highlighted views	
	  $('.highlighted').css('display', 'block');
		$('.tools .show-raw, .tools .show-colored').click(function() {
			var el = $(this),
			    tools = el.parent().parent(),
			    wrap = tools.parent();
			wrap.children('.highlighted').slideToggle();
      wrap.children('.raw').slideToggle();
			tools.find('.show-colored').toggle();
      tools.find('.show-raw').toggle();
			return false;
		});
    
		// clipboard
    var zeroUrl = "<?php bloginfo('wpurl') ?>/wp-content/plugins/WpPygments/js/ZeroClipboard10.swf",
        i = 0;
    ZeroClipboard.setMoviePath(zeroUrl);
    $('.to-clipboard').each(function() {
      var el = $(this),
          code = el.parent().parent().parent().find('.raw code'),
          clip = new ZeroClipboard.Client();
      el.data("clip", clip);
      i++;
      clip.setText('');
      clip.setHandCursor(true);
      clip.addEventListener('mouseDown', function(){
        clip.setText(code.text());
      });
      clip.glue(this);
    });
    
    $(window).resize(function() {
      $('.to-clipboard').each(function() {
        $(this).data("clip").reposition();
      });
    });
    
		// print
    $('.print').click(function() {
      var el = $(this),
          code = el.parent().parent().parent().find('.highlight');
			code.printArea();
			return false;
    });

    // about
		$('.about').click(function() {
		  var dialog = $('#wppygments-about-dialog');
		  if (!dialog.size()) {
        dialog = $('<div id="wppygments-about-dialog">' +
            '<p>WpPygments is a Wordpress plugin that colorizes your code snippets using the web-service created around a popular Pygments library.</p>' +
            '<p>Both the service and the plugin are developed by Eugene Mirotin.</p>' +
          '</div>');
        dialog.dialog({ autoOpen: false, title: 'WpPygments by Eugene Mirotin' });
      }
			dialog.dialog('open');
			return false;
		});
	});
}(jQuery));