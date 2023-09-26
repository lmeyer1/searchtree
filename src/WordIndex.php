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

	/**
	 * @returns array [$success bool, $path array of nodes visited, $bits_found]
	 */
	public function find($word) : array
	{
		$traverse_node = 1;
		$bits_found = 0;
		$found = false;
		$is_leaf = false;
		$path = [$traverse_node];
		
		while ($traverse_node && $bits_found < strlen($word) * 8)
		{
			$next_bit = Tools::bit_substr($bits_found, 1) == 0x80;
			
			$node_pointer = 20 * $traverse_node + 4 + (int) $next_bit * 4;
			$node = unpack('L5', substr($this->bytes, 20 * $traverse_node, 20));
			$next_node = $node[2 + (int) $next_bit];
			$is_leaf = $node[1] & 0x20000000;
			
			if ($next_node) {
				$node_bit_len = $node[1] & 0x1f000000;
				$node_string = Tools::bit_substr($node[1] & 0xffffff, 0, $node_bit_len);
				if (Tools::bit_compare($node_string, Tools::bit_substr($word, $bits_found + 1)) == $node_bit_len){
					$traverse_node = $next_node;
					array_push($path, $traverse_node);
					$bits_found += $node_bit_len;
				} else {
					break;
				}
			} else {
				break;
			}
		}
		if ($is_leaf && $bits_found == strlen($word) * 8) {
			$found = true;
		}
		return [$found, $path, $bits_found];
	}

	public function insert($word, $path, $bits_found): array
	{
		$result = $this->find($word);
		if ($result[0]) {
			return $result;
		}
		$failed_node = end($path);
		$last_used = unpack('L', substr($this->bytes, 4, 4))[1];
		$node = unpack('L5', substr($this->bytes, 20 * $failed_node, 20));
		$first_bit = ord(Tools::bit_substr($word, $bits_found, 1)) == 0x80;
		$flag_byte = ord(substr($this->bytes, 20 * $failed_node, 1));
		if (!$first_bit && !($flag_byte & 0x80) || $first_bit && !($flag_byte & 0x40)) { // the child node is missing
			$next_node_id = pack('L', $last_used + 1);
			Tools::string_splice($this->bytes, 20 * $failed_node + 4 + ((int) $first_bit * 4), $next_node_id);
			$this->bytes[20 * $failed_node] = chr(!$first_bit ? $flag_byte | 0x80 : $flag_byte | 0x40);
			$remaining_bits = strlen($word) * 8 - $bits_found - 1;
			if ($remaining_bits > 24) {
				$node_bits = 24;
				$is_leaf = false;
			} else {
				$node_bits = $remaining_bits;
				$is_leaf = true;
			}
			$encoded_string = chr(($is_leaf ? 0x20 : 0x0) + $node_bits) . Tools::bit_substr($word, $bits_found + 1, $node_bits);
			$bits_found += $node_bits + 1;
			$next_node = pack('a20', $encoded_string);
			Tools::string_splice($this->bytes, 20 * ($last_used + 1), $next_node);
			Tools::string_splice($this->bytes, 4, pack('L', $last_used + 1));
			array_push($path, $last_used + 1);
			if (!$is_leaf) {
				return $this->insert($word, $path, $bits_found);
			} else {
				return [true, $path, $bits_found];
			}
		} else {
			
		}
	}

	public function setListNode($word_node, $wlist_node) : void
	{
	}

	public function getListNode($word_node) : int
	{
	}
}