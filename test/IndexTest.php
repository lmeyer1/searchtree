<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IndexTest extends TestCase
{
	public function testInit(): void
    {
		$index = new Searchindex\Index(null);
		$this->assertEquals(
            pack('H*', '494e4458280000001400000004000000' . '57524453010000000000000000000000000000000000000000000000000000000000000000000000' . '574c535400000000000000000000000000000000' . '444f435' . '3444c535400000000000000000000000000000000'),
            $index->getBytes()
        );
    }
}
