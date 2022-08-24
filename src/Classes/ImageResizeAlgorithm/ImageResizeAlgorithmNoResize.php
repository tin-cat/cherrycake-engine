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
		$destinationFilePath = $this->renameDestinationFilePath($destinationFilePath);
		$imageData = $this->loadImage($sourceFilePath);
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
}
