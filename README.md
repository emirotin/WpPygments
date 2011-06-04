WpPygments is the plugin that utilizes Pygments library 
(through the http://pybmentizer.appspot.com service, which I also wrote) 
to highlight and colorize the code snippets in your WP blog.

Plugin page: http://blog.mirotin.net/wppygments

Plugin code: http://github.com/emirotin/WpPygments/

Pygmentizer service: http://pygmentizer.appspot.com/

Pygmentizer code: http://github.com/emirotin/pygmentizer/


Download
========
https://github.com/downloads/emirotin/WpPygments/WpPygments.zip

Installation
============
As usual - unzip the archive to your plugins forlder, go to WP admin panel and activate the plugin.

Configuration
=============
You can set several options: 

- theme -- pick one of the existing Pygments themes

- tools -- set which of the existing tools (view raw code, copy to clipboard, print) you need

- cache clearing -- set how long code highlighting cache should be stored (since the last access time)

Configuration is available as a separate WpPygments page under the Settings menu.

Known issues
============
With PG4WP cache is not working (looks like plugin<->DB communication is broken at all for all plugins).
This means, code will be re-submitted to pygmentizer service every time the page is requested.
I should say, it doesn't significanlty slow the page (tested with the page containing 10 small pieces of Bash code).
  
