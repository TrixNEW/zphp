<?php
// ill-formed UTF-8 through the string natives: mbstring decodes each bad
// sequence as one '?' character, iconv refuses it, htmlspecialchars
// substitutes U+FFFD by default, and intl answers its failure value
$inputs = ["\xe0\x80\x80", "\xf4\x90\x80\x80", "\xc2\xc0x", "\xe2\x82\xc0x", "h\xc3", "\xc3\x28\xa0\xa1\xe2\x28\xa1\xf0\x28\x8c\xbc\xf0\x90\x28", "a\xe2\x82", "\xf0\x9f\x98\x80x", "\xff\xfeab", "\xc0\xafz", "\xed\xa0\x80q", "ok\xc3\xa9"];
foreach ($inputs as $s) {
    echo bin2hex($s), "\n";
    echo "  mb: ", mb_strlen($s), ' ', bin2hex(mb_substr($s, 1, 5)), ' ', bin2hex(mb_substr($s, -2)), ' ', json_encode(array_map('bin2hex', mb_str_split($s))), ' ', json_encode(array_map('bin2hex', mb_str_split($s, 2))), "\n";
    echo "  case: ", bin2hex(mb_strtoupper($s)), ' ', bin2hex(mb_strtolower($s)), ' ', bin2hex(mb_convert_case($s, MB_CASE_TITLE)), "\n";
    echo "  convert: ", bin2hex(mb_convert_encoding($s, 'UTF-16', 'UTF-8')), ' ', bin2hex(mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8')), ' ', bin2hex(mb_convert_encoding($s, 'ASCII', 'UTF-8')), ' ', bin2hex(mb_convert_encoding($s, 'UTF-8', 'UTF-8')), "\n";
    echo "  iconv: ", var_export(@iconv('UTF-8', 'ISO-8859-1', $s), true), ' ', var_export(@iconv('UTF-8', 'ASCII', $s), true), "\n";
    echo "  html: ", bin2hex(htmlspecialchars($s)), ' ', bin2hex(htmlspecialchars($s, ENT_QUOTES)), ' ', bin2hex(htmlspecialchars($s, ENT_QUOTES | ENT_IGNORE)), ' ', bin2hex(htmlspecialchars($s, ENT_QUOTES, 'ISO-8859-1')), ' ', bin2hex(htmlentities($s)), "\n";
    if (function_exists('grapheme_strlen')) {
        echo "  intl: ", var_export(grapheme_strlen($s), true), ' ', var_export(grapheme_substr($s, 0, 1) !== false, true), ' ', var_export(grapheme_strpos($s, 'a'), true), ' ', var_export(normalizer_normalize($s) !== false, true), "\n";
    }
}

// title case follows Unicode's cased and case-ignorable properties
foreach (["a1b", "don't", "x.y", "3rd place", "hello_world", "\u{FB01}x", "\u{1C6}a", "\u{1F600}x", "\u{65E5}y", "e\u{301}a", "\u{DF} x", "a\u{2019}b", "\u{C9}COLE \u{E9}l\u{E8}ve", "o'neil mcdonald-smith", "stra\u{DF}e", "h\xc3llo w\xe2\x82"] as $s) {
    echo bin2hex(mb_convert_case($s, MB_CASE_TITLE)), "\n";
}
echo htmlentities("caf\xe9", ENT_QUOTES, 'ISO-8859-1'), "\n";
