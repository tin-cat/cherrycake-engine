<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

/**
 * An image resizing algorithm that resizes the image to the desired width while keeping its aspect ratio.
 * Images with a width smaller than the desired final width are not upscaled.
 */
class ImageResizeAlgorithmWidth extends ImageResizeAlgorithm implements ImageResizeAlgorithmInterface {
	/**
	 * @param int $width The desired width of the resulting image.
	 * @param bool $isUpscale Whether to upscale the image if the desired width is greater than the source width. Set it to false to avoid interpolation.
	 * @param int $outputImageType The imagetype of the output, one of the IMAGETYPE_* constants (https://www.php.net/manual/es/image.constants.php)
	 * @param int $jpegQuality The JPEG quality of the output when using IMAGETYPE_JPEG, ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file)
	 * @param bool $isInterlaced Whether the output image should be interlaced, when the outputImageType supports it.
	 */
	public function __construct(
		private int $width,
		private bool $isUpscale = false,
		...$parameters
	) {
		parent::__construct(...$parameters);
	}

	public function resize(
		string $sourceFilePath,
		string $destinationFilePath,
	) {
		$imageData = $this->loadImage($sourceFilePath);

		// If isUpscale is deactivated and the desired width is greater than the source width, or if the desired width is equal to the source width, do not resize
		if (
			($this->isUpscale && $this->width > $imageData['width'])
			||
			$this->width === $imageData['width']
		) {
			// If the source image is the same type as the destination image, simply copy the file
			if ($imageData['type'] === $this->outputImageType) {
				copy($sourceFilePath, $destinationFilePath);
			}
			else {
				// The source image is of a different type than the destination image, store it without any processing
				$this->storeImage(
					$imageData['gdImage'],
					$destinationFilePath,
				);
			}
		}

		$height = floor(($this->width * $imageData['height']) / $imageData['width']);
		$image = imageCreateTrueColor($this->width, $height);
		imagecopyresampled(
			dst_image: $image,
			src_image: $imageData['gdImage'],
			dst_x: 0,
			dst_y: 0,
			src_x: 0,
			src_y: 0,
			dst_width: $this->width,
			dst_height: $height,
			src_width: $imageData['width'],
			src_height: $imageData['height']
		);
		$this->storeImage(
			image: $image,
			destinationFilePath: $destinationFilePath,
		);
	}
}
