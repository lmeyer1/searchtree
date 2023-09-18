<?php
namespace Searchindex;

class WordIndex 
{
	protected $bytes;

	public function __construct ($bytes = null)
	{
		if (is_null($bytes)) {
			$this->init();
		} else {
			$this->bytes = $bytes;
		}
	}

	public function getBytes()
	{
		return $this->bytes;
	}

	protected function init()
	{
		$last_used = 1;
		$first_freed = 0;
		$count_freed = 0;
		$this->bytes = 'WRDS' . pack('L4', $last_used, $first_freed, $count_freed, 0);
		$this->bytes .= pack('L5', 0, 0, 0, 0, 0);
	}

	public function find($word) : int
	{
		$traverse_node = 1;
		$bits_found = 0;
		
		while ($traverse_node && $bits_found < strlen($word) * 8)
		{
			$next_byte = ord(substr($word, intdiv($bits_found, 8), 1));
			$next_bit = (bool)($next_byte << ($bits_found % 8)) & 0x80;
			
			$node_pointer = 20 * $traverse_node + 4 + (int) $next_bit * 4;
			$node = unpack('L5', substr($this->bytes, 20 * $traverse_node, 4));
			$next_node = $node[2 + (int) $next_bit];
			
			if ($next_node) {
				$node_bit_len = $node[1] & 0x1f000000;
				if (string_matches()){
					$traverse_node = $next_node;
					$bits_found += $node_bit_len;
				} else {
					return 0;
				}
			} else {
				return 0;
			}
		}
		if (!isLeaf($traverse_node)) {
			return 0;
		}
		return $traverse_node;
	}

	public function insert($word) : int
	{
		$node = unpack('L5', substr($this->bytes, 20 * 1, 20));
		$node['string'] = $node[0] & 0xffffff;
		$node['strbits'] = $node[0] & 0x1f000000;
		    // Begin at the root with no elements found


	}

	public function setListNode($word_node, $wlist_node) : void
	{
	}

	public function getListNode($word_node) : int
	{
	}
}