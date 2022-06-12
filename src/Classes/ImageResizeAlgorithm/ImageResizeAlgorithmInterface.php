<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

interface ImageResizeAlgorithmInterface {
	/**
	 * @param string $sourceFilePath The full file path of the image to user as source
	 * @param string $destinationFilePath The full file path of the destination image
	 * @throws UnrecognizedFileTypeException if the provided source image is not recognized as a file Type
	 * @throws UnsupportedOutputFileTypeException if the required output file type is not supported
	 */
	public function resize(
		string $sourceFilePath,
		string $destinationFilePath,
	);
}
