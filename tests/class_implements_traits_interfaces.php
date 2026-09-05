<?php
trait QueryTrait {}
interface QueryRoot {}
interface QueryOther {}
interface QueryChild extends QueryRoot, QueryOther {}
interface QueryLeaf extends QueryChild {}
foreach ([QueryTrait::class, QueryRoot::class, QueryChild::class, QueryLeaf::class] as $name) {
    echo $name, ':', json_encode(array_values(class_implements($name, false))), "\n";
}
