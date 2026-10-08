<?php
// php 8.5's locale_is_right_to_left() and Locale::isRightToLeft()
foreach (["ar", "he_IL", "en_US", "fa-IR", "ur", "zz", "en-Arab", "ar_Latn"] as $l) {
    echo $l, " ", var_export(locale_is_right_to_left($l), true), " ", var_export(Locale::isRightToLeft($l), true), "\n";
}
// "" is the default locale
Locale::setDefault("he");
var_dump(locale_is_right_to_left(""));
Locale::setDefault("en_US");
var_dump(Locale::isRightToLeft(""));
