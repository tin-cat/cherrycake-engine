<?php

/**
 * Login
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2 = 0;

const LOGIN_RESULT_OK = 0;
const LOGIN_RESULT_FAILED = 1;
const LOGIN_RESULT_FAILED_UNKNOWN_USER = 2;
const LOGIN_RESULT_FAILED_WRONG_PASSWORD = 3;

const LOGOUT_RESULT_OK = 0;
const LOGOUT_RESULT_FAILED = 1;

/**
 * Login
 *
 * Implements a login/logout mechanism based on the Session module.
 *
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 *
 * Configuration example for login.config.php:
 * <code>
 * $loginConfig = [
 *  "userClassName" => "", // The name of the class that represents a user on the App. Must implement the \Cherrycake\LoginUser interface.
 *  "passwordAuthenticationMethod" => LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2, // One of the available consts for password authentication methods. LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2 by default
 *  "isLoadUserOnInit" => true, // Whether to check for a logged user and get it on this module's init sequence. Defaults to true.
 *  "sleepOnErrorSeconds" => 1 // Seconds to delay execution when wrong login specified, to make things difficult for bombing attacks
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Login extends \Cherrycake\Module {
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"isLoadUserOnInit" => true, // Whether to check for a logged user and get it on this module's init sequence. Defaults to true.
		"passwordAuthenticationMethod" => LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2, // One of the available consts for password authentication methods. LOGIN_PASSWORD_AUTHENTICATION_METHOD_PBKDF2 by default
		"sleepOnErrorSeconds" => 1  // Seconds to delay execution when wrong login specified, to make things difficult for bombing attacks
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	var $dependentCoreModules = [
		"Errors",
		"Cache",
		"Database",
		"Session"
	];

	/**
	 * @var User $user The user object that represents the logged user, object of class specified as "userClassName" in config, must implement the \Cherrycake\LoginUser interface
	 */
	public $user = false;

	/**
	 * init
	 *
	 * Initializes the module and loads the base CacheProvider class
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		if (!parent::init())
			return false;

		global $e;
		$e->loadCoreModuleClass("Login", "LoginUser");

		if ($this->getConfig("isLoadUserOnInit") && $e->Session->isSession() && $e->Session->getSessionData("userId"))
			return $this->loadUser();

		return true;
	}

	/**
	 * loadUser
	 *
	 * If a user is logged, gets it and loads it into $this->user
	 *
	 * @return bool Whether the user was successfully retrieved. False if there's no logged user, or if there's an error while retrieving it.
	 */
	function loadUser() {
		global $e;
		if (!$e->Session->isSession())
			return false;

		if (!$userId = $e->Session->getSessionData("userId"))
			return false;

		eval("\$this->user = new ".$this->getConfig("userClassName")."();");
		if (!$this->user->loadFromId($userId)) {
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Cannot load the user from the given Id",
				"errorVariables" => [
					"userId" => $userId
				]
			]);
			return false;
		}
		else
			return $this->user->afterLoginInit();
	}

	/**
	 * encryptPassword
	 *
	 * Encrypts the given password with the configured password encryption method
	 *
	 * @param string $password The password to encrypt
	 * @return mixed The encrypted string, or false if the password could not be encrypted
	 */
	function encryptPassword($password) {
		switch ($this->getConfig("passwordAuthenticationMethod")) {
			case LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2:
				$pbkdf2 = new \Cherrycake\Pbkdf2;
				return $pbkdf2->createHash($password);
				break;
			default:
				return false;
		}
	}

	/**
	 * checkPassword
	 *
	 * Checks the given password against the given encrypted password with the configured encryption method
	 *
	 * @param string $passwordToCheck The plain password to check
	 * @param string $encryptedPassword The encrypted password to check against
	 * @return boolean True if password is correct, false otherwise
	 */
	function checkPassword($passwordToCheck, $encryptedPassword) {
		switch ($this->getConfig("passwordAuthenticationMethod")) {
			case LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2:
				$pbkdf2 = new \Cherrycake\Pbkdf2;
				return $pbkdf2->checkPassword($passwordToCheck, $encryptedPassword);
				break;
			default:
				return false;
		}
	}

	/**
	 * isLogged
	 *
	 * Determines whether there is a logged user or not
	 *
	 * @return bool True if there is a logged user, false otherwise.
	 */
	function isLogged() {
		if ($this->user !== false)
			return true;
		else
			return false;
	}

	/**
	 * doLogin
	 *
	 * Logs in the user with the given $userName and $password.
	 * Locates the user on the database and checks the given password against the stored one.
	 *
	 * @param string $userName The string field that uniquely identifies the user on the database, the one used by the user to login. Usually, an email or a username.
	 * @param string $password The password entered by the user to login.
	 * @return integer One of the LOGIN_RESULT_* consts
	 */
	function doLogin($userName, $password) {
		eval("\$user = new ".$this->getConfig("userClassName")."();");

		if (!$user->loadFromUserNameField($userName)) {
			if ($this->getConfig("sleepOnErrorSeconds"))
				sleep($this->getConfig("sleepOnErrorSeconds"));
			return LOGIN_RESULT_FAILED_UNKNOWN_USER;
		}

		if (!$this->checkPassword($password, $user->getEncryptedPassword())) {
			if ($this->getConfig("sleepOnErrorSeconds")) {
				sleep($this->getConfig("sleepOnErrorSeconds"));
			}
			return LOGIN_RESULT_FAILED_WRONG_PASSWORD;
		}

		if (!$this->logInUserId($user->id))
			return LOGIN_RESULT_FAILED;
		$this->loadUser();
		return LOGIN_RESULT_OK;
	}

	/**
	 * doLogout
	 *
	 * Logs out the user
	 *
	 * @return integer One of the LOGOUT_RESULT_* consts
	 */
	function doLogout() {
		return $this->logoutUser();
	}

	/**
	 * Logs in the current client as the specified $userId
	 * @param integer $userId The user id to log in
	 * @return bool Whether the session info to log the user could be set or not
	 */
	function loginUserId($userId) {
		global $e;
		return $e->Session->setSessionData("userId", $userId);
	}

	/**
	 * Logs out the current client
	 */
	function logoutUser() {
		global $e;
		if (!$e->Session->removeSessionData("userId"))
			return LOGOUT_RESULT_FAILED;
		return LOGOUT_RESULT_OK;
	}

	/**
	 * debug
	 *
	 * @return string Debug info about the current login
	 */
	function debug() {
	}

}