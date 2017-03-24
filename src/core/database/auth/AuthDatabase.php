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
 * Created on 12/07/2016 at 9:13 PM
 *
 */

namespace core\database\auth;

/**
 * All classes that implement an auth database MUST implement this class
 */
interface AuthDatabase {

	public function register($name, $password, $email);

	public function login($name);

	public function update($name, array $args);

	public function unregister($name);

	public function changePassword($name, $hash);

	public function close();

}