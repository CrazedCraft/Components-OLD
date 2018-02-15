<?php

/**
 * CompileCoreCommand.php – Components
 *
 * Copyright (C) 2015-2018 Jack Noordhuis
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Jack Noordhuis
 *
 */

declare(strict_types=1);

namespace core\command\commands;

use core\command\CoreConsoleCommand;
use core\Main;
use core\util\PharUtils;
use pocketmine\command\ConsoleCommandSender;

class CompileCoreCommand extends CoreConsoleCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "compilecore", "Compile the components plugin", "/compilecore", ["makecore"]);
	}

	public function onRun(ConsoleCommandSender $console, array $args) {
		$plugin = $this->getCore();
		$description = $plugin->getDescription();

		$path = $plugin->getServer()->getFilePath() . "builds";
		@mkdir($path);
		$path = $plugin->getServer()->getFilePath() . "builds" . DIRECTORY_SEPARATOR . date("D_M_j-H.i.s-T_Y");
		@mkdir($path);
		$pharPath = $path . DIRECTORY_SEPARATOR . $description->getName() . "_v" . $description->getVersion() . ".phar";

		$metadata = [
			"name" => $description->getName(),
			"version" => $description->getVersion(),
			"main" => $description->getMain(),
			"api" => $description->getCompatibleApis(),
			"depend" => $description->getDepend(),
			"description" => $description->getDescription(),
			"authors" => $description->getAuthors(),
			"website" => $description->getWebsite(),
			"creationDate" => time()
		];

		$stub = '<?php echo "CrazedCraft Components plugin v' . $description->getVersion() . '\nThis archive was created at ' . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();';

		$reflection = new \ReflectionClass("pocketmine\\plugin\\PluginBase");
		$file = $reflection->getProperty("file");
		$file->setAccessible(true);
		$filePath = rtrim(str_replace("\\", "/", $file->getValue($plugin)), "/") . "/";

		PharUtils::buildPhar($console, $pharPath, $path, $filePath, [], $metadata, $stub);
	}

}