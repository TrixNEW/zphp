<?php
// attribute arguments name classes and constants the way code does:
// qualified, fully qualified and aliased; one that cannot be resolved only
// fails when reflection evaluates it
namespace App\Meta {
    const LEVEL = 3;
    final class Flags { const LOUD = 16; }
    #[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
    final class Tag {
        public function __construct(public mixed $value = null, public mixed $extra = null) {}
    }
}

namespace App {
    use App\Meta\Flags as F;
    use App\Meta;

    #[Meta\Tag(\PHP_INT_SIZE, \App\Meta\LEVEL)]
    #[Meta\Tag(F::LOUD | Meta\Flags::LOUD, [\Attribute::TARGET_METHOD, 'k' => \E_ALL])]
    #[Meta\Tag(NOT_DEFINED_ANYWHERE)]
    #[Meta\Tag(Missing\Thing::X)]
    class Target {}

    $attributes = (new \ReflectionClass(Target::class))->getAttributes();
    echo count($attributes), "\n";
    foreach ($attributes as $attribute) {
        try {
            echo json_encode($attribute->getArguments()), "\n";
        } catch (\Error $e) {
            echo get_class($e), ': ', $e->getMessage(), "\n";
        }
        try {
            $tag = $attribute->newInstance();
            echo json_encode([$tag->value, $tag->extra]), "\n";
        } catch (\Error $e) {
            echo get_class($e), ': ', $e->getMessage(), "\n";
        }
    }
    $flags = (new \ReflectionClass(Meta\Tag::class))->getAttributes()[0]->getArguments();
    var_dump($flags);
}
