<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

/**
 * An image resizing algorithm that resizes the image to the desired width while keeping its aspect ratio.
 * Images with a width smaller than the desired final width are not upscaled.
 */
class ImageResizeAlgorithmNoResize implements ImageResizeAlgorithm {
	public function resize(
		string $sourceFilePath,
		string $detinationFilePath,
	) {
		copy($sourceFilePath, $detinationFilePath);
	}
}
