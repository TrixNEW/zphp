<?php
class MixedMethods { public function setLength($value) {} private function HiddenCase() {} }
$o=new MixedMethods();
foreach (['setLength','setlength','SETLENGTH','HiddenCase','hiddencase','missing'] as $m) var_dump(method_exists($o,$m), method_exists(MixedMethods::class,$m));
