<?php

/**
 * ResponseImagePng
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Response
 *
 * Class that represents a response to a client. Mostly used by the Output module.
 *
 * @package Cherrycake
 * @category Classes
 */
class ResponseImagePng extends Response {
	/**
	 * @var integer $contentType The content type of the response
	 */
	protected $contentType = "image/png";
}