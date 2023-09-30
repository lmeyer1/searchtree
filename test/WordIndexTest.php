<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Searchindex\Tools;
use Searchindex\WordIndex;

final class WordIndexTest extends TestCase
{
	public function testInit(): WordIndex
	{
		$index = new WordIndex(null);
		$this->assertEquals(
			pack('H*', '5752445300000000000000000000000000000000'),
			$index->getBytes()
		);
		return $index;
	}
	
	/**
	 * @depends testInit
	 */
	public function testFindInEmpty($index): array
	{
		$result = $index->find('test');
		$this->assertEquals(
			[false, [], 0],
			$result
		);
		return [$index, $result];
	}
	
	/**
	 * @depends testFindInEmpty
	 */
	public function testInsert($args): WordIndex
	{
		$index = $args[0];
		$result = $args[1];
		$result = $index->insert('test', $result[1], $result[2]);
		Tools::bin_dump($index->getBytes());
		$this->assertEquals(
			[true, [1, 2], 32],
			$result,
			"Inserting 'test'"
		);
		$this->assertEquals(
			[true, [1, 2], 32],
			$index->find('test'),
			"Finding 'test'"
		);
		return $index;
	}
}
