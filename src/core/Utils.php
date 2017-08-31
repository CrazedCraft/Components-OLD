<?php

/**
 * CrazedCraft Network Components
 *
 * Copyright (C) 2016 CrazedCraft Network
 *
 * This is private software, you cannot redistribute it and/or modify any way
 * unless otherwise given permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author JackNoordhuis
 *
 * Created on 22/05/2016 at 4:20 PM
 *
 */

namespace core;

use core\language\LanguageUtils;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class Utils {

	const PREFIX = TF::BOLD . TF::GOLD . "CC" . TF::RESET . TF::YELLOW . "> " . TF::RESET;
	const STAFF_PREFIX = TF::GRAY . "[" . TF::AQUA . "STAFF" . TF::GRAY . "] " . TF::RESET;

	/**
	 * Get a vector instance from a string
	 *
	 * @param string $string
	 * @return Vector3
	 */
	public static function parseVector(string $string) {
		$data = explode(",", str_replace(" ", "", $string));
		return new Vector3($data[0], $data[1], $data[2]);
	}

	/**
	 * Get a position instance from a string
	 *
	 * @param string $string
	 * @return Position|Vector3
	 */
	public static function parsePosition(string $string) {
		$data = explode(",", str_replace(" ", "", $string));
		$level = Server::getInstance()->getLevelByName($data[3]);
		if($level instanceof Level) {
			return new Position($data[0], $data[1], $data[2], $level);
		}
		return self::parseVector($string);
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
		return LanguageUtils::translateColors($string, $symbol);
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
		return LanguageUtils::cleanString($string, $symbol);
	}

	/**
	 * Replaces all in a string spaces with -
	 *
	 * @param $string
	 * @return mixed
	 */
	public static function stripSpaces($string) {
		return str_replace(" ", "_", $string);
	}

	/**
	 * Strip all white space in a string
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function stripWhiteSpace(string $string) {
		$string = preg_replace('/\s+/', "", $string);
		$string = preg_replace('/=+/', '=', $string);
		return $string;
	}

	/**
	 * Center a line of text based around the length of another line
	 *
	 * @param $toCentre
	 * @param $checkAgainst
	 *
	 * @return string
	 */
	public static function centerText($toCentre, $checkAgainst) {
		if(strlen($toCentre) >= strlen($checkAgainst)) {
			return $toCentre;
		}

		$times = floor((strlen($checkAgainst) - strlen($toCentre)) / 2);
		return str_repeat(" ", ($times > 0 ? $times : 0)) . $toCentre;
	}

	/**
	 * @param int $time
	 *
	 * @return string
	 */
	public static function getTimeString(int $time) {
		if($time <= 0) {
			return "0 seconds";
		}
		$min = (int) gmdate("i", $time);
		$sec = (int) gmdate("s", $time);
		return rtrim(($min > 0 ? $min . "minute" . ($min == 1 ? "," : "s,") : "") . ($sec > 0 ? $sec . " second" . ($sec == 1 ? "" : "") : ""), ",");

	}

	/**
	 * @param $uuid
	 *
	 * @return null|\pocketmine\Player
	 */
	public static function getPlayerByUUID($uuid) {
		$uuid = str_replace("-", "", strtolower($uuid));
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			if(str_replace("-", "", strtolower($player->getUniqueId()->toString())) == $uuid) {
				return $player;
			}
		}
		return null;
	}

	/**
	 * Send a 'ghost' block to a player
	 *
	 * @param CorePlayer $player
	 * @param Vector3 $pos
	 * @param $id
	 * @param $damage
	 */
	public static function sendBlock(CorePlayer $player, Vector3 $pos, $id, $damage) {
		$pk = new UpdateBlockPacket();
		$pk->records[] = [$pos->x, $pos->z, $pos->y, $id, $damage, UpdateBlockPacket::FLAG_PRIORITY];
		$player->dataPacket($pk);
	}

	/**
	 * Sends a message to all online staff members
	 *
	 * @param string $message
	 */
	public static function broadcastStaffMessage(string $message) {
		$message = self::STAFF_PREFIX . LanguageUtils::translateColors($message);
		foreach(Main::getStaffNames() as $name) {
			$p = Server::getInstance()->getPlayer($name);
			if($p instanceof CorePlayer and $p->isOnline()) {
				$p->sendMessage($message);
			}
		}
	}

	/**
	 * Return the stack trace
	 *
	 * @param int $start
	 * @param null $trace
	 *
	 * @return array
	 */
	public static function getTrace($start = 1, $trace = null) {
		if($trace === null) {
			if(function_exists("xdebug_get_function_stack")) {
				$trace = array_reverse(xdebug_get_function_stack());
			} else {
				$e = new \Exception();
				$trace = $e->getTrace();
			}
		}
		$messages = [];
		$j = 0;
		for($i = (int)$start; isset($trace[$i]); ++$i, ++$j) {
			$params = "";
			if(isset($trace[$i]["args"]) or isset($trace[$i]["params"])) {
				if(isset($trace[$i]["args"])) {
					$args = $trace[$i]["args"];
				} else {
					$args = $trace[$i]["params"];
				}
				foreach($args as $name => $value) {
					$params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . @strval($value)) . ", ";
				}
			}
			$messages[] = "#$j " . (isset($trace[$i]["file"]) ? ($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" or $trace[$i]["type"] === "->") ? "->" : "::") : "") . $trace[$i]["function"] . "(" . substr($params, 0, -2) . ")";
		}
		return $messages;
	}

	/**
	 * Uses SHA-512 [http://en.wikipedia.org/wiki/SHA-2] and Whirlpool [http://en.wikipedia.org/wiki/Whirlpool_(cryptography)]
	 *
	 * Both of them have an output of 512 bits. Even if one of them is broken in the future, you have to break both
	 * of them at the same time due to being hashed separately and then XORed to mix their results equally.
	 *
	 * @param string $salt
	 * @param string $password
	 *
	 * @return string[128] hex 512-bit hash
	 */
	public static function hash($salt, $password) {
		$salt = strtolower($salt); // temp fix for password in chat check :p
		return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
	}

}