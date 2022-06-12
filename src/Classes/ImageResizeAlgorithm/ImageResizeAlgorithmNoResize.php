<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

/**
 * An image resizing algorithm that does not resize the image at all
 */
class ImageResizeAlgorithmNoResize extends ImageResizeAlgorithm implements ImageResizeAlgorithmInterface {
	public function resize(
		string $sourceFilePath,
		string $destinationFilePath,
	) {
		$imageData = $this->loadImage($sourceFilePath);
		$this->storeImage(
			$imageData['gdImage'],
			$destinationFilePath,
		);
	}
}
