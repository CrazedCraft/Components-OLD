<?php

/**
 * Utils.php – Components
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
 */

namespace core;

use core\language\LanguageUtils;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class Utils {

	const PREFIX = TF::BOLD . TF::GOLD . "CC" . TF::RESET . TF::YELLOW . "> " . TF::RESET;
	const STAFF_PREFIX = TF::GRAY . "[" . TF::AQUA . "STAFF" . TF::GRAY . "] " . TF::RESET;

	/** @var CorePlayer[] */
	protected static $playerLookup = [];

	/** @var LittleEndianNBTStream */
	protected static $nbtWriter;

	/**
	 * Get a vector instance from a string
	 *
	 * @param string $string
	 * @return Vector3
	 */
	public static function parseVector(string $string) {
		$data = explode(",", str_replace(" ", "", $string));
		return new Vector3(floatval($data[0]), floatval($data[1]), floatval($data[2]));
	}

	/**
	 * Get a position instance from a string
	 *
	 * @param string $string
	 * @return Position
	 */
	public static function parsePosition(string $string) {
		$data = explode(",", str_replace(" ", "", $string));
		return new Position(floatval($data[0]), floatval($data[1]), floatval($data[2]), self::parseLevel($data[3] ?? ""));
	}

	/**
	 * @param string $level
	 *
	 * @return \pocketmine\level\Level
	 */
	public static function parseLevel(string $level) {
		return Server::getInstance()->getLevelByName($level) ?? Server::getInstance()->getDefaultLevel();
	}

	/**
	 * @param string $string
	 *
	 * @return Item
	 */
	public static function parseItem(string $string) {
		$data = explode(",", str_replace(" ", "", $string));
		return Item::get($data[0] ?? 0, $data[1] ?? 0, $data[2] ?? 1);
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
	 * Add a player to the uuid lookup
	 *
	 * @param CorePlayer $player
	 */
	public static function addToUuidLookup(CorePlayer $player) {
		static::$playerLookup[$player->getUniqueId()->toString()] = $player;
	}

	/**
	 * @param string $uuid
	 *
	 * @return CorePlayer|null
	 */
	public static function lookupUuid(string $uuid) : ?CorePlayer {
		return static::$playerLookup[$uuid] ?? null;
	}

	/**
	 * Remove a player from the uuid lookup
	 *
	 * @param CorePlayer|string $player
	 */
	public static function removeFromUuidLookup($player) {
		if($player instanceof CorePlayer) {
			$player = $player->getUniqueId()->toString();
		}

		unset(static::$playerLookup[$player]);
	}

	/**
	 * @param string $uuid
	 *
	 * @return CorePlayer|null
	 */
	public static function getPlayerByUUID(string $uuid) : ?CorePlayer {
		if(($player = self::lookupUuid($uuid)) instanceof CorePlayer) {
			return $player;
		}

		/** @var CorePlayer $player */
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			if($player->getUniqueId()->toString() === $uuid) {
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
		$pk->blockRuntimeId = $id;
		$pk->dataLayerId = $damage;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->flags = UpdateBlockPacket::FLAG_PRIORITY;
		$player->dataPacket($pk);
	}

	/**
	 * @param CorePlayer $player
	 * @param Vector3 $pos
	 * @param CompoundTag $namedtag
	 */
	public static function sendTile(CorePlayer $player, Vector3 $pos, CompoundTag $namedtag) {
		if(self::$nbtWriter === null) {
			self::$nbtWriter = new LittleEndianNBTStream();
		}

		$pk = new BlockEntityDataPacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->namedtag = self::$nbtWriter->write($namedtag);;
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

	/**
	 * Make an exception serializable by flattening complex values in backtrace.
	 *
	 * @param \Exception $exception
	 */
	public static function flattenExceptionBacktrace(\Exception $exception) {
		$traceProperty = (new \ReflectionClass('Exception'))->getProperty('trace');
		$traceProperty->setAccessible(true);
		$flatten = function(&$value, $key) {
			if($value instanceof \Closure) {
				$closureReflection = new \ReflectionFunction($value);
				$value = sprintf(
					'(Closure at %s:%s)',
					$closureReflection->getFileName(),
					$closureReflection->getStartLine()
				);
			} elseif(is_object($value)) {
				$value = sprintf('object(%s)', get_class($value));
			} elseif(is_resource($value)) {
				$value = sprintf('resource(%s)', get_resource_type($value));
			}
		};
		do {
			$trace = $traceProperty->getValue($exception);
			foreach($trace as &$call) {
				array_walk_recursive($call['args'], $flatten);
			}
			$traceProperty->setValue($exception, $trace);
		} while($exception = $exception->getPrevious());
		$traceProperty->setAccessible(false);
	}

	/**
	 * Get the contents of a JSON encoded file as an array or \stdClass
	 *
	 * @param string $path
	 * @param bool $assoc
	 *
	 * @return array|\stdClass
	 */
	public static function getJsonContents(string $path, $assoc = true) {
		return json_decode(file_get_contents($path), $assoc);
	}

	public static function preg_quote_array(array $strings, string $delim = null) : array {
		return array_map(function(string $str) use ($delim) : string{ return preg_quote($str, $delim); }, $strings);
	}

}