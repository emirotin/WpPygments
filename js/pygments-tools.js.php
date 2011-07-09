<?php
if (!function_exists('add_action')) {
  require_once('../../../../wp-config.php');
}
?>

var ZeroClipboard, jQuery;

(function($) {
	$(function(){
    var zeroUrl = "<?php bloginfo('wpurl') ?>/wp-content/plugins/<?php echo basename(dirname((dirname(__FILE__)))) ?>/js/ZeroClipboard10.swf";
    ZeroClipboard.setMoviePath(zeroUrl);

    function create_clip(el, copy) {
      var clip = new ZeroClipboard.Client();
      clip.setText('');
      clip.setHandCursor(true);
      clip.addEventListener('mouseDown', function() {
        clip.setText(copy());
      });
      clip.glue(el, el.parentNode);
      $(el).data("clip", clip);    
      return clip;
    }
    
    function create_code_copy_clip(el) {
      var dom_el = el[0],
          code = el.parent().parent().parent().find('.raw code');
      create_clip(dom_el, function() {return code.text()});
    }
    
    $('.tools .show-raw').show();
    $('.tools .show-colored').hide();

		// clipboard
    $('.to-clipboard').each(function() {
      create_code_copy_clip($(this));
    });
    
    // toggle raw/ highlighted views  
    $('.highlighted').css('display', 'block');
    $('.tools .show-raw, .tools .show-colored').click(function() {
      var el = $(this),
          tools = el.parent().parent(),
          wrap = tools.parent(),
          to_clip = tools.find('.to-clipboard');
      wrap.children('.highlighted').slideToggle();
      wrap.children('.raw').slideToggle();
      tools.find('.show-colored').toggle();
      tools.find('.show-raw').toggle();
      to_clip.data("clip").destroy();
      create_code_copy_clip(to_clip);
      return false;
    });
    

    
    $(window).resize(function() {
      $('.to-clipboard').each(function() {
        $(this).data("clip").glue(this, this.parentNode);
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
            '<p>Read <a href="http://blog.mirotin.net/?page_id=49" target="_blank">here</a> for details.</p>' +
            '<p>Both the service and the plugin are developed by Eugene Mirotin.</p>' +
          '</div>');
        dialog.dialog({ autoOpen: false, title: 'WpPygments by Eugene Mirotin', width: 330 });
      }
			dialog.dialog('open');
			return false;
		});
	});
}(jQuery));