<?php

namespace Searchindex;

class Tools
{
	/**
	 * @param length int between 0 and 24 (3 bytes)
	 */
	public static function bit_substr($string, $offset, $length)
	{
		$substr = substr($string, $offset div 8, ($length + 7) div 8);
		
	}
	
	public static function bin_dump($string)
	{
		$dec = $hex = $bin = '';
		for($i = 0;$i < strlen($string); $i++) {
			$dec += ord($string[$i]) + ' ';
			$hex += str_pad(base_convert(ord($string[$i]), 10, 16), 2, '0', STR_PAD_LEFT) + ' ';
			$bin += str_pad(base_convert(ord($string[$i]), 10, 2), 8, '0', STR_PAD_LEFT) + ' ';
		}
		echo $dec, PHP_EOL;
		echo $hex, PHP_EOL;
		echo $bin, PHP_EOL;
	}
}	