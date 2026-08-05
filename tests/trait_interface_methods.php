<?php

namespace App\Contract {
    interface Comparison
    {
        public function greaterThan(int $other): bool;
        public function lessThan(int $other): bool;
    }
}

namespace App\Support {
    trait BaseComparison
    {
        public function lessThan(int $other): bool
        {
            return $other < 0;
        }
    }

    trait BasicComparison
    {
        use BaseComparison;

        public function greaterThan(int $other): bool
        {
            return $other > 0;
        }
    }
}

namespace App {
    use App\Contract\Comparison;
    use App\Support\BasicComparison;

    final class Number implements Comparison
    {
        use \App\Support\BasicComparison;
    }

    $number = new Number();
    var_dump($number->greaterThan(1), $number->lessThan(-1));
}
