<?php

/**
 * MySQLCredentials.php – Components
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
 * Last modified on 20/10/2017 at 5:50 PM
 *
 */

namespace core\database;

use core\database\exception\DatabaseConnectionException;

class MySQLCredentials implements \JsonSerializable {

	/** @var string */
	private $host;

	/** @var string */
	private $username;

	/** @var string */
	private $password;

	/** @var string */
	private $schema;

	/** @var int */
	private $port;

	/** @var string */
	private $socket;

	/**
	 * Construct a new credentials class from an array
	 *
	 * @param array $array
	 *
	 * @return MySQLCredentials
	 */
	public static function fromArray(array $array) {
		return new MysqlCredentials($array["host"] ?? "127.0.0.1", $array["username"] ?? "root",
			$array["password"] ?? "", $array["schema"], $array["port"] ?? 3306, $array["socket"] ?? "");
	}

	/**
	 * Constructs a new {@link MysqlCredentials} by passing parameters directly.
	 *
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $schema
	 * @param int    $port
	 * @param string $socket
	 */
	public function __construct(string $host, string $username, string $password, string $schema, int $port = 3306, string $socket = ""){
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->schema = $schema;
		$this->port = $port;
		$this->socket = $socket;
	}

	/**
	 * Get a new mysqli instance
	 *
	 * @return \mysqli
	 *
	 * @throws DatabaseConnectionException
	 */
	public function newMysqli() {
		$mysqli = @new \mysqli($this->host, $this->username, $this->password, $this->schema, $this->port, $this->socket);
		if($mysqli->connect_error) {
			throw new DatabaseConnectionException("Failed to connect to MySQL: {$mysqli->connect_error}");
		}
		return $mysqli;
	}

	/**
	 * Produces a human-readable output without leaking password
	 *
	 * @return string
	 */
	public function __toString() : string{
		return "$this->username@$this->host:$this->port/schema,$this->socket";
	}

	/**
	 * Prepares value to be var_dump()'ed without leaking password
	 *
	 * @return array
	 */
	public function __debugInfo(){
		return [
			"host" => $this->host,
			"username" => $this->username,
			"password" => str_repeat("*", strlen($this->password)),
			"schema" => $this->schema,
			"port" => $this->port,
			"socket" => $this->socket
		];
	}

	public function jsonSerialize(){
		return [
			"host" => $this->host,
			"username" => $this->username,
			"password" => $this->password,
			"schema" => $this->schema,
			"port" => $this->port,
			"socket" => $this->socket
		];
	}

}