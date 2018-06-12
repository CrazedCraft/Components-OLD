<?php

/**
 * CheckMessageTask.php – Components
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

namespace core\task;

use core\CorePlayer;
use core\language\LanguageManager;
use core\Main;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\plugin\PluginException;

/**
 * Check messages for blocked phrases and passwords in chat in an
 * async task so the main thread does less work ^-^
 */
class CheckMessageTask extends AsyncTask {

	/** @var string */
	protected $name;

	/** @var string */
	protected $hash;

	/** @var string */
	protected $lastMessage;

	/** @var int */
	protected $lastMessageTime;

	/** @var string */
	protected $message;

	/* Results */
	const SUCCESS = "result.success";
	const MESSAGES_SIMILAR = "result.similar";
	const CHAT_COOLDOWN = "result.cooldown";
	const CONTAINS_PASSWORD = "result.contains.password";

	public function __construct(string $name, string $hash, string $lastMessage, int $lastMessageTime, string $message) {
		$this->name = $name;
		$this->hash = $hash;
		$this->lastMessage = $lastMessage;
		$this->lastMessageTime = $lastMessageTime;
		$this->message = $message;
	}

	/**
	 * Check the message for all the things!
	 */
	public function onRun() {
		// chat filter, formatting, check for password in chat etc
		if(!LanguageManager::containsPassword($this->name, $this->message, $this->hash)) {
			if(floor(microtime(true) - $this->lastMessageTime) >= 3) {
				similar_text(strtolower($this->lastMessage), strtolower($this->message), $percent);
				if(round($percent) < 80) {
					$this->setResult(self::SUCCESS);
				} else {
					$this->setResult(self::MESSAGES_SIMILAR);
				}
			} else {
				$this->setResult(self::CHAT_COOLDOWN);
			}
		} else {
			$this->setResult(self::CONTAINS_PASSWORD);
		}
	}

	public function onCompletion(Server $server) {
		$plugin = $server->getPluginManager()->getPlugin("Components");
		if($plugin instanceof Main) {
			$player = $server->getPlayerExact($this->name);
			if($player instanceof CorePlayer) {
				$result = $this->getResult();
				switch($result) {
					case self::SUCCESS:
						$player->messageCheckCallback($this->message);
						$player->setLastMessage($this->message);
						return;
					case self::MESSAGES_SIMILAR:
						$player->sendTranslatedMessage("MESSAGES_TOO_SIMILAR", [], true);
						return;
					case self::CHAT_COOLDOWN:
						$player->sendTranslatedMessage("CHAT_COOLDOWN", [], true);
						return;
					case self::CONTAINS_PASSWORD:
						$player->sendTranslatedMessage("PASSWORD_IN_CHAT", [], true);
						return;
				}
			} else {
				$server->getLogger()->debug("Failed to complete CheckMessageTask due to user not being online! User: {$this->name}");
				return;
			}
		} else {
			$server->getLogger()->debug("Attempted to complete CheckMessageTask while Components plugin isn't enabled! User: {$this->name}");
			throw new PluginException("Components plugin isn't enabled!");
		}
	}

}