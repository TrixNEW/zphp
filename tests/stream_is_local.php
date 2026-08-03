<?php

var_dump(stream_is_local('/tmp/file'));
var_dump(stream_is_local('file:///tmp/file'));
var_dump(stream_is_local('phar:///tmp/archive.phar/file'));
var_dump(stream_is_local('https://example.com/file'));
