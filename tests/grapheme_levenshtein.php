<?php
// php 8.5's grapheme_levenshtein(): edit distance over grapheme clusters,
// with canonically equivalent clusters equal
$pairs = [
    ["kitten", "sitting"], ["café", "cafe"], ["cafe\u{301}", "café"],
    ["👨‍👩‍👧", "👨"], ["", "abc"], ["abc", ""], ["Straße", "Strasse"], ["ABC", "abc"],
    ["🇫🇷🇩🇪", "🇩🇪🇫🇷"], ["", ""],
];
foreach ($pairs as [$a, $b]) {
    echo json_encode([$a, $b]), " ", var_export(grapheme_levenshtein($a, $b), true), "\n";
}
var_dump(grapheme_levenshtein("abc", "axc", 2, 3, 4));
var_dump(grapheme_levenshtein("abc", "abcd", 5));
var_dump(grapheme_levenshtein("abcd", "abc", 1, 1, 7));
var_dump(grapheme_levenshtein("abc", "ABC", 1, 1, 1, "en"));
foreach ([[0, 1, 1], [1, 0, 1], [1, 1, 0], [1073741824, 1, 1]] as [$i, $r, $d]) {
    try {
        grapheme_levenshtein("a", "b", $i, $r, $d);
    } catch (ValueError $e) {
        echo $e->getMessage(), "\n";
    }
}
var_dump(grapheme_levenshtein("\xff", "a"));
