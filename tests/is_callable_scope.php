<?php
// callability is judged from the calling scope, with __call/__callStatic fallbacks
class P { private function priv() {} protected function prot() {} public function pub() {} public static function st() {} public function __call($n, $a) {} 
  function test() { return [is_callable([$this, 'priv']), is_callable([$this, 'prot']), is_callable([$this, 'zzz']), is_callable('P::st'), is_callable([self::class, 'pub'])]; } }
class C extends P { function t2() { return [is_callable([$this, 'priv']), is_callable([$this, 'prot']), is_callable([$this, 'pub'])]; } }
class N { private function x() {} }
var_dump((new P)->test(), (new C)->t2(), is_callable([new P, 'priv']), is_callable([new P, 'prot']), is_callable([new N, 'x']), is_callable([new N, 'nope']), is_callable('N::x'), is_callable([new C, 'pub']));
