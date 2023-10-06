<?php
namespace Searchindex;

use Exception;

class WordIndex
{
    protected $bytes;
    public $count_inserts;

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
        $root_node = 0;
        $found = false;
        $path = [];
        $bits_found = 0;
        $is_leaf = false;
        $current_node = unpack('L', substr($this->bytes, 4, 4))[1];

        while ($current_node && $bits_found < strlen($word) * 8)
        {
            $node_bytes = substr($this->bytes, 20 * $current_node, 20);
            $flag_byte = ord($node_bytes[0]);
            $node = unpack('L4', substr($node_bytes, 4));
            $node_bit_len = $flag_byte & 0x1f;
            $node_string = Tools::bit_substr(substr($node_bytes, 1, 3), 0, $node_bit_len);
            if (Tools::bit_compare($node_string, Tools::bit_substr($word, $bits_found)) >= $node_bit_len) {
                $bits_found += $node_bit_len;
                $path[] = $current_node;
                $is_leaf = $flag_byte & 0x20;
                if ($bits_found == strlen($word) * 8) {
                    if ($is_leaf) {
                        $found = true;
                    }
                    break;
                }
            } else {
                break;
            }
            $next_bit = Tools::bit_substr($word, $bits_found, 1) == "\x80";
            if (!$next_bit ? $flag_byte & 0x80 : $flag_byte & 0x40) {
                $next_node = $node[1 + (int) $next_bit];
                $bits_found++;
                if ($next_node) {
                    $current_node = $next_node;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return [$found, $path, $bits_found];
    }

    public function insert($word, $path, $bits_found): array
    {
        $this->count_inserts++;
        $result = $this->find($word);
        if ($result[0]) {
            return $result;
        }
        $bit_matches = false;
        if (count($path)) {
            $current_node = end($path);
            $node_bytes = substr($this->bytes, 20 * $current_node, 20);
            $flag_byte = ord($node_bytes[0]);
            if ($bits_found == strlen($word) * 8) { // we are at the correct node, but we don't have a leaf
                if ($flag_byte & 0x20) throw new Exception('Unexpected leaf flag');
                $flag_byte = $flag_byte | 0x20;
                Tools::string_splice($this->bytes, 20 * $current_node, chr($flag_byte));
                return [true, $path, $bits_found];
            }
            $first_bit = ord(Tools::bit_substr($word, $bits_found, 1)) == 0x80;
            $bit_matches = !$first_bit ? $flag_byte & 0x80 : $flag_byte & 0x40;
            if (!$bit_matches) { // the bit is not found
                Tools::string_splice($this->bytes, 20 * $current_node, chr(!$first_bit ? $flag_byte | 0x80 : $flag_byte | 0x40));
            }
            $bits_found++;
        } else {
            $current_node = 0;
            $first_bit = 0;
        }
        $next_node_id = $this->getNewNode();
        if ($bit_matches) {
            $node_bit_len = $flag_byte & 0x1f;
            $node_string = Tools::bit_substr(substr($node_bytes, 1, 3), 0, $node_bit_len);
            $matching_bits = Tools::bit_compare($node_string, Tools::bit_substr($word, $bits_found));
            if ($matching_bits >= $node_bit_len) throw new Exception('Too many matching bits');
            $flag_byte = $flag_byte & 0xe0 + $node_bit_len;
            Tools::string_splice($this->bytes, 20 * $current_node, chr($flag_byte) . pack('a3', Tools::bit_substr($word, $bits_found, $matching_bits)));
        } else {
            Tools::string_splice($this->bytes, 20 * $current_node + 4 + ((int) $first_bit * 4), pack('L', $next_node_id));
            $remaining_bits = strlen($word) * 8 - $bits_found;
            if ($remaining_bits > 24) {
                $node_bits = 24;
                $is_leaf = false;
            } else {
                $node_bits = $remaining_bits;
                $is_leaf = true;
            }
            $flags = $is_leaf ? 0x20 : 0x0;
            $encoded_string = chr($flags + $node_bits) . Tools::bit_substr($word, $bits_found, $node_bits);
            $bits_found += $node_bits;
            Tools::string_splice($this->bytes, 20 * $next_node_id, pack('a20', $encoded_string));
            array_push($path, $next_node_id);
        }
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
        return 0;
    }

    protected function getNewNode() : int
    {
        $data = unpack('L3', substr($this->bytes, 8, 12));
        if ($data[2]) {
            $new_node = $data[2];
            $next_free = unpack('L', substr($this->bytes, 20 * $new_node, 4));
            Tools::string_splice($this->bytes, 12, pack('L2', $next_free, $data[3] - 1));
        } else {
            $new_node = $data[1] + 1;
            Tools::string_splice($this->bytes, 8, pack('L', $new_node));
        }
        return $new_node;
    }
}