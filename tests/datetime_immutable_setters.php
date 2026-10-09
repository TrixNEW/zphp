<?php
// the immutable setters return a clone that keeps the subclass, its own
// properties, and the microseconds; setTimestamp resets the microseconds
class MyDate extends DateTimeImmutable { public $tag = 'x'; }
$i = new MyDate("2024-01-01 10:00:00.5", new DateTimeZone("Europe/Paris"));
$i->tag = 'kept';
foreach ([
  $i->setTimestamp(5), $i->setDate(2020, 1, 1), $i->setTime(1, 2), $i->setTime(1, 2, 3, 4), $i->setISODate(2021, 3),
  $i->setTimezone(new DateTimeZone("Asia/Tokyo")), $i->setMicrosecond(7), $i->add(new DateInterval("P1D")), $i->sub(new DateInterval("PT1H")), $i->modify("+1 day"),
] as $r) echo get_class($r), " ", $r->tag, " ", $r->format("Y-m-d H:i:s.u e"), "\n";
echo $i->format("Y-m-d H:i:s.u e"), "\n";
$m = DateTime::createFromImmutable($i); echo get_class($m), " ", $m->format("Y-m-d H:i:s.u e"), "\n";
$b = DateTimeImmutable::createFromMutable($m); echo get_class($b), " ", $b->format("Y-m-d H:i:s.u e"), "\n";
$d = new DateTime("2024-01-01 10:00:00.5"); $d->setTimestamp(5); echo $d->format("u"), "\n";
