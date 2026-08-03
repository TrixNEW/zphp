<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
final class RuntimeTest extends TestCase {
    private FixtureCalculator $calculator;
    protected function setUp(): void { $this->calculator = new FixtureCalculator(); }
    #[Test] public function assertions(): void {
        self::assertSame(5, $this->calculator->add(2, 3)); self::assertTrue(true);
        self::assertArrayHasKey('name', ['name' => 'zphp']); self::assertStringEndsWith('php', 'zphp');
        self::assertJsonStringEqualsJsonString('{"a":1,"b":2}', '{"b":2,"a":1}');
    }
    #[DataProvider('cases')] public function test_data_provider(int $a, int $b, int $sum): void { self::assertSame($sum, $this->calculator->add($a, $b)); }
    public static function cases(): array { return [[1, 2, 3], [-2, 5, 3], [0, 0, 0]]; }
    public function test_mock(): void {
        $service = $this->createMock(FixtureService::class);
        $service->expects(self::exactly(2))->method('fetch')->willReturnMap([['first', 'one'], ['second', 'two']]);
        self::assertSame('one', $service->fetch('first')); self::assertSame('two', $service->fetch('second'));
    }
    public function test_exception(): void { $this->expectException(RuntimeException::class); $this->expectExceptionMessage('expected'); throw new RuntimeException('expected'); }
}
