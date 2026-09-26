<?php
// a written class name resolves against the namespace for writes as for reads

namespace App\Config;

class Settings { public static $values = ['a']; public static $mode = 'off'; }
class Writer {
    public static function write() {
        Settings::$mode = 'on';
        Settings::$values[] = 'b';
        \App\Config\Settings::$values[] = 'c';
    }
}
Writer::write();
var_dump(Settings::$mode, Settings::$values);
