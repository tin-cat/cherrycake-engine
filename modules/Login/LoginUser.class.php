<?php

/**
 * LoginUser
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * LoginUser
 *
 * An interface to be implemented by the App class that represents a user when interacting with the Login module
 *
 * @package Cherrycake
 * @category Modules
 */
interface LoginUser
{
	/**
	 * loadFromId
	 *
	 * Interfaced method to be implemented by the App class that represents a user when interacting with the Login module.
	 * Loads a user with the given $userId
	 *
	 * @param integer $userId The user id of the user to load
	 * @return boolean True if the user could be loaded ok, or false if the load failed, or the user does not exists.
	 */
	public function loadFromId($userId);

	/**
	 * loadFromUserIdField
	 *
	 * Interfaced method to be implemented by the App class that represents a user when interacting with the Login module.
	 * Loads a user with the given $userName. $userName is whatever is required to the user as username; normally, an email or a username.
	 *
	 * @param string $userName The username of the user to load. Usually, an email or a username.
	 * @return boolean True if the user could be loaded ok, or false if the load failed, or the user does not exists.
	 */
	public function loadFromUserNameField($userName);

	/**
	 * getEncryptedPassword
	 *
	 * Interfaced method to be implemented by the App class that represents a user when interacting with the Login module.
	 * Returns the loaded user's encrypted password to be checked by the Login module.
	 *
	 * @return string The user's encrypted password, false if doesn't has one or if the user can't login.
	 */
	public function getEncryptedPassword();

	/**
	 * Interfaced method to be implemented by the App class that represents a user when interacting with the Login module.
	 * It performs any initialization needed for the user object when it represents a successfully logged in user.
	 * @return boolean True if success, false otherwise
	 */
	public function afterLoginInit();
}