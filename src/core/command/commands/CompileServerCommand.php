<?php

/**
 * CompileServerCommand.php – Components
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
use pocketmine\plugin\FolderPluginLoader;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class CompileServerCommand extends CoreConsoleCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "compile", "Compile the server and all loaded source plugins", "/compile", ["makeserver", "compileserver"]);
	}

	public function onRun(ConsoleCommandSender $console, array $args) {
		$plugin = $this->getCore();

		$path = $plugin->getServer()->getFilePath() . "builds";
		@mkdir($path);
		$path = $plugin->getServer()->getFilePath() . "builds" . DIRECTORY_SEPARATOR . date("D_M_j-H.i.s-T_Y");
		@mkdir($path);

		foreach($plugin->getServer()->getPluginManager()->getPlugins() as $plugin) {
			if($plugin->getPluginLoader() instanceof FolderPluginLoader) {
				$this->compilePlugin($console, $plugin, $path);
			}
		}

		if(strpos(\pocketmine\PATH, "phar://") === 0){
			$console->sendMessage(TextFormat::GOLD . "Skipping server phar due to not running from source...");
			return;
		}

		$this->compileServer($console, $plugin->getServer(), $path);
	}

	private function compilePlugin(ConsoleCommandSender $console, Plugin $plugin, string $buildPath) : void {
		$description = $plugin->getDescription();

		$console->sendMessage("Compiling plugin '" . $description->getFullName() . "' v" . $description->getVersion() . "...");

		$pharPath = $buildPath . DIRECTORY_SEPARATOR . $description->getName() . "_v" . $description->getVersion() . ".phar";

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

		$stub = '<?php echo "CrazedCraft ' . $description->getFullName() . ' plugin v' . $description->getVersion() . '\nThis archive was created at ' . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();';

		$reflection = new \ReflectionClass("pocketmine\\plugin\\PluginBase");
		$file = $reflection->getProperty("file");
		$file->setAccessible(true);
		$filePath = rtrim(str_replace("\\", "/", $file->getValue($plugin)), "/") . "/";

		PharUtils::buildPhar($console, $pharPath, $buildPath, $filePath, [], $metadata, $stub);
	}

	private function compileServer(ConsoleCommandSender $console, Server $server, string $buildPath) : void {
		$console->sendMessage("Compiling server phar...");

		$pharPath = $buildPath . DIRECTORY_SEPARATOR . $server->getName() . "_" . $server->getPocketMineVersion() . ".phar";

		$metadata = [
			"name" => $server->getName(),
			"version" => $server->getPocketMineVersion(),
			"minecraft" => $server->getVersion(),
			"creationDate" => time(),
			"protocol" => Info::CURRENT_PROTOCOL
		];

		$stub = '<?php require_once("phar://". __FILE__ ."/src/pocketmine/PocketMine.php");  __HALT_COMPILER();';

		$filePath = realpath(\pocketmine\PATH) . "/";
		$filePath = rtrim(str_replace("\\", "/", $filePath), "/") . "/";

		PharUtils::buildPhar($console, $pharPath, $buildPath, $filePath, ["'src'"], $metadata, $stub);
	}

}