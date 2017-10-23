<?php

/**
 * UIManager.php – Components
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

namespace core\ui;

use core\Main;
use core\util\traits\CorePluginReference;
use core\ui\windows\DefaultServerSelectionForm;
use pocketmine\customUI\CustomUI;

class UIManager {

	use CorePluginReference;

	/** @var CustomUI[] */
	private $formPool = [];

	public function __construct(Main $plugin) {
		$this->setCore($plugin);

		$this->registerDefaults();
	}

	protected function registerDefaults() {
		$this->registerForm(new DefaultServerSelectionForm($this->getCore()), DefaultServerSelectionForm::FORM_UI_ID);
	}

	/**
	 * @param string $id
	 *
	 * @return null|CustomUI
	 */
	public function getForm(string $id) {
		if($this->formExists($id)) {
			return clone $this->formPool[$id];
		}
		return null;
	}

	/**
	 * @param CustomUI $form
	 * @param string $id
	 * @param bool $overwrite
	 *
	 * @return bool
	 * @throws \ErrorException
	 */
	public function registerForm(CustomUI $form, string $id, bool $overwrite = false) {
		if(!$this->formExists($id) or $overwrite) {
			$this->formPool[$id] = $form;
			return true;
		}

		throw new \ErrorException("Attempted to overwrite existing form!");
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function formExists(string $id) {
		return isset($this->formPool[$id]) and $this->formPool[$id] instanceof CustomUI;
	}

}