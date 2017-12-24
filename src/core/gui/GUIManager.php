<?php

/**
 * GUIManager.php – Components
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

namespace core\gui;

use core\gui\container\ContainerGUI;
use core\gui\container\defaults\ServerSelectionContainer;
use core\Main;
use core\util\traits\CorePluginReference;

class GUIManager {

	use CorePluginReference;

	/** @var ContainerGUI[] */
	private $containerPool = [];

	public function __construct(Main $plugin) {
		$this->setCore($plugin);

		$this->registerDefaults();
	}

	/**
	 * Register the default containers
	 */
	protected function registerDefaults() {
		$containers = [
			[new ServerSelectionContainer($this->getCore()), ServerSelectionContainer::CONTAINER_ID]
		];

		foreach($containers as $container) {
			try {
				$this->registerContainer($container[0], $container[1]);
			} catch(\ErrorException $e) {
				$this->getCore()->getLogger()->debug("Failed to register container " . (new \ReflectionObject($container[1]))->getShortName() . ": " . $e->getMessage());
			}
		}
	}

	/**
	 * @param string $id
	 *
	 * @return ContainerGUI|null
	 */
	public function getContainer(string $id) {
		if($this->containerExists($id)) {
			return clone $this->containerPool[$id];
		}

		return null;
	}

	/**
	 * @param ContainerGUI $container
	 * @param string $id
	 * @param bool $overwrite
	 *
	 * @return bool
	 *
	 * @throws \ErrorException
	 */
	public function registerContainer(ContainerGUI $container, string $id, bool $overwrite = false) {
		if(!$this->containerExists($id) or $overwrite) {
			$this->containerPool[$id] = $container;
			return true;
		}

		throw new \ErrorException("Attempted to overwrite existing form!");
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function containerExists(string $id) {
		return isset($this->containerPool[$id]) and $this->containerPool[$id] instanceof ContainerGUI;
	}

}