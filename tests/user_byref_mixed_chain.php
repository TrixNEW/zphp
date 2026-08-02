<?php

function userByRefMixedChainSet(&$value): void
{
    $value = 'changed';
}

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

userByRefMixedChainSet(
    $root->items[$itemKey]->$childProperty[$childKey]->$valuesProperty[$valueKey],
);

echo $root->items['first']->children['second']->values['target'], "\n";
