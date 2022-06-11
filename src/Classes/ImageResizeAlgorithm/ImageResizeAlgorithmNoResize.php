<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

/**
 * An image resizing algorithm that does not resize the image at all
 */
class ImageResizeAlgorithmNoResize implements ImageResizeAlgorithm {
	public function resize(
		string $sourceFilePath,
		string $detinationFilePath,
	) {
		copy($sourceFilePath, $detinationFilePath);
	}
}
