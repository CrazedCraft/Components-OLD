<?php

/**
 * ServerSelectionContainer.php – Components
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

namespace core\gui\container\defaults;

use core\CorePlayer;
use core\gui\container\ChestGUI;
use core\gui\item\defaults\serverselector\ServerSelection;
use core\gui\item\GUIItem;
use core\language\LanguageUtils;
use core\Main;
use core\ui\windows\DefaultServerSelectionForm;
use core\Utils;
use pocketmine\Player;

class ServerSelectionContainer extends ChestGUI {

	const CONTAINER_ID = "server_selection_container";

	/** @var ServerSelection[] */
	protected $defaultContents = [];

	public function __construct(Main $plugin) {
		parent::__construct($plugin);

		$this->setContents($this->getDefaultContents());
	}

	protected function getDefaultContents() {
		if(empty($this->defaultContents)) {
			foreach(Utils::getJsonContents($this->getCore()->getDataFolder() . DefaultServerSelectionForm::SERVER_SELECTOR_DATA_FILE) as $display => $data) {
				$this->defaultContents[] = new ServerSelection(Utils::parseItem($data["item"]), $this, $display, $data["node"], $data["id"]);
			}

			return $this->defaultContents;
		}

		foreach($this->defaultContents as $selection) {
			$selection->updateName();
		}

		return $this->defaultContents;
	}

	public function onOpen(Player $who) {
		$this->setContents($this->getDefaultContents());
		parent::onOpen($who);
	}

	public function onSelect(int $slot, GUIItem $item, CorePlayer $player) : bool {
		$player->removeWindow($this);

		$item->onClick($player);
		return false; // don't remove the item
	}

}