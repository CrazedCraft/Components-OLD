<?php

/**
 * ServerSelector.php – Components
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

namespace core\gui\item\defaults\serverselector;

use core\CorePlayer;
use core\gui\container\defaults\ServerSelectionContainer;
use core\gui\item\GUIItem;
use core\language\LanguageUtils;
use core\Main;
use core\ui\windows\DefaultServerSelectionForm;
use pocketmine\item\Item;
use pocketmine\network\protocol\Info;

class ServerSelector extends GUIItem {

	const GUI_ITEM_ID = "server_selector";

	public function __construct($parent = null) {
		parent::__construct(Item::get(Item::COMPASS, 0, 1), $parent);
		$this->setCustomName(LanguageUtils::translateColors("&l&dServer Selector"));
		$this->setPreviewName($this->getName());
	}

	public function onClick(CorePlayer $player) {
		if($player->getPlayerProtocol() >= Info::PROTOCOL_120) {
			$player->showModal(Main::getInstance()->getUIManager()->getForm(DefaultServerSelectionForm::FORM_UI_ID));
		} else {
			$player->openGuiContainer($player->getCore()->getGuiManager()->getContainer(ServerSelectionContainer::CONTAINER_ID));
		}
	}

	public function getCooldown() : int {
		return 5; // in seconds
	}

}