<?php

namespace Thetheago\SeederVersioning\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thetheago\SeederVersioning\Services\LineDiff;

class LineDiffTest extends TestCase
{
    public static function diffs(): array
    {
        return [
            'identical' => ["a\nb\nc", "a\nb\nc", 0, 0],
            'line added in the middle' => ["a\nc", "a\nb\nc", 1, 0],
            'line removed' => ["a\nb\nc", "a\nc", 0, 1],
            'line changed' => ["a\nb\nc", "a\nB\nc", 1, 1],
            'lines moved' => ["a\nb\nc\nd", "c\nd\na\nb", 2, 2],
            'from empty' => ['', "a\nb", 2, 0],
            'windows line endings' => ["a\r\nb\r\n", "a\nb\n", 0, 0],
        ];
    }

    #[DataProvider('diffs')]
    public function test_countAddedAndRemovedLines(string $old, string $new, int $added, int $removed): void
    {
        $this->assertSame(['added' => $added, 'removed' => $removed], LineDiff::count($old, $new));
    }

    public function test_hugeFilesAreComparedWithoutOrder(): void
    {
        $old = implode("\n", range(1, 3000));
        $new = implode("\n", array_reverse(range(1, 3000))) . "\nextra";

        $this->assertSame(['added' => 1, 'removed' => 0], LineDiff::count($old, $new));
    }
}
