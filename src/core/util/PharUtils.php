<?php

/**
 * PharUtils.php – Components
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

namespace core\util;

use core\Utils;
use pocketmine\command\CommandSender;

class PharUtils {

	public static function createPharPlugin(CommandSender $sender) : int {

	}

	public static function buildPhar(CommandSender $sender, string $pharPath, string $buildPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = \Phar::SHA1) {
		if(file_exists($pharPath)) {
			$sender->sendMessage("Phar file already exists, overwriting...");
			\Phar::unlinkArchive($pharPath);
		}

		$sender->sendMessage("Processing files...");

		//$copyPath = $buildPath . DIRECTORY_SEPARATOR . ".build";
		//@mkdir($buildPath); // create a temporary directory to pull all the required files into

		//$sender->sendMessage("Build path: " . $buildPath);

		//self::copyPath($basePath, $copyPath);

		$start = microtime(true);
		$phar = new \Phar($pharPath);
		$phar->setMetadata($metadata);
		$phar->setStub($stub);
		$phar->setSignatureAlgorithm($signatureAlgo);
		$phar->startBuffering();

		//If paths contain any of these, they will be excluded
		$excludedSubstrings = [
			"/.", //"Hidden" files, git information etc
			realpath($pharPath) //don't add the phar to itself
		];

		$regex = sprintf('/^(?!.*(%s))^%s(%s).*/i',
			implode('|', Utils::preg_quote_array($excludedSubstrings, '/')), //String may not contain any of these substrings
			preg_quote($basePath, '/'), //String must start with this path...
			implode('|', Utils::preg_quote_array($includedPaths, '/')) //... and must be followed by one of these relative paths, if any were specified. If none, this will produce a null capturing group which will allow anything.
		);

		$sender->sendMessage("Adding files...");
		$directory = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FileSystemIterator::CURRENT_AS_PATHNAME); //can't use fileinfo because of symlinks
		$iterator = new \RecursiveIteratorIterator($directory);
		$regexIterator = new \RegexIterator($iterator, $regex);

		$count = count($phar->buildFromIterator($regexIterator, $basePath));
		$sender->sendMessage("Added $count files");

		$sender->sendMessage("Checking for compressible files...");
		foreach($phar as $file => $finfo) {
			/** @var \PharFileInfo $finfo */
			if($finfo->getSize() > (1024 * 512)) {
				$sender->sendMessage("[DevTools] Compressing " . $finfo->getFilename());
				$finfo->compress(\Phar::GZ);
			}
		}
		$phar->stopBuffering();

		$sender->sendMessage(" Done in " . round(microtime(true) - $start, 3) . "s");
	}

	public static function copyPath(string $from, string $to) {
		if(is_dir($to)) {
			@unlink($to);
		}

		@mkdir($to);

		/** @var \SplFileInfo $file */
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
			var_dump($file->getFilename());
			if(substr($file->getFilename(), 0, 1) === ".") {
				continue;
			}

			$newDir = $to . self::getPathDifference($file->getPath(), $from);
			$newPath = $newDir . DIRECTORY_SEPARATOR . $file->getFilename();

			if($file->isLink() and $file->isDir()) {
				//echo "Copying symlink contents '" . $file->getFilename() . "' from '" . $file->getRealPath() . "' to '" . $newPath . "'" . PHP_EOL;
				self::copySymlinkContents($file->getRealPath(), $newPath);
			} elseif($file->isDir() or is_dir($file->getRealPath())) {
				//echo "Making directory " . $file->getFilename() . " from " . $file->getRealPath() . " to " . $newPath . PHP_EOL;
				@mkdir($newPath);
			} elseif($file->isFile()) {
				//echo "Copying " . $file->getFilename() . " from " . $file->getRealPath() . " to " . $newPath . PHP_EOL;
				if(!is_dir($newDir)) {
					@mkdir($newDir);
				}
				copy($file->getRealPath(), $newPath);
			}
		}
	}

	public static function copySymlinkContents(string $from, string $to) {
		@mkdir($to);

		/** @var \SplFileInfo $file */
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
			if(substr($file->getFilename(), 0, 1) === ".") {
				continue;
			}

			$newDir = $to . self::getCommonRelativePath($file->getPath(), $from);
			$newPath = $newDir . DIRECTORY_SEPARATOR . $file->getFilename();

			if($file->isLink() and $file->isDir()) {
				//echo "Copying recursive symlink contents '" . $file->getFilename() . "' from '" . $file->getRealPath() . "' to '" . $newPath . "'" . PHP_EOL;
				self::copySymlinkContents($newPath, $newPath);
			} elseif($file->isDir() or is_dir($file->getRealPath())) {
				//echo "Making directory " . $file->getFilename() . " from " . $file->getRealPath() . " to " . $newPath . PHP_EOL;
				@mkdir($newPath);
			} elseif($file->isFile()) {
				//echo "Copying " . $file->getFilename() . " from " . $file->getRealPath() . " to " . $newPath . PHP_EOL;
				if(!is_dir($newDir)) {
					@mkdir($newDir);
				}
				copy($file->getRealPath(), $newPath);
			}
		}
	}

	public static function getPathDifference(string $from, string $to) {
		$from = explode('/', $from);
		$to = explode('/', $to);

		$traveledPath = [];

		foreach($from as $depth => $dir) {
			if(isset($to[$depth]) and $dir !== $to[$depth]) {
				$traveledPath[] = $dir;
			}
		}

		return implode('/', $traveledPath);
	}

	public static function getCommonRelativePath(string $from, string $to) {
		return substr($from, strlen($to), strlen($to) + strlen($from));
	}

}