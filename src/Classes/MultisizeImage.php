<?php

namespace Cherrycake\Classes;

use Cherrycake\Modules\Errors\Errors;

/**
 * Class that represents an image that's available in multiple sizes
 */
abstract class MultisizeImage {
	/**
	 * The sizes on which the image is available, as an array like:
	 * ```php
	 * "thumbnail" => [
 	 *		"imageResizeMethod" => "maximumWidthOrHeight",
 	 *		"width" => 100,
 	 *		"height" => 100,
 	 *		"imageFormat" => "jpg",
 	 *		"isProgressive" => true,
 	 *		"jpgCompression" => 75,
 	 *		"isHd" => true
 	 * ],
 	 * "small" => []
 	 *		"imageResizeMethod" => "maximumWidthOrHeight",
 	 *		"width" => 800,
 	 *		"height" => 800,
 	 *		"imageFormat" => "jpg",
 	 *		"isProgressive" => true,
 	 *		"jpgCompression" => 90,
 	 *		"isHd" => true
 	 * ]
	 * ```
	 */
	var $sizes;
}
