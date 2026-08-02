<?php

$source = 'before';
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

$item = 'first';
$property = 'children';
$child = 'second';
$values = 'values';
$key = 'target';

$root->items[$item]->$property[$child]->$values[$key] =& $source;
$source = 'source-write';
echo $root->items['first']->children['second']->values['target'], "\n";
$root->items['first']->children['second']->values['target'] = 'target-write';
echo $source, "\n";
