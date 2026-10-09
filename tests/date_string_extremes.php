<?php
// extreme and malformed date strings: numbers past the integer range, relative
// amounts that overflow when applied, nul bytes, invalid utf-8, long garbage
date_default_timezone_set('America/New_York');
$base = 1710000000;
$inputs = [
  '@99999999999999999999999', '@-99999999999999999999999', '@9223372036854775807', '@-9223372036854775808', '@9223372036854775807.999999',
  '+9999999999999 days', '-9999999999999 days', str_repeat('+9999999999999 days ', 40), str_repeat('-9999999999999 weeks ', 40),
  '9999999999999 years', '+9999999999999 months', '+9999999999999 weekdays', '-9999999999999 weekdays', '9999999999999 sec', '9999999999999 usec',
  '@0 +9999999999999 days', '@0 ' . str_repeat('+9999999999999 weeks ', 30), "\0", "a\0b", "2024-01-01\0", "\xff\xfe\xfd", "\xc2", "\xe2\x80",
  str_repeat('x', 5000), str_repeat('2024-01-01 ', 300), str_repeat('+1 day ', 2000), '   ', ' ', "\t\n", '', '+', '-', '@', '@.', '@-', '@1.',
  '9999-99-99 99:99:99', '0000-00-00 00:00:00', '-0001-01-01', '+99999-01-01', '-99999999999-01-01', '9223372036854775807-01-01',
  'first monday of +9999999999999 months', 'last day of -9999999999999 months', '+9999999999999 hours', 'tuesday +9999999999999 weeks',
  '2024W53', '2024W99-9', '2024-366', '1999-367', '24:60:60', 'back of 24', 'front of 0', '12:00 PM PM', '(((UTC', 'UTC)))', 'Europe/Paris/Extra',
];
foreach ($inputs as $s) {
    $t = @strtotime($s, $base);
    $p = date_parse($s);
    try { $m = (new DateTimeImmutable('@' . $base))->modify($s)->format('Y-m-d H:i:s.u e'); } catch (Throwable $e) { $m = get_class($e) . ': ' . $e->getMessage(); }
    echo json_encode(substr($s, 0, 60)), ' => ', var_export($t, true), ' | ', $m, ' | ', $p['error_count'], '/', $p['warning_count'], ' ', json_encode([$p['year'], $p['month'], $p['day'], $p['hour'], $p['relative'] ?? null]), "\n";
}
