<?php
trait QueryBaseTrait {}
trait QueryTrait { use QueryBaseTrait; }
interface QueryRoot {}
interface QueryOther {}
interface QueryChild extends QueryRoot, QueryOther {}
interface QueryLeaf extends QueryChild {}
foreach ([QueryTrait::class, QueryRoot::class, QueryChild::class, QueryLeaf::class] as $name) {
    echo $name, ':', json_encode(array_values(class_implements($name, false))), "\n";
    var_dump(get_parent_class($name));
    echo json_encode(array_values(class_uses($name, false))), "\n";
}
