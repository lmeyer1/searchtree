<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Searchindex\Tools;

final class ToolsTest extends TestCase
{
    public function testBitSubstr(): void
    {
        $this->assertEquals(
            'abc',
            Tools::bit_substr('abc', 0, 24)
        );
    }
}