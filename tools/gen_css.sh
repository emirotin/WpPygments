CSS_DIR="css/pygments"
WRAPPER=".highlighted"

cd "$(dirname "$0")/.."
pwd

for theme in 'monokai' 'manni' 'perldoc' 'borland' 'colorful' 'default' 'murphy' 'vs' 'trac' 'tango' 'fruity' 'autumn' 'bw' 'emacs' 'vim' 'pastie' 'friendly' 'native'; do
/opt/local/Library/Frameworks/Python.framework/Versions/2.7/bin/pygmentize -S $theme -a $WRAPPER -f html > $CSS_DIR/$theme.css;
java -jar tools/yuicompressor.jar -o $CSS_DIR/$theme.css $CSS_DIR/$theme.css
done;