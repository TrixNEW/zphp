<?php
// every line of the fixtures through strtotime(), modify(), date_parse(), and the
// constructor (its zone and errors only, since its holes come from the real clock)
function run_corpus(string $file, string $zone): void
{
    date_default_timezone_set($zone);
    $base = 1710000000 + 7 * 86400 * 5 + 3723;
    $b = (new DateTimeImmutable('@' . $base))->setTimezone(new DateTimeZone($zone));
    foreach (file(__DIR__ . '/fixtures/' . $file, FILE_IGNORE_NEW_LINES) as $s) {
        echo '[', $s, "]\n";
        $t = strtotime($s, $base);
        echo '  strtotime: ', $t === false ? 'false' : $t . ' ' . date('Y-m-d H:i:s T', $t), "\n";
        try {
            echo '  modify: ', $b->modify($s)->format('Y-m-d H:i:s.u T e'), "\n";
        } catch (DateMalformedStringException $e) {
            echo '  modify: ', $e->getMessage(), "\n";
        }
        try {
            $d = new DateTimeImmutable($s);
            echo '  construct: ', $d->getTimezone()->getName(), ' ', json_encode(DateTimeImmutable::getLastErrors()), "\n";
        } catch (DateMalformedStringException $e) {
            echo '  construct: ', $e->getMessage(), "\n";
        }
        echo '  parse: ', json_encode(date_parse($s)), "\n";
    }
}

run_corpus('date_strings.txt', 'America/New_York');
run_corpus('date_strings_fuzz.txt', 'Europe/Paris');
