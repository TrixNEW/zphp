<?php

function &referenceReturnMixedChain(object $root, string $item, string $property, string $child, string $values, string $key)
{
    return $root->items[$item]->$property[$child]->$values[$key];
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

$alias =& referenceReturnMixedChain($root, 'first', 'children', 'second', 'values', 'target');
$alias = 'through-alias';
echo $root->items['first']->children['second']->values['target'], "\n";

$root->items['first']->children['second']->values['target'] = 'through-target';
echo $alias, "\n";
