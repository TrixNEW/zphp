<?php

$object = (object) [
    'first' => (object) ['value' => 'old'],
    'second' => (object) ['value' => 'same'],
];

foreach ($object as &$member) {
    if ($member->value === 'old') {
        $member = (object) ['value' => 'changed'];
    }
}
unset($member);

echo $object->first->value, "\n";
echo $object->second->value, "\n";
