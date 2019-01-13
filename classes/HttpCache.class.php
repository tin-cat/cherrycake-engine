<?php

/**
 * HttpCache
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * HttpCache
 *
 * Class that sends HTTP Cache-related headers to let the client browser recognize cached elements
 *
 * @package Cherrycake
 * @category Classes
 * @author Jasper <http://stackoverflow.com/users/1714705/jasper>
 */
class HttpCache
{
	/**
	 * Init
	 *
	 * Method to call statically to perform Http cache control and send the proper headers
	 *
	 * @param int $lastModifiedTimestamp The timestamp to mark this request with, i.e: Last modified timestamp
	 * @param int $maxAge The maximum age of this request in seconds, defaults to CACHE_TTL_LONGEST
	 */
	public static function Init($lastModifiedTimestamp, $maxAge = false)
	{
		if (!$maxAge)
			$maxAge = \Cherrycake\Modules\CACHE_TTL_LONGEST;

		if (self::IsModifiedSince($lastModifiedTimestamp))
			self::SetLastModifiedHeader($lastModifiedTimestamp, $maxAge);
		else
			self::SetNotModifiedHeader($maxAge);
	}

	private static function IsModifiedSince($lastModifiedTimestamp)
	{
		$allHeaders = self::getAllHeaders();

		if (array_key_exists("If-Modified-Since", $allHeaders))
		{
			$gmtSinceDate = $allHeaders["If-Modified-Since"];
			$sinceTimestamp = strtotime($gmtSinceDate);

			// Can the browser get it from the cache?
			if ($sinceTimestamp != false && $lastModifiedTimestamp <= $sinceTimestamp)
				return false;
		}

		return true;
	}

	private static function SetNotModifiedHeader($maxAge)
	{
		// Set headers
		header("HTTP/1.1 304 Not Modified", true);
		header("Cache-Control: public, max-age=$maxAge", true);
		die();
	}

	private static function SetLastModifiedHeader($lastModifiedTimestamp, $maxAge)
	{
		$date = gmdate("D, j M Y H:i:s", $lastModifiedTimestamp)." GMT";

		// Set headers
		header("HTTP/1.1 200 OK", true);
		header("Cache-Control: public, max-age=$maxAge", true);
		header("Last-Modified: $date", true);
	}

	private static function getAllHeaders() {
		$headers = ''; 
		foreach ($_SERVER as $name => $value) 
			if (substr($name, 0, 5) == 'HTTP_')
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
		return $headers;
	}
}