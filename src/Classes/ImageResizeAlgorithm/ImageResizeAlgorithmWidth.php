<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

/**
 * An image resizing algorithm that resizes the image to the desired width while keeping its aspect ratio.
 * Images with a width smaller than the desired final width are not upscaled.
 */
class ImageResizeAlgorithmWidth extends ImageResizeAlgorithm implements ImageResizeAlgorithmInterface {
	/**
	 * @param int $width The desired width of the resulting image.
	 * @param int $outputImageType The imagetype of the output, one of the IMAGETYPE_* constants (https://www.php.net/manual/es/image.constants.php)
	 * @param int $jpegQuality The JPEG quality of the output when using IMAGETYPE_JPEG, ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file)
	 * @param bool $isInterlaced Whether the output image should be interlaced, when the outputImageType supports it.
	 */
	public function __construct(
		private int $width,
		protected int $outputImageType,
		protected int $jpegQuality = 90,
		protected bool $isInterlaced = true,
	) {
		parent::__construct(
			outputImageType: $outputImageType,
			jpegQuality: $jpegQuality,
			isInterlaced: $isInterlaced,
		);
	}

	public function resize(
		string $sourceFilePath,
		string $detinationFilePath,
	) {
		copy($sourceFilePath, $detinationFilePath);
	}
}
