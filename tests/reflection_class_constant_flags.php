<?php
foreach (['IS_PUBLIC', 'IS_PROTECTED', 'IS_PRIVATE', 'IS_FINAL'] as $name) {
    $reflection = new ReflectionClassConstant(ReflectionClassConstant::class, $name);
    echo $name, '=', $reflection->getValue(), ':', $reflection->isPublic() ? 'public' : 'hidden', "\n";
}
echo ReflectionClassConstant::IS_PUBLIC | ReflectionClassConstant::IS_PROTECTED, "\n";
