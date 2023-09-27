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
		$first_node = 0;
		$last_used = 0;
		$first_freed = 0;
		$count_freed = 0;
		$this->bytes = 'WRDS' . pack('L4', $first_node, $last_used, $first_freed, $count_freed);
	}

	/**
	 * @returns array [$success bool, $path array of nodes visited, $bits_found]
	 */
	public function find($word) : array
	{
		$traverse_node = 1;
		$found = false;
		$path = [];
		$bits_found = 0;
		if (strlen($this->bytes) < 20 * ($traverse_node + 1)) {
			return [$found, $path, $bits_found];
		}
		$is_leaf = false;
		$path[] = $traverse_node;
		
		while ($traverse_node && $bits_found < strlen($word) * 8)
		{
			$node = unpack('L5', substr($this->bytes, 20 * $traverse_node, 20));
			$node_bit_len = $node[1] & 0x1f000000;
			$node_string = Tools::bit_substr($node[1] & 0xffffff, 0, $node_bit_len);
			if (Tools::bit_compare($node_string, Tools::bit_substr($word, $bits_found)) == $node_bit_len) {
				$bits_found += $node_bit_len;
				$is_leaf = $node[1] & 0x20000000;
				if ($is_leaf && $bits_found == strlen($word) * 8) {
					$found = true;
					break;
				}
			} else {
				break;
			}
			$next_bit = Tools::bit_substr($bits_found, 1) == 0x80;
			$next_node = $node[2 + (int) $next_bit];
			$bits_found++;
			if ($next_node) {
				$traverse_node = $next_node;
				array_push($path, $traverse_node);
			} else {
				break;
			}
		}

		return [$found, $path, $bits_found];
	}

	public function insert($word, $path, $bits_found): array
	{
		$result = $this->find($word);
		if ($result[0]) {
			return $result;
		}
		$next_node_id = $this->getNewNode();
		if (count($path)) {
			$current_node = end($path);
			$first_bit = ord(Tools::bit_substr($word, $bits_found, 1)) == 0x80;
			$flag_byte = ord(substr($this->bytes, 20 * $failed_node, 1));
			Tools::string_splice($this->bytes, 20 * $current_node, chr(!$first_bit ? $flag_byte | 0x80 : $flag_byte | 0x40));
			Tools::string_splice($this->bytes, 20 * $current_node + 4 + ((int) $first_bit * 4), pack('L', $next_node_id));
			$bits_found++;
		}
		$remaining_bits = strlen($word) - $bits_found;
		if ($remaining_bits > 24) {
			$node_bits = 24;
			$is_leaf = false;
		} else {
			$node_bits = $remaining_bits;
			$is_leaf = true;
		}
		$bits_found += $node_bits;
		$flags = $is_leaf ? 0x20 : 0x0;
		$encoded_string = chr($flags + $node_bits) . Tools::bit_substr($word, $bits_found, $node_bits);
		Tools::string_splice($this->bytes, 20 * $next_node_id, pack('a20', $encoded_string));
		array_push($path, $next_node_id);
		if (!$is_leaf) {
			return $this->insert($word, $path, $bits_found);
		} else {
			return [true, $path, $bits_found];
		}
	}

	public function setListNode($word_node, $wlist_node) : void
	{
	}

	public function getListNode($word_node) : int
	{
	}
	
	protected function getNewNode() : int
	{
		$data = unpack('L3', substr($this->bytes, 8, 12));
		if ($data[2]) {
			$new_node = $data[2];
			$next_free = unpack('L', substr($this->bytes, 20 * $new_node, 4));
			Tools::string_splice($this->bytes, 8, pack('L2', $next_free, $data[3] - 1));
		} else {
			$new_node = $data[1] + 1;
			Tools::string_splice($this->bytes, 4, pack('L', $new_node));
		}
		return $new_node;
	}
}