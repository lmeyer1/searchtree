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

    public static function insertSecondProvider(): array
    {
        return [
            ['tes', [1], 24],
            ['tesère', [1], 24], // 7 chars
            ['the', [], 0],
/*          ['tür', [], 0],
            ['testimony', [1], 24],
            ['test it', [1], 24],
            ['1025', [], 0],*/
        ];
    }

    /**
     * @dataProvider insertSecondProvider
     * @depends testFindInEmpty
     */
    public function testInsertSecond($word_to_insert, $path_expected, $same_bits_expected, $depend_result): void
    {
        static $words = ['test'];
        $index = $depend_result[0];

        foreach ($words as $word) {
            list ($success, $path, $same_bits) = $index->find($word);
            $this->assertEquals(
                true,
                $success,
                "Finding word '$word' before insert: success"
            );
            $this->assertEquals(
                strlen($word) * 8,
                $same_bits,
                "Finding word '$word' before insert: count same bits"
            );
        }

        list($success, $path, $same_bits) = $index->find($word_to_insert);
        $this->assertEquals(
            false,
            $success,
            "Finding word '$word_to_insert' before insert: success"
        );
        $this->assertEquals(
            $path_expected,
            $path,
            "Finding word '$word_to_insert' before insert: path"
        );
        $this->assertEquals(
            $same_bits_expected,
            $same_bits,
            "Finding word '$word_to_insert' before insert: count same bits"
        );

        var_dump($word_to_insert, $path, $same_bits);
        $result = $index->insert($word_to_insert, $path, $same_bits);
        $words[] = $word_to_insert;
        Tools::bin_dump($index->getBytes());
        $this->assertEquals(
            strlen($word_to_insert) * 8,
            $result[2],
            "Inserting word '$word_to_insert'"
        );
        foreach ($words as $word) {
            list ($success, $path, $same_bits) = $index->find($word);
            $this->assertEquals(
                true,
                $success,
                "Finding word '$word' after insert: success"
            );
            $this->assertEquals(
                strlen($word) * 8,
                $same_bits,
                "Finding word '$word' after insert: count same bits"
            );
        }
    }
}
