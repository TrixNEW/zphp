<?php
// php 8.5's PHP_BUILD_DATE and max_memory_limit (unlimited by default, and
// only settable at startup)
var_dump(is_string(PHP_BUILD_DATE), (bool) preg_match('/^[A-Z][a-z]{2} [ \d]\d \d{4} \d\d:\d\d:\d\d$/', PHP_BUILD_DATE));
var_dump(ini_get('max_memory_limit'));
var_dump(ini_set('max_memory_limit', '1G'), ini_get('max_memory_limit'));
var_dump(ini_set('memory_limit', '64M') !== false, ini_get('memory_limit'));
