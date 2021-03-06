<?php

/**
 * CoreDatabaseManager.php – Components
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

namespace core\database;

use core\database\request\MySQLDatabaseRequest;
use core\database\task\AsyncDatabaseRequestExecutor;
use core\database\task\DatabaseRequestExecutor;
use core\database\task\DatabaseRequestScheduler;
use core\util\traits\CorePluginReference;

class CoreDatabaseManager extends DatabaseManager {

	/** @var int */
	private $requestBatchThrottle = 4;

	/** @var MySQLCredentials[] */
	private $credentialsPool = [];

	/** @var MySQLDatabaseRequest[] */
	private $requestPool = [];

	/** @var DatabaseRequestScheduler */
	private $requestScheduler;

	/** @var bool */
	private $closed = false;

	/**
	 * Load up all the databases
	 */
	protected function init() {
		$this->requestBatchThrottle = $this->getCore()->getSettings()->getNested("settings.request-batch-throttle");
		$this->addCredentials(MySQLCredentials::fromArray($this->getCore()->getSettings()->getNested("settings.database")), "main");
		$this->requestScheduler = new DatabaseRequestScheduler($this);
	}

	/**
	 * Add a database credentials instance into the pool
	 *
	 * @param MySQLCredentials $credentials
	 * @param string $key
	 */
	public function addCredentials(MySQLCredentials $credentials, string $key) {
		$this->credentialsPool[$key] = $credentials;
	}

	/**
	 * Get a database credentials instance from the pool
	 *
	 * @param string $key
	 *
	 * @return MySQLCredentials|null
	 */
	public function getCredentials(string $key) {
		return $this->credentialsPool[$key] ?? null;
	}

	/**
	 * Check if there is a credentials instance in the pool
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasCredentials(string $key) : bool {
		return isset($this->credentialsPool[$key]);
	}

	/**
	 * Add a request to the pool
	 *
	 * @param MySQLDatabaseRequest $request
	 */
	public function pushToPool(MySQLDatabaseRequest $request) {
		$this->requestPool[] = $request;
	}

	/**
	 * Pull a request from the pool
	 *
	 * @return MySQLDatabaseRequest
	 */
	public function pullFromPool() : MySQLDatabaseRequest {
		return array_shift($this->requestPool);
	}

	/**
	 * Check if the request pool is empty
	 *
	 * @return bool
	 */
	public function poolEmpty() : bool {
		return empty($this->requestPool);
	}

	/**
	 * Process a chunk of requests from the pool
	 */
	public function processPool() {
		$requests = [];
		$count = 0;
		while($count < $this->requestBatchThrottle and !$this->poolEmpty()) {
			$requests[] = $this->pullFromPool();
			$count++;
		}

		if(!empty($requests)) { // don't spam unneeded async tasks
			$this->getCore()->getServer()->getScheduler()->scheduleAsyncTask(new AsyncDatabaseRequestExecutor($this->getCredentials("main"), $requests));
		}
	}

	/**
	 * Process all requests in the pool on the main thread
	 */
	public function processEntirePool() {
		if(!$this->poolEmpty()) {
			$requests = [];

			while(!$this->poolEmpty()) {
				$requests[] = $this->pullFromPool();
			}
			$executor = new DatabaseRequestExecutor($this->getCredentials("main"), $requests);

			$start = microtime(true);
			$executor->run();
			$runFinish = microtime(true);
			$executor->onCompletion($this->getCore()->getServer());
			$finish = microtime(true);

			$this->getCore()->getLogger()->info("Flushed request pool in " . round($total = $finish - $start, 3) . "s!");
			$this->getCore()->getLogger()->debug("Run time: " . round($run = $runFinish - $start, 3) . "s");
			$this->getCore()->getLogger()->debug("Complete time: " . round($run - $total, 3) . "s");
		}
	}

	public function close() : bool {
		if(parent::close()) {
			unset($this->credentialsPool);
			return true;
		}
		return false;
	}

	public function isClosed() : bool {
		return $this->closed;
	}

}