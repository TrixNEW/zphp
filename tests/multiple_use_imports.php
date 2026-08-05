<?php

namespace App\Support {
    function first(): string { return 'first'; }
    function second(): string { return 'second'; }
    function third(): string { return 'third'; }
}

namespace App {
    use function App\Support\first, App\Support\second as renamed, App\Support\third;

    var_dump(first(), renamed(), third());
}
