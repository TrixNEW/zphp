<?php

$root = (object) [
    'items' => [
        'first' => (object) [
            'children' => [
                'second' => (object) [
                    'values' => ['target' => null],
                ],
            ],
        ],
    ],
];

$itemKey = 'first';
$childProperty = 'children';
$childKey = 'second';
$valuesProperty = 'values';
$valueKey = 'target';

parse_str(
    'result=mixed',
    $root->items[$itemKey]->$childProperty[$childKey]->$valuesProperty[$valueKey],
);

echo $root->items['first']->children['second']->values['target']['result'], "\n";
