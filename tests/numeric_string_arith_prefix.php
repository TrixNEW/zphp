<?php
// a leading-numeric string with a dangling exponent marker uses its numeric prefix
var_dump("1e" + 0);
var_dump("1e+" * 2);
var_dump("2.5e" - 1);
var_dump("1E-" + 1);
var_dump("7e" % 4);
var_dump(-"3e");
var_dump("1e2" + 0);
var_dump("1.5e1" * 2);
var_dump("0e" == "0");
