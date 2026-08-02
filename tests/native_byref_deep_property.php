<?php

$root = (object) [];
$cursor = $root;
for ($i = 0; $i < 20; $i++) {
    $name = 'p' . $i;
    $cursor->$name = (object) [];
    $cursor = $cursor->$name;
}
$cursor->value = null;

parse_str(
    'value=deep',
    $root->p0->p1->p2->p3->p4->p5->p6->p7->p8->p9->p10->p11->p12->p13->p14->p15->p16->p17->p18->p19->value,
);

echo $cursor->value['value'], "\n";
