<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

use Exception;
use Cherrycake\Classes\JpegIcc;

abstract class ImageResizeAlgorithm {
	/**
	 * @var string $jpegIccProfileData The ICC profile data for JPEG images when isKeepOriginalIccProfile is used.
	 */
	private string $jpegIccProfileData;

	/**
	 * @param int $outputImageType The imagetype of the output, one of the IMAGETYPE_* constants (https://www.php.net/manual/es/image.constants.php)
	 * @param int $jpegQuality The JPEG compression quality of the output when using IMAGETYPE_JPEG, ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file)
	 * @param int $pngQuality The PNG compressoin quality of the output when using IMAGETYPE_PNG, ranges from 0 (no compression) to 9. -1 uses the zlib compression default.
	 * @param int $webpQuality The WEBP compressoin quality of the output when using IMAGETYPE_WEBP, ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file)
	 * @param bool $isInterlaced Whether the output image should be interlaced, when the outputImageType supports it.
	 * @param bool $isCorrectOrientation Whether to rotate the image to bring it to the correct orientation based on EXIF data.
	 * @param bool $isKeepOriginalIccProfile Whether to keep the original ICC color profile in generated images
	 */
	public function __construct(
		protected int $outputImageType,
		protected int $jpegQuality = 90,
		protected int $pngQuality = -1,
		protected int $webpQuality = -1,
		protected bool $isInterlaced = true,
		protected bool $isCorrectOrientation = true,
		protected bool $isKeepOriginalIccProfile = true,
	) {}

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return [];
	}

	/**
	 * Loads the specified image and returns a GdImage object along its width, height and image type.
	 * It also corrects the image orientation based on EXIF data if needed.
	 * @param string $sourceFilePath The path to the image's local file
	 * @return array An array containing the following keys
	 * - gdImage: A GdImage object that can be handled by GD
	 * - width: The width of the image
	 * - height: The height of the image
	 * - type: The image type, one of the IMAGETYPE_* constants (https://www.php.net/manual/es/image.constants.php)
	 * @throws UnrecognizedFileTypeException if the provided source image is not recognized as a file Type
	 */
	protected function loadImage(
		string $sourceFilePath,
	): array {
		if (!$imageSpecs = getimagesize($sourceFilePath))
			throw new UnrecognizedFileTypeException('The provided file could not be recognized as an image');

		list($width, $height, $type) = $imageSpecs;

		switch ($type) {
			case IMAGETYPE_GIF:
				$image = imageCreateFromGif($sourceFilePath);
				break;
			case IMAGETYPE_PNG:
				$image = imageCreateFromPng($sourceFilePath);
				break;
			case IMAGETYPE_JPEG:
				if ($this->isKeepOriginalIccProfile) {
					$jpegIcc = new JpegIcc;
					try {
						if ($jpegIcc->LoadFromJPEG($sourceFilePath))
							$this->jpegIccProfileData = $jpegIcc->GetProfile();
					} catch (Exception $e) {
						throw new ResizeException($e);
					}
				}
				$image = imagecreateFromJpeg($sourceFilePath);
				break;
			case IMAGETYPE_WEBP:
				$image = imagecreateFromWebp($sourceFilePath);
				break;
		}

		if ($this->isCorrectOrientation) {
			if (!function_exists('exif_read_data'))
				throw new Exception('EXIF extension required to retrieve orientation data');
			$exif = exif_read_data($sourceFilePath);
			if (($exif = exif_read_data($sourceFilePath)) && isset($exif["Orientation"])) {
				$orientation = $exif["Orientation"];
				if ($orientation == 6 || $orientation == 5)
					$image = imagerotate($image, 270, 0);
				if ($orientation == 3 || $orientation == 4)
					$image = imagerotate($image, 180, 0);
				if ($orientation == 8 || $orientation == 7)
					$image = imagerotate($image, 90, 0);

				if ($orientation == 5 || $orientation == 4 || $orientation == 7)
					imageflip($image, IMG_FLIP_HORIZONTAL);

				if ($orientation == 6 || $orientation == 5 || $orientation == 8 || $orientation == 7) {
					$oldWidth = $width;
					$width = $height;
					$height = $oldWidth;
				}
			}
		}

		return [
			'gdImage' => $image,
			'width' => $width,
			'height' => $height,
			'type' => $type,
		];
	}

	/**
	 * Stores a GdImage object into its final destination and type
	 * @param Object $image A GdImage object
	 * @param string $destinationFilePath The full file path of the destination image
	 */
	function storeImage(
		Object $image,
		string $destinationFilePath,
	) {
		switch ($this->outputImageType) {
			case IMAGETYPE_GIF:
				if (!function_exists('imagegif'))
					throw new UnsupportedFileTypeException('Creation of GIF images is not supported');
				imageinterlace($image, $this->isInterlaced);
				if (!imagegif(
					$image,
					$destinationFilePath,
				))
					throw new ResizeException('Error creating GIF image');
				break;
			case IMAGETYPE_PNG:
				if (!function_exists('imagepng'))
					throw new UnsupportedFileTypeException('Creation of PNG images is not supported');
				if (!imagepng(
					$image,
					$destinationFilePath,
					$this->pngQuality,
				))
					throw new ResizeException('Error creating PNG image');
				break;
			case IMAGETYPE_JPEG:
				if (!function_exists('imagejpeg'))
					throw new UnsupportedFileTypeException('Creation of JPEG images is not supported');
				imageinterlace($image, $this->isInterlaced);
				if (!imagejpeg(
					$image,
					$destinationFilePath,
					$this->jpegQuality,
				))
					throw new ResizeException('Error creating JPEG image');

				if ($this->isKeepOriginalIccProfile && $this->jpegIccProfileData) {
					$jpegIcc = new JpegIcc;
					try {
						if ($jpegIcc->setProfile($this->jpegIccProfileData))
							$jpegIcc->SaveToJPEG($destinationFilePath);
					} catch (Exception $e) {
						throw new ResizeException($e);
					}
				}
				break;
			case IMAGETYPE_WEBP:
				if (!function_exists('imagewebp'))
					throw new UnsupportedFileTypeException('Creation of WEBP images is not supported');
				if (!imagewebp(
					$image,
					$destinationFilePath,
					$this->webpQuality,
				))
					throw new ResizeException('Error creating WEBP image');
				break;
		}
	}
}
