<?php

namespace Cherrycake\Modules\Login;

use Cherrycake\Engine;

/**
 * Provides a standardized method for implementing secure user identification workflows for web apps.
 */
class Login extends \Cherrycake\Module {

	const LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2 = 0;

	const RESULT_OK = 0;
	const RESULT_FAILED = 1;
	const RESULT_FAILED_UNKNOWN_USER = 2;
	const RESULT_FAILED_WRONG_PASSWORD = 3;

	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"userClassName" => "\App\User", // The name of the app class that represents a user on the App. Must implement the \Cherrycake\Modules\LoginUser interface.
		"isLoadUserOnInit" => true, // Whether to check for a logged user and get it on this module's init sequence. Defaults to true.
		"passwordAuthenticationMethod" => \Cherrycake\Modules\Login\Login::LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2, // One of the available consts for password authentication methods. \Cherrycake\Modules\Login\LOGIN_PASSWORD_AUTHENTICATION_METHOD_PBKDF2 by default
		"sleepOnErrorSeconds" => 1  // Seconds to delay execution when a wrong login is requested, to make things difficult for bombing attacks
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Errors",
		"Cache",
		"Database",
		"Session"
	];

	/**
	 * @var LoginUser $user The user object that represents the logged user, object of class specified as "userClassName" in config, must implement the \Cherrycake\Modules\LoginUser interface
	 */
	public LoginUser $user;

	/**
	 * Initializes the module and loads the base CacheProvider class
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
		if (!parent::init())
			return false;

		if ($this->getConfig("isLoadUserOnInit") && Engine::e()->Session->isSession() && Engine::e()->Session->getSessionData("userId"))
			return $this->loadUser();

		return true;
	}

	/**
	 * If a user is logged, gets it and loads it into $this->user
	 * @return bool Whether the user was successfully retrieved. False if there's no logged user, or if there's an error while retrieving it.
	 */
	function loadUser(): bool {
		if (!Engine::e()->Session->isSession())
			return false;

		if (!$userId = Engine::e()->Session->getSessionData("userId"))
			return false;

		eval("\$this->user = new ".$this->getConfig("userClassName")."();");
		if (!$this->user->loadFromId($userId)) {
			// If the user could not be loaded, logout gracefully
			$this->logoutUser();
			return true;
		}
		else
			return $this->user->afterLoginInit();
	}

	/**
	 * Encrypts the given password with the configured password encryption method
	 * @param string $password The password to encrypt
	 * @return string|bool The encrypted string, or false if the password could not be encrypted
	 */
	function encryptPassword(string $password): string|bool {
		switch ($this->getConfig("passwordAuthenticationMethod")) {
			case \Cherrycake\Modules\Login\Login::LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2:
				$pbkdf2 = new \Cherrycake\Pbkdf2;
				return $pbkdf2->createHash($password);
				break;
			default:
				return false;
		}
	}

	/**
	 * Checks the given password against the given encrypted password with the configured encryption method
	 * @param string $passwordToCheck The plain password to check
	 * @param string $encryptedPassword The encrypted password to check against
	 * @return boolean True if password is correct, false otherwise
	 */
	function checkPassword(string $passwordToCheck, string $encryptedPassword): bool {
		switch ($this->getConfig("passwordAuthenticationMethod")) {
			case \Cherrycake\Modules\Login\Login::LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2:
				$pbkdf2 = new \Cherrycake\Pbkdf2;
				return $pbkdf2->checkPassword($passwordToCheck, $encryptedPassword);
				break;
			default:
				return false;
		}
	}

	/**
	 * Checks whether there is a logged user or not
	 * @return bool True if there is a logged user, false otherwise.
	 */
	function isLogged(): bool {
		return isset($this->user);
	}

	/**
	 * Checks the given credentials in the database, and logs in the user if they're found to be correct.
	 * @param string $userName The string field that uniquely identifies the user on the database, the one used by the user to login. Usually, an email or a username.
	 * @param string $password The password entered by the user to login.
	 * @return int One of the \Cherrycake\Modules\Login\Login::RESULT_* consts
	 */
	function doLogin(string $userName, string $password): int {
		eval("\$user = new ".$this->getConfig("userClassName")."();");

		if (!$user->loadFromUserNameField($userName)) {
			if ($this->getConfig("sleepOnErrorSeconds"))
				sleep($this->getConfig("sleepOnErrorSeconds"));
			return \Cherrycake\Modules\Login\Login::RESULT_FAILED_UNKNOWN_USER;
		}

		if (!$this->checkPassword($password, $user->getEncryptedPassword())) {
			if ($this->getConfig("sleepOnErrorSeconds")) {
				sleep($this->getConfig("sleepOnErrorSeconds"));
			}
			return \Cherrycake\Modules\Login\Login::RESULT_FAILED_WRONG_PASSWORD;
		}

		if (!$this->logInUserId($user->id))
			return \Cherrycake\Modules\Login\Login::RESULT_FAILED;
		$this->loadUser();
		return \Cherrycake\Modules\Login\Login::RESULT_OK;
	}

	/**
	 * Logs out the user
	 * @return int One of the \Cherrycake\Modules\Login\Login::RESULT_* consts
	 */
	function doLogout(): int {
		return $this->logoutUser();
	}

	/**
	 * Logs in the current user as the specified $userId
	 * @param int $userId The user id to log in
	 * @return bool Whether the session info to log the user could be set or not
	 */
	function loginUserId(int $userId): bool {
		return Engine::e()->Session->setSessionData("userId", $userId);
	}

	/**
	 * Logs out the current user
	 */
	function logoutUser() {
		if (!Engine::e()->Session->removeSessionData("userId"))
			return \Cherrycake\Modules\Login\Login::RESULT_FAILED;
		return \Cherrycake\Modules\Login\Login::RESULT_OK;
	}

	/**
	 * @return string Debug info about the current login
	 */
	function debug(): string {}

}
