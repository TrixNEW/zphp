<?php
// setDate, setTime, and setISODate change the wall-clock time in the object's
// own timezone, across dst changes, fixed offsets, overflow, and pre-1970
$tz = new DateTimeZone('America/New_York');
$d = new DateTime('2024-03-10 22:30:00', $tz);
$d->setTime(8, 15); echo $d->format(DATE_ATOM), "\n";
$d->setDate(2024, 1, 15); echo $d->format(DATE_ATOM), "\n";
$d->setDate(2024, 7, 4); echo $d->format(DATE_ATOM), "\n";
$d->setISODate(2024, 2, 1); echo $d->format(DATE_ATOM), "\n";
$d->setISODate(2020, 53, 7); echo $d->format(DATE_ATOM), "\n";

// a local time skipped by spring-forward, and the hour repeated at fall-back
$g = new DateTime('2024-03-10 00:00:00', $tz); $g->setTime(2, 30); echo $g->format(DATE_ATOM), "\n";
$f = new DateTime('2024-11-03 00:00:00', $tz); $f->setTime(1, 30); echo $f->format(DATE_ATOM), " ", $f->getTimestamp(), "\n";

// overflow rolls into the neighbouring fields
$o = new DateTime('2024-01-31 10:00:00', $tz);
$o->setTime(25, 70, 70); echo $o->format(DATE_ATOM), "\n";
$o->setDate(2024, 2, 30); echo $o->format(DATE_ATOM), "\n";
$o->setDate(2024, 13, 1); echo $o->format(DATE_ATOM), "\n";

// before 1970, fixed offsets, utc, and the immutable variants
$old = new DateTime('1950-06-15 12:00:00', new DateTimeZone('Europe/Paris'));
$old->setTime(3, 4, 5); echo $old->format(DATE_ATOM), "\n";
$old->setDate(1901, 1, 1); echo $old->format(DATE_ATOM), "\n";
$fixed = new DateTime('2024-06-01 10:00:00+02:00'); $fixed->setDate(2024, 12, 1); $fixed->setTime(3, 4); echo $fixed->format(DATE_ATOM), "\n";
$u = new DateTime('2024-01-01 00:00:00', new DateTimeZone('UTC')); $u->setTime(5, 6); echo $u->format(DATE_ATOM), "\n";
$i = new DateTimeImmutable('2024-07-04 23:00:00', new DateTimeZone('Asia/Tokyo'));
echo $i->setTime(1, 2, 3)->format(DATE_ATOM), " ", $i->setDate(2025, 2, 28)->format(DATE_ATOM), " ", $i->setISODate(2025, 10)->format(DATE_ATOM), " ", $i->format(DATE_ATOM), "\n";

// the procedural forms
$p = date_create('2024-11-03 00:30:00', timezone_open('America/New_York'));
date_time_set($p, 1, 30); echo date_format($p, DATE_ATOM), "\n";
date_date_set($p, 2024, 6, 1); echo date_format($p, DATE_ATOM), "\n";
date_isodate_set($p, 2024, 1, 3); echo date_format($p, DATE_ATOM), "\n";
var_dump(date_offset_get($p));
