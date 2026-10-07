<?php
// case and default may end with ; instead of : (deprecated in 8.5, still valid),
// and a stray ; may precede the first case
foreach ([1, 2, 3, 4] as $v) {
    switch ($v) {;
        case 1; echo "one\n"; break;
        case 2; case 3: echo "two or three\n"; break;
        default; echo "other\n";
    }
}
switch ("b"): ;
    case "a"; echo "a\n"; break;
    default; echo "default\n";
endswitch;
