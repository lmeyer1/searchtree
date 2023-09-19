<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Searchindex\Tools;

final class ToolsTest extends TestCase
{
	public static function bitSubstrProvider(): array
	{
		return [
			['abc', 0, 24, 'abc'],
			['abc', 0, 12, 'a`'],
			['abc', 1, 2, "\xc0"],
			['abc', 8, 15, 'bb'],
			['abc', 6, 16, "X\x98"],
			['abcdefg', 3, 47, "\x0b\x13\x1b\x23\x2b\x32"],
			['abc', 6, null, "X\x98\xc0"],
		];
	}

	/**
	 * @dataProvider bitSubstrProvider
	 */
	public function testBitSubstr(string $input, int $offset, $length, string $result): void
	{
		$substr = Tools::bit_substr($input, $offset, $length);
		$this->assertEquals(
			$result,
			$substr
		);
	}

	public static function bitCompareProvider(): array
	{
		return [
			['abc', 'abc', 24],
			['a', 'abc', 8],
			['a', 'b', 6],
			['abcdefg', 'abz', 19],
		];
	}

	/**
	 * @dataProvider bitCompareProvider
	 */
	public function testBitCompare(string $string1, string $string2, int $result): void
	{
		$this->assertEquals(
			$result,
			Tools::bit_compare($string1, $string2)
		);
	}

	public static function binDumpProvider(): array
	{
		return [
			['abc', 0, '97 98 99'],
			['abc', 1, '61 62 63'],
			['abc', 2, '01100001 01100010 01100011'],
		];
	}

	/**
	 * @dataProvider binDumpProvider
	 */
	public function testBinDump(string $input, int $mode, string $result): void
	{
		$this->assertEquals(
			$result,
			Tools::bin_dump($input, $mode, true)
		);
	}
}