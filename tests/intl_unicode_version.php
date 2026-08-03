<?php

var_dump(is_string(INTL_ICU_VERSION));
$version = IntlChar::getUnicodeVersion();
var_dump(is_array($version), count($version));
