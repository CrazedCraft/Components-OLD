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
 * Created on 12/07/2016 at 9:11 PM
 *
 */

namespace core\entity\text;

use pocketmine\entity\Entity;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\Player;
use pocketmine\Server;

class UpdatableFloatingText extends FloatingText {

	/**
	 * @param $text
	 * @param Player[] $players
	 */
	public function update($text, array $players = []) {
		if(empty($players)) {
			$players = Server::getInstance()->getOnlinePlayers();
		}
		$this->text = $text;
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->eid;
		$pk->metadata = [
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text]
		];
		Server::getInstance()->broadcastPacket($players, $pk);
	}

}