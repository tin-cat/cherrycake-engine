<?php

namespace Cherrycake\Modules\Session;

use Cherrycake\Engine;
use Cherrycake\Modules\Cache\Cache;

/**
 * Provides a session tracking and storage mechanism.
 *
 * It uses the Cherrycake skeleton database table cherrycake_session to store sessions, and caches them on the provided CacheProvider.
 * Session ids are generated by hashing 128 random bytes with a SHA512 algorithm, giving 128 hexits that constitute an effectively unpredictable session id to avoid session hijacking.
 * Session id collisions are not checked because the probability of getting a collision is so low (1 in 16^128, or 1 in 1.3x10^154, a number _way_ bigger than the estimated number of atoms in the observable universe) that it's preferable to have that security bug instead of having to perform that additional check on each newly created session.
 * A data storage mechanism is provided to store basic information within each session. The data is stored as a serialized array on the "data" field. When requesting an update of this data, the cache is flushed so it will generate an additional database hit on the next request.
 *
 * The sessions table must be maintained often in order to remove old sessions. Otherwise, a point will be reached where all possible session ids are used and the module will remove the oldest session from the database in order to make room for the new one, effectively generating stress on the database. This will most probably happen a while after the maximum entropy point has been reached and all the stars in the universe have gone extinct.
 * The JanitorTaskSession is required to be run in order to do this maintenance work, so be sure to add it to your Janitor.config.php
 *
 * Important note: You cannot use session keys starting with "_pool_", since it's used for the pools functionality.
 */
class Session extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"sessionDatabaseProviderName" => "main", // The name of the database provider to use for storing sessions
		"sessionTableName" => "cherrycake_session", // The name of the table used to store sessions
		"sessionCacheProviderName" => "engine", // The name of the cache provider to use to store sessions and the counter of created sessions
		"sessionCacheTtl" => Cache::TTL_SHORT, // The TTL of cached sessions.
		"cachePrefix" => "Session", // The cache prefix to use when storing sessions into cache
		"cookieName" => "cherrycake", // The name of the cookie. Recommended to be changed.
		"cookiePath" => "/", // The path of the cookie. If set to "/", it will be available within the entire domain
		"cookieSecure" => false, // If set to true, the cookie will only be sent when the current request is secure (SSL)
		"cookieHttpOnly" => false, // If set to true, the cookie only will be sent when an HTTP request is made.
		"sessionDuration" => 2592000, // The duration of the session in seconds. If set to zero, the session will last until the browser is closed.
		"isSessionRenew" => true, // When set to true, the duration of the session will be renewed to a new sessionDuration every time a request is made. If set to false, the cookie will expire after sessionDuration, no matter how many times the session is requested.
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Errors",
		"Cache",
		"Database"
	];

	/**
	 * @var string $sessionId Stores the current session Id
	 */
	private $sessionId;

	/**
	 * init
	 *
	 * Initializes the module and loads the base CacheProvider class
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
		if (!parent::init())
			return false;

		if (Engine::e()->isCli())
			return true;

		if ($this->loadSessionCookie()) {
			if ($this->loadSessionData($this->sessionId)) {
				if ($this->getConfig("isSessionRenew"))
					$this->renewSessionCookie($this->sessionId);
			}
			else {
				$this->resetSessionCache($this->sessionId);
				if (!$this->removeSessionCookie())
					return false;
				if (!$this->newSession())
					return false;
			}
		}
		else {
			if (!$this->newSession())
				return false;
		}

		return true;
	}

	/**
	 * loadSessionCookie
	 *
	 * Finds the current session's cookie and loads $this->sessionId.
	 *
	 * @return boolean True if the session cookie could be retrieved, false if the session cookie does not exists (no session present).
	 */
	function loadSessionCookie() {
		if (!isset($_COOKIE[$this->getConfig("cookieName")]))
			return false;

		$this->sessionId = $_COOKIE[$this->getConfig("cookieName")];
		return true;
	}

	/**
	 * isSession
	 *
	 * @return bool Whether a session is present and loaded or not
	 */
	function isSession() {
		return isset($this->sessionId);
	}

	/**
	 * getSessionId
	 *
	 * Returns the current session id
	 *
	 * @return string The current session id
	 */
	function getSessionId() {
		return $this->sessionId;
	}

	/**
	 * newSession
	 *
	 * Creates a new session, sends the session cookie and stores the session into the DB
	 *
	 * @return boolean Whether the session could be created or not
	 */
	function newSession() {

		$sessionId = $this->generateNewSessionId();

		// Create session in DB
		$databaseProviderName = $this->getConfig("sessionDatabaseProviderName");
		$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
			"insert into ".$this->getConfig("sessionTableName")." (id, creationDate, ip, browserString, data) values (?, ?, ?, ?, null)",
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => $sessionId
				],
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
					"value" => time()
				],
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_IP,
					"value" => $this->getClientIp()
				],
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => $this->getClientBrowserString()
				],
			]
		);

		if (!$result) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Could not create the session into the DB"
			);
			return false;
		}

		// Send session cookie
		if (!$this->sendSessionCookie($sessionId))
			return false;

		$this->sessionId = $sessionId;

		return true;
	}

	/**
	 * @return string The client's IP
	 */
	function getClientIp() {
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			return $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * getClientBrowserString
	 *
	 * @return string The client's browserstring
	 */
	function getClientBrowserString() {
		return $_SERVER["HTTP_USER_AGENT"];
	}

	/**
	 * sendSessionCookie
	 *
	 * Sends the cookie for the given session id to the client
	 *
	 * @param string $sessionId The session id
	 * @return bool Whether the cookie could be sent or not
	 */
	function sendSessionCookie($sessionId) {

		if (Engine::e()->isCli())
			return false;

		if(!setcookie(
			$this->getConfig("cookieName"),
			$sessionId,
			($this->getConfig("sessionDuration") == 0 ? 0 : time()+$this->getConfig("sessionDuration")),
			$this->getConfig("cookiePath"),
			$this->getConfig("cookieDomain"),
			$this->getConfig("cookieSecure"),
			$this->getConfig("cookieHttpOnly")
		)) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "The session cookie could not be sent"
			);
			return false;
		}

		return true;
	}

	/**
	 * renewSessionCookie
	 *
	 * Resets the session cookie for the given session id expiration time
	 *
	 * @param string $sessionId The session id
	 * @return bool Whether the cookie could be renewed or not
	 */
	function renewSessionCookie($sessionId) {
		return $this->sendSessionCookie($sessionId);
	}

	/**
	 * removeSessionCookie
	 *
	 * Removes the session cookie from the client
	 *
	 * @return bool Whether the cookie could be removed or not
	 */
	function removeSessionCookie() {

		if (Engine::e()->isCli())
			return false;

		if(!setcookie(
			$this->getConfig("cookieName"),
			false,
			($this->getConfig("sessionDuration") == 0 ? 0 : time()+$this->getConfig("sessionDuration")),
			$this->getConfig("cookiePath"),
			$this->getConfig("cookieDomain"),
			$this->getConfig("cookieSecure"),
			$this->getConfig("cookieHttpOnly")
		)) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "The session cookie could not be sent"
			);
			return false;
		}

		return true;
	}


	/**
	 * generateNewSessionId
	 *
	 * Generates a random session Id
	 *
	 * @return mixed A random new session id or false if it can't be generated
	 */
	function generateNewSessionId($attemptsCounter = 0) {
		if (!function_exists("openssl_random_pseudo_bytes")) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Session module needs function openssl_random_pseudo_bytes()"
			);
			return false;
		}

		if ($attemptsCounter > 10) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Maximum attempts to generate a unique session id had been reached"
			);
			return false;
		}

		$sessionId =  hash("sha512", openssl_random_pseudo_bytes(128));

		// Tries to find the randomized session id on the cache for added protection against (very) unprobable collisions.
		// It should be desirable to check for sessions on the DB instead of the cache, but given the extremely low probabilities of a collision, only sessions on cache are checked for improved perfomance
		if($this->isSessionExistsOnCache($sessionId))
			return $this->generateNewSessionId(++$attemptsCounter);
		else
			return $sessionId;
	}

	/**
	 * getSessionCacheKey
	 *
	 * @param string $sessionId The session Id
	 * @return string The cache key to use when accessing or storing the given session id to cache
	 */
	function getSessionCacheKey($sessionId) {
		return Cache::buildCacheKey(
			prefix: $this->getConfig("cachePrefix"),
			uniqueId: $sessionId
		);
	}

	/**
	 * isSessionExistsOnCache
	 *
	 * @param string $sessionId The session id to check for
	 * @return boolean True if the session is on the cache, false if not
	 */
	function isSessionExistsOnCache($sessionId) {
		$cacheProviderName = $this->getConfig("sessionCacheProviderName");
		return Engine::e()->Cache->$cacheProviderName->isKey($this->getSessionCacheKey($sessionId));
	}

	/**
	 * Loads the given session id stored data from the cache/database into the data hashed list cache
	 *
	 * @param string $sessionId The session id to load
	 * @return boolean True if the data could be loaded, false if not (session never existed, or has been purged)
	 */
	function loadSessionData($sessionId) {

		$cacheProviderName = $this->getConfig("sessionCacheProviderName");

		// If we already have the hashed list key for this session into cache, no need to load it from database
		if (Engine::e()->Cache->$cacheProviderName->isKey($this->getSessionCacheKey($sessionId)))
			return true;

		$databaseProviderName = $this->getConfig("sessionDatabaseProviderName");
		$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
			"select data from ".$this->getConfig("sessionTableName")." where id = ? limit 1",
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => $sessionId
				]
			]
		);

		if (!$result || !$result->isAny())
			return false;

		$row = $result->getRow();

		$data = unserialize($row->getField("data"));
		if (is_array($data)) {
			foreach ($data as $key => $value)
				Engine::e()->Cache->$cacheProviderName->listSet(
					$this->getSessionCacheKey($sessionId),
					$key,
					$value
				);
		}

		return true;
	}

	/**
	 * Checks whether a session data key has been set or not
	 * @param string $key The data key to check
	 * @return boolean True if the data key exists in the session, false if not.
	 */
	function isSessionData($key) {

		if (Engine::e()->isCli())
			return false;

		$cacheProviderName = $this->getConfig("sessionCacheProviderName");
		return Engine::e()->Cache->$cacheProviderName->listExists(
			$this->getSessionCacheKey($this->getSessionId()),
			$key
		);
	}

	/**
	 * Gets a data key from the session
	 *
	 * @param string $key The data key to retrieve
	 * @return mixed The requested value from session data
	 */
	function getSessionData($key) {

		if (Engine::e()->isCli())
			return false;

		$cacheProviderName = $this->getConfig("sessionCacheProviderName");
		return Engine::e()->Cache->$cacheProviderName->listGet(
			$this->getSessionCacheKey($this->getSessionId()),
			$key
		);
	}

	/**
	 * Stores a value with the given key in the session data. It does it both in the hashed list cache and on the database for persistence
	 *
	 * @param string $key The data key to store
	 * @param mixed $value The value to store
	 * @return bool Whether the value could be stored or not
	 */
	function setSessionData($key, $value) {

		if (Engine::e()->isCli())
			return false;

		if (!$this->isSession()) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Couldn't set session data because no session is present."
			);
			return false;
		}

		$cacheProviderName = $this->getConfig("sessionCacheProviderName");

		if (is_null($value))
			Engine::e()->Cache->$cacheProviderName->listDel(
				$this->getSessionCacheKey($this->getSessionId()),
				$key
			);
		else
			Engine::e()->Cache->$cacheProviderName->listSet(
				$this->getSessionCacheKey($this->getSessionId()),
				$key,
				$value
			);

		$databaseProviderName = $this->getConfig("sessionDatabaseProviderName");
		$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
			"update ".$this->getConfig("sessionTableName")." set data = ? where id = ? limit 1",
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => serialize(Engine::e()->Cache->$cacheProviderName->listGetAll($this->getSessionCacheKey($this->getSessionId())))
				],
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => $this->getSessionId()
				]
			]
		);

		if (!$result) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Couldn't update session data in DB"
			);
			return false;
		}

		return true;
	}

	/**
	 * Magic get method to return the an item in the session
	 * @param string $key The key of the data to get
	 * @return mixed The data. Null if data with the given key is not set.
	 */
	function __get($key) {
		return $this->getSessionData($key);
	}

	/**
	 * Magic set method to set the item $key to the given $value in the session
	 * @param string $key The key of the data to set
	 * @param mixed $value The value
	 * @return bool Whether the value could be stored or not
	 */
	function __set($key, $value) {
		return $this->setSessionData($key, $value);
	}

	/**
	 * Magic method to check if the item $key has been set in the session
	 * @param string $key The key of the data to check
	 * @param boolean True if the data exists, false otherwise
	 */
	function __isset($key) {
		return $this->isSessionData($key);
	}

	/**
	 * Magic method to remove the given key from the session.
	 * @param string $key The key of the data to remove
	 */
	function __unset($key) {
		$this->removeSessionData($key);
	}

	/**
	 * removeSessionData
	 *
	 * Removes the value with the given key from the session
	 *
	 * @param string $key The key of the stored value
	 * @return bool Whether the value could be removed or not
	 */
	function removeSessionData($key) {
		return $this->setSessionData($key, null);
	}

	/**
	 * Sets a value for a key inside a pool
	 * @param string $pool The name of the pool
	 * @param string $key The key
	 * @param mixed $value The value
	 */
	function setSessionPoolData($pool, $key, $value) {
		return $this->setSessionData("_pool_".$pool."_".$key, $value);
	}

	/**
	 * Gets the value for the key inside a pool
	 * @param string $pool The name of the pool
	 * @param string $key The key
	 * @return mixed The value
	 */
	function getSessionPoolData($pool, $key) {
		return $this->getSessionData("_pool_".$pool."_".$key);
	}

	/**
	 * Removes the given key from a pool
	 * @param string $pool The name of the pool
	 * @param string $key The key
	 * @param boolean True if the data exists, false otherwise
	 */
	function removeSessionPoolData($pool, $key) {
		return $this->removeSessionData("_pool_".$pool."_".$key);
	}

	/**
	 * Checks whether the given key exists in the specified pool
	 * @param string $pool The name of the pool
	 * @param string $key The key
	 * @return boolean Whether the key exists in the specified pool
	 */
	function isSessionPoolData($pool, $key) {
		return $this->isSessionData("_pool_".$pool."_".$key);
	}

	/**
	 * Removes all the keys in the specified pool
	 * @param string $pool The name of the pool
	 * @return bool Whether the pool could be removed or not. Returns true also if the pool didn't exist.
	 */
	function removeSessionPool($pool) {
		$cacheProviderName = $this->getConfig("sessionCacheProviderName");

		if (!Engine::e()->Cache->$cacheProviderName->isKey($this->getSessionCacheKey($this->getSessionId())))
			return true;

		// Loops through all the session keys
		$poolPrefix = "_pool_".$pool."_";
		$data = Engine::e()->Cache->$cacheProviderName->listGetAll($this->getSessionCacheKey($this->getSessionId()));
		foreach (array_keys($data) as $key) {
			if (substr($key, 0, strlen($poolPrefix)) == $poolPrefix)
				if (!$this->removeSessionData($key))
					$isAnyError = true;
		}
		return !$isAnyError;
	}

	/**
	 * Removes the session data from the hash cache for the session with the given id, effectively forcing a DB hit the next time the session is requested.
	 *
	 * @param string $sessionId The session id
	 * @return bool Whether the session cache could be resetted or not
	 */
	function resetSessionCache($sessionId) {
		$cacheProviderName = $this->getConfig("sessionCacheProviderName");
		return Engine::e()->Cache->$cacheProviderName->delete($this->getSessionCacheKey($sessionId));
	}

	/**
	 * debug
	 *
	 * @return string Debug info about the current session
	 */
	function debug(){
		if (!$this->isSession())
			return "No session.";

		$r = "<p><b>Session id:</b> ".$this->getSessionId()."</p>";

		if (is_array($this->sessionData)) {
			foreach ($this->sessionData as $key => $value)
				$r .= "<p><b>Session data key \"".$key."\":</b> ".$value."</p>";
		}

		return $r;
	}

}