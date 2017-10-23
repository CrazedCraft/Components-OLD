<?php

/**
 * DefaultServerSelectionForm.php – Components
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

namespace core\ui\windows;

use core\Main;
use core\Utils;
use core\ui\elements\generic\ServerSelectionButton;
use core\ui\windows\generic\ServerSelectionForm;

class DefaultServerSelectionForm extends ServerSelectionForm {

	const SERVER_SELECTOR_DATA_FILE = "data/server_selector.json";

	const FORM_UI_ID = "DEFAULT_SERVER_SELECTION_FORM";

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "&l&dServer Selector");
	}

	public function addDefaultButtons() {
		foreach(Utils::getJsonContents($this->getCore()->getDataFolder() . self::SERVER_SELECTOR_DATA_FILE) as $display => $data) {
			$this->addButton(new ServerSelectionButton($display, $data["node"], $data["id"], $data["image"]));
		}
	}

}