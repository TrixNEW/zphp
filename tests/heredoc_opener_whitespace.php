<?php
$value = 'world';
echo <<< TEXT
hello $value
TEXT, "\n";
echo <<< 	"TEXT"
    hello $value
    second line
    TEXT, "\n";
echo <<< 	'TEXT'
    literal $value\n
    second line
    TEXT, "\n";
echo <<<"TEXT"
  quoted $value
  TEXT, "\n";
function spacedDefault($text = <<< END
constant
END) {
    return $text;
}
echo spacedDefault(), "\n";
