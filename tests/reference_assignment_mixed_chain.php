<?php

$root = (object) [
    'items' => [
        'first' => (object) [
            'children' => [
                'second' => (object) [
                    'values' => ['target' => 'before'],
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

$alias =& $root->items[$itemKey]->$childProperty[$childKey]->$valuesProperty[$valueKey];
$alias = 'through-alias';
echo $root->items['first']->children['second']->values['target'], "\n";

$root->items['first']->children['second']->values['target'] = 'through-target';
echo $alias, "\n";
