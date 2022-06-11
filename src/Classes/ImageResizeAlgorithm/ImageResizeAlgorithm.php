<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

interface ImageResizeAlgorithm {
	/**
	 * @param string $sourceFilePath The full file path of the image to user as source
	 * @param string $destinationFilePath The full file path of the destination image
	 */
	public function resize(
		string $sourceFilePath,
		string $detinationFilePath,
	);
}
