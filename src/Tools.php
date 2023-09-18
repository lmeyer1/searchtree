<?php

namespace Searchindex;

class Tools
{
	public const BIN_DUMP_DEC = 0;
	public const BIN_DUMP_HEX = 1;
	public const BIN_DUMP_BIN = 2;

	public static function bit_substr($string, $offset, $length)
	{
		$substr = substr($string, intdiv($offset, 8), intdiv($length + 7 + $offset % 8, 8));
		if ($offset % 8) { // shift the bits
			$substr_new = '';
			$retain = "\0";
			for($i = 0; $i < strlen($substr) / 3; $i++) {
				$int32 = unpack('N', str_pad(substr($substr, $i * 3, 3), 4, "\0", STR_PAD_LEFT));
				$str4 = pack('N', $int32[1] << ($offset % 8));
				$bytes_exceeding = ($i + 1) * 3 - strlen($substr);
				$bytes_exceeding = $bytes_exceeding > 0 ? $bytes_exceeding : 0;
				$shifted = substr($str4, 1 + $bytes_exceeding);
				if(ord($retain)) {
					$substr_new[-1] = chr(ord($substr_new[-1]) | ord($retain));
				}
				$substr_new .= $shifted;
				$retain = substr($str4, 0, 1);
			}
			$substr = substr($substr_new, 0, intdiv($length + 7, 8));
		}
		if ($length % 8) {
			$masks = [0x80, 0xc0, 0xe0, 0xf0, 0xf8, 0xfc, 0xfe, 0xff];
			$substr[-1] = chr(ord($substr[-1]) & $masks[$length % 8 - 1]);
		}
		return $substr;
	}
	
	public static function bin_dump($string, $mode = self::BIN_DUMP_HEX, $return = false)
	{
		$output = '';
		for($i = 0;$i < strlen($string); $i++) {
			switch ($mode) {
				case self::BIN_DUMP_DEC:
					$output .= ord($string[$i]);
					break;
				case self::BIN_DUMP_HEX:
					$output .= str_pad(base_convert(ord($string[$i]), 10, 16), 2, '0', STR_PAD_LEFT);
					break;
				case self::BIN_DUMP_BIN:
					$output .= str_pad(base_convert(ord($string[$i]), 10, 2), 8, '0', STR_PAD_LEFT);
			}
			$output .= ' ';
			if ($i && !($i % 8)) {
				$output .= PHP_EOL;
			}
		}
		if ($return) {
			return trim($output);
		} else {
			echo trim($output) . PHP_EOL;
			return;
		}
	}
}	