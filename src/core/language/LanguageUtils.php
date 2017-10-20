<?php

/**
 * LanguageUtils.php – Components
 *
 * Copyright (C) 2015-2017 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Jack Noordhuis
 *
 * Last modified on 20/10/2017 at 5:31 PM
 *
 */

namespace core\language;

use core\Utils;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class LanguageUtils {

	const MSG_LEN = 30;
	const TITLE_CHAR = '*';
	const BORDER_CHAR = '*';
	const LIST_PER_PAGE = 5;
	const CHAR_LENGTH = 6;
	const SPACE_CHAR = " ";

	private static $charLengths = [
		" " => 4,
		"!" => 2,
		"\"" => 5,
		"'" => 3,
		"(" => 5,
		")" => 5,
		"*" => 5,
		"," => 2,
		"." => 2,
		":" => 2,
		";" => 2,
		"<" => 5,
		">" => 5,
		"@" => 7,
		"I" => 4,
		"[" => 4,
		"]" => 4,
		"f" => 5,
		"i" => 2,
		"k" => 5,
		"l" => 3,
		"t" => 4,
		"{" => 5,
		"|" => 2,
		"}" => 5,
		"~" => 7,
		"█" => "9",
		"░" => "8",
		"▒" => "9",
		"▓" => "9",
		"▌" => "5",
		"─" => "9",
		//        "ï"  => 4,
		//        "ì" => 3,
		//        "×" => 4,
		//        "í" => 3,
		//        "®" => 7,
		//        "¡" => 2,
		//		"-" => 4
	];

	/**
	 * Wrap the message in a nice full-screen format, center text etc.
	 *
	 * @param $message
	 * @param bool $center
	 * @param bool $border
	 * @param bool $prefix
	 *
	 * @return array
	 */
	public static function wrapText($message, $center = false, $border = false, $prefix = false) {
		// Split into multiple lines
		if($prefix == true) {
			$prefix = Utils::PREFIX;
		}
		$wrapped = wordwrap((is_array($message) ? implode("\n", $message) : $message), self::MSG_LEN);
		$lines = explode("\n", $wrapped);
		$return = [];
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, self::MSG_LEN);
		}
		foreach($lines as $line) {
			$return[] = ($center) ? self::center($line) : ($prefix !== false ? $prefix : "") . $line;
		}
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, self::MSG_LEN);
		}
		return $return;
	}

	/**
	 * @param CommandSender $sender
	 * @param $message
	 * @param bool $center
	 * @param bool $border
	 */
	public static function sendWrappedText($sender, $message, $center = false, $border = false, $prefix = false) {
		if($prefix == true) {
			$prefix = Utils::PREFIX;
		}
		$wrapped = ($sender->getName() == "CONSOLE") ? self::wrapText($message, $center, $border, $prefix) : self::wrapPrecise($message, $center, $border, $prefix);
		$x = 0;
		foreach($wrapped as $line) {
			$sender->sendMessage($line);
			$x++;
		}
		unset($x);
	}

	/**
	 *
	 * @param CommandSender|Player $sender
	 * @param string $title
	 * @param array $list
	 */
	public static function sendList($sender, $title, $list, $page = 1) {
		$pages = ceil(count($list) / self::LIST_PER_PAGE);
		$newList = array_slice($list, ($page - 1) * self::LIST_PER_PAGE, self::LIST_PER_PAGE);
		$header = " [ " . $title . " | " . $page . "/" . $pages . " ] ";
		$num = self::MSG_LEN - strlen($header);
		$spacer = str_repeat(self::TITLE_CHAR, floor($num / 2));
		$sender->sendMessage($spacer . $header . $spacer . (str_repeat(self::TITLE_CHAR, ($num % 2))));
		if(count($newList) == 0) {
			$sender->sendMessage(self::center("No Results to display."));
		}
		foreach($newList as $value) {
			$sender->sendMessage("/> " . $value);
		}
		$sender->sendMessage(str_repeat(self::TITLE_CHAR, self::MSG_LEN));
	}

	public static function center($message, $len = self::MSG_LEN, $fillChar = self::SPACE_CHAR) {
		if(is_array($message)) {
			// Get longest line msg
			if($len == null) {
				$len = 0;
				foreach($message as $line) {
					$l = strlen(self::cleanString($line));
					$len = ($l > $len) ? $l : $len;
				}
			}
			$lines = [];
			foreach($message as $line) {
				$lines[] = self::center($line, $len, $fillChar);
			}
			return $lines;
		}
		$message = trim($message);
		$stripped = self::cleanString($message);
		$padd = ($len - strlen($stripped)) / 2;
		$leftPadding = max(ceil($padd), 1);
		$rightPadding = max(floor($padd), 1);
		return str_repeat($fillChar, $leftPadding) . $message . str_repeat($fillChar, $rightPadding);
	}

	public static function wrapPrecise($message, $center = false, $border = false, $prefix = false) {
		// Split into multiple lines
		if($prefix == true) {
			$prefix = Utils::PREFIX;
		}
		$wrapped = wordwrap((is_array($message) ? implode("\n", $message) : $message), self::MSG_LEN);
		$lines = explode("\n", $wrapped);
		$return = [];
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, ceil((self::MSG_LEN * self::CHAR_LENGTH) / self::getCharLength(self::BORDER_CHAR)));
		}
		foreach($lines as $line) {
			$return[] = ($center ? self::centerPrecise($line) : ($prefix !== false ? $prefix : "") . $line);
		}
		if($border) {
			$return[] = str_repeat(self::BORDER_CHAR, ceil((self::MSG_LEN * self::CHAR_LENGTH) / self::getCharLength(self::BORDER_CHAR)));
		}
		return $return;
	}

	public static function centerPrecise($message, $len = null) {
		if(is_array($message)) {
			// Get longest line msg
			if($len == null) {
				$len = 0;
				foreach($message as $line) {
					$l = (self::calculatePixelLength($line)) / self::CHAR_LENGTH;
					$len = ($l > $len) ? $l : $len;
				}
			}
			$lines = [];
			foreach($message as $line) {
				$lines[] = self::centerPrecise($line, $len);
			}
			return $lines;
		}
		if(strpos($message, "\n") > -1) {
			$arr = explode("\n", $message);
			return implode("\n", self::centerPrecise($arr, $len));
		}
		$message = trim($message);
		$messageLength = self::calculatePixelLength($message);
		$totalLength = $len * self::CHAR_LENGTH;
		$half = ($totalLength - $messageLength) / (2 * self::getCharLength(self::SPACE_CHAR));
		$prePadding = max(floor($half), 0);
		$newLine = ($prePadding > 0 ? str_repeat(self::SPACE_CHAR, $prePadding) : "") . $message;
		return $newLine;
	}

	public static function wrap($message, $prefix = "") {
		$wrapped = $prefix . " " . wordwrap((is_array($message) ? implode("\n", $message) : $message), self::MSG_LEN * 1.5, ($prefix !== "" ? "\n" . $prefix . " " : "\n"));
		return $wrapped;
	}

	/**
	 * Apply minecraft color codes to a string from our custom ones
	 *
	 * @param string $string
	 * @param string $symbol
	 *
	 * @return string
	 */
	public static function translateColors(string $string, string $symbol = "&") : string {
		return preg_replace("/{$symbol}([0123456789abcdefklmnor])/i", "§$1", $string);
	}

	/**
	 * Removes all minecraft color codes from a string
	 *
	 * @param string $string
	 * @param string $symbol
	 *
	 * @return string
	 */
	public static function cleanString(string $string, string $symbol = "&") : string {
		return preg_replace("/(?:{$symbol}|§)([0123456789abcdefklmnor])/i", "", $string);
	}

	/**
	 * Rainbow-ify a string
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	public static function rainbow($string) {
		$str = "";
		$col = ["4", "c", "6", "e", "a", "2", "b", "3", "1", "5", "d"];
		$string = str_replace("§", "^", $string);
		$chars = str_split($string);
		$i = 0;
		$skip = false;
		foreach($chars as $char) {
			if(ctype_alnum($char) && $char != "^" && $skip == false) {
				$str .= "§" . $col[$i];
				$i = ($i < (count($col) - 1) ? $i + 1 : 0);
			}
			$skip = false;
			if($char == "^") {
				$skip = true;
			}
			$str .= $char;
		}
		return str_replace("^", "§", $str);
	}

	public static function str_split_unicode($str, $l = 0) {
		if($l > 0) {
			$ret = [];
			$len = mb_strlen($str, "UTF-8");
			for($i = 0; $i < $len; $i += $l) {
				$ret[] = mb_substr($str, $i, $l, "UTF-8");
			}
			return $ret;
		}
		return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Calculate the length of a string in pixels
	 *
	 * @param $string
	 *
	 * @return int|mixed
	 */
	private static function calculatePixelLength($string) {
		$clean = self::cleanString($string);
		$parts = self::str_split_unicode($clean);
		$length = 0;
		foreach($parts as $part) {
			$length += self::getCharLength($part);
		}
		// +1 pixel for each bold character
		preg_match_all("/(?:&|§)l(.+?)(?:[&|§]r|$)/", $string, $matches);
		if(isset($matches[1])) {
			foreach($matches[1] as $match) {
				$cl = trim(str_replace(" ", "", self::cleanString($match)));
				$cl = preg_replace("/[^\x20-\x7E]+/", "", $cl);
				$length += strlen($cl);
			}
		}
		return $length;
	}

	/**
	 * Get the size of a character in pixels
	 *
	 * @param $char
	 *
	 * @return int|mixed
	 */
	private static function getCharLength($char) {
		return (isset(self::$charLengths[$char])) ? self::$charLengths[$char] : self::CHAR_LENGTH;
	}

}