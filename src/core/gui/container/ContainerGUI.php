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
 * Created on 20/04/2017 at 6:10 PM
 *
 */

namespace core\gui\container;

use core\CorePlayer;
use core\gui\item\GUIItem;
use pocketmine\inventory\Inventory;

interface ContainerGUI extends Inventory {

	/**
	 * Called when a player selects an item from within the GUI
	 *
	 * @param $slot
	 * @param GUIItem $item
	 * @param CorePlayer $player
	 *
	 * @return mixed
	 */
	public function onSelect($slot, GUIItem $item, CorePlayer $player);

}