<?php

namespace Cherrycake\Login;

/**
 * An abstract class to be extended by the App class that represents a user when interacting with the Login module
 *
 * @package Cherrycake
 * @category Modules
 */
abstract class LoginUser extends \Cherrycake\Item {
	/**
	 * @var $userNameFieldName The field name that holds the username user to identify this user in the Login process
	 */
	protected $userNameFieldName = false;
	/**
	 * @var $encryptedPasswordFieldName The field name that holds the encrypted password to identify this user in the Login process
	 */
	protected $encryptedPasswordFieldName = false;

	/**
	 * Loads a user with the given $userName. $userName is whatever is required to the user as username; normally, an email or a username.
	 *
	 * @param string $userName The username of the user to load. Usually, an email or a username.
	 * @return boolean True if the user could be loaded ok, or false if the load failed, or the user does not exists.
	 */
	public function loadFromUserNameField($userName) {
		return $this->loadFromId($userName, $this->userNameFieldName);
	}

	/**
	 * Returns the loaded user's encrypted password to be checked by the Login module.
	 *
	 * @return string The user's encrypted password, false if doesn't has one or if the user can't login.
	 */
	public function getEncryptedPassword() {
		return $this->{$this->encryptedPasswordFieldName};
	}

	/**
	 * Performs any initialization needed for the user object when it represents a successfully logged in user.
	 *
	 * @return boolean True if success, false otherwise
	 */
	public function afterLoginInit() { return true; }
}
