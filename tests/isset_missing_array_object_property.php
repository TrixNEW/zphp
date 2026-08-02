<?php

$items = [];
var_dump(isset($items['missing']->declared));
var_dump(isset($items['missing']->nested->declared));
$items['present'] = (object) ['declared' => null];
var_dump(isset($items['present']->declared));
$items['present']->declared = 'yes';
var_dump(isset($items['present']->declared));
