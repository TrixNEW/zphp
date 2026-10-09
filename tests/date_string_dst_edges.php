<?php
// date strings read at instants on both sides of dst transitions, in zones with
// whole-hour, half-hour, and 45-minute offsets, with and without an explicit zone
$zones = ['America/New_York', 'Europe/Paris', 'Australia/Sydney', 'Australia/Lord_Howe', 'America/Sao_Paulo', 'Pacific/Chatham', 'Asia/Kolkata', 'UTC'];
$strings = ['2024-03-10 02:30', '2024-03-10 01:59:59', '2024-03-10 03:00', '2024-11-03 01:30', '2024-11-03 00:59:59', '2024-11-03 02:00',
  '2024-03-31 02:30', '2024-10-27 02:30', '2024-10-27 01:30', '2024-04-07 02:30', '2024-10-06 02:30', '2024-04-07 01:45', '2024-10-06 01:45',
  '+1 hour', '-1 hour', '+1 day', '-1 day', '+30 minutes', 'tomorrow', 'midnight', 'noon', '02:30', '01:30', '+24 hours', 'next sunday', 'last day of next month',
  '2024-11-03 01:30 EDT', '2024-11-03 01:30 EST', '2024-11-03 01:30 America/New_York', '2024-10-27 02:30 Europe/Paris', '2024-10-27 02:30 CEST', '2024-10-27 02:30 CET',
  '@1730612400', '1 week ago', 'first monday of next month', '+1 month', '2024-03-10', '2024-11-03'];
$bases = [1710054000, 1710057600, 1710054000 - 1800, 1730608200, 1730611800, 1730615400, 1711846800, 1729990800, 1729994400, 1712419200, 1728187200, 1700000000];
foreach ($zones as $zone) {
    date_default_timezone_set($zone);
    foreach ($bases as $base) {
        foreach ($strings as $s) {
            $t = strtotime($s, $base);
            $b = (new DateTimeImmutable('@' . $base))->setTimezone(new DateTimeZone($zone));
            try { $m = $b->modify($s)->format('U T'); } catch (Throwable $e) { $m = get_class($e); }
            $c = new DateTimeImmutable($s);
            $cz = $c->getTimezone()->getName();
            echo "$zone $base [$s] ", $t, " | ", $m, " | ", $cz, "\n";
        }
    }
    // explicit date strings in the constructor are deterministic
    foreach ($strings as $s) {
        if (!preg_match('/^\d{4}-/', $s)) continue;
        $c = new DateTimeImmutable($s);
        echo "$zone ctor [$s] ", $c->format('c U T I'), "\n";
        $c = new DateTimeImmutable($s, new DateTimeZone('Europe/Paris'));
        echo "$zone ctorP [$s] ", $c->format('c U T I'), "\n";
    }
}
