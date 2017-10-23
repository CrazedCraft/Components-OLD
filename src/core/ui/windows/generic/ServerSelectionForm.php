<?php

/**
 * ServerSelectionForm.php – Components
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

namespace core\ui\windows\generic;

use core\language\LanguageUtils;
use core\Main;
use core\util\traits\CorePluginReference;
use pocketmine\customUI\windows\SimpleForm;

abstract class ServerSelectionForm extends SimpleForm {

	use CorePluginReference;

	public function __construct(Main $plugin, string $title) {
		$this->setCore($plugin);
		parent::__construct(LanguageUtils::translateColors($title), "");

		$this->addDefaultButtons();
	}

	/**
	 * Add the default buttons onto the form
	 *
	 * @return mixed
	 */
	abstract protected function addDefaultButtons();

}