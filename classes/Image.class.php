<?php

/**
 * Image
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Image
 *
 * Class that represents an image stored on the server.
 * * It allows for images to be stored in multiple sizes.
 * * Can create the multiple size images from a given file.
 * * All jpg images are considered with the extension ".jpg", not ".jpeg".
 * * Any resizing of animated gif images will result on losing the animation, use the "copy" resize method to preserve the original file.
 * * By specifying a fileNameObfuscationSalt, the file names (and the directory structure if fileDirectoryDeepness is set) will be obfuscated to avoid file scrapping. 
 * * * Attention: If you set fileNameObfuscationSalt, be sure to never change it. If salt ever changes, the names of the files that have been already saved to disk with a different salt won't match the ones generated with the previous salt. If you lose the original salt, you might lose the connection between a given file id and its file on disk.
 * * * If such catastrophe happens, you might still be able to recover the original file names: Since the file names are obfuscated by adding a dot and a hash to the base file name, removing this dot and hash from the file names will leave you with the original files as if they weren't obfuscated.
 * * * It's recommended that you backup the salts you use in your projects in a safe place.
 *
 * Example of a sizes configuration array:
 * <code>
 * $traceimageSizes = [
 *  "thumbnail" => [
 *      "imageResizeMethod" => "maximumWidthOrHeight",
 *      "width" => 100,
 *      "height" => 100,
 *      "imageFormat" => "jpg",
 *      "isProgressive" => true,
 *      "jpgCompression" => 75,
 *      "isHd" => true
 *  ],
 *  "small" => []
 *      "imageResizeMethod" => "maximumWidthOrHeight",
 *      "width" => 800,
 *      "height" => 800,
 *      "imageFormat" => "jpg",
 *      "isProgressive" => true,
 *      "jpgCompression" => 90,
 *      "isHd" => true
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 * @todo Implement forceWidthAndHeight on buildFromFile method
 */
class Image {
	/**
	 * @var string $fileName The fileName of the image (without extension)
	 */
	protected $fileName;

	/**
	 * @var string $fileDirectory The directory on the server where the image is stored, usually a relative path
	 */
	protected $fileDirectory;

	/**
	 * @var integer $fileDirectoryDeepness The number of subdirectories on which the directory structure is built in order to prevent too many files in a single directory. Leave to false if no subdirectory structure is used.
	 */
	protected $fileDirectoryDeepness = false;

	/**
	 * @var array $sizes The specification of available sizes for this Image, if any
	 */
	protected $sizes;

	/**
	 * @var string $imageFormatWhenNoSizes If no $sizes specified, the image file format that will be used
	 */
	protected $imageFormatWhenNoSizes;

	/**
	 * Attention: If you set fileNameObfuscationSalt, be sure to never change it. If salt ever changes, the names of the files that have been already saved to disk with a different salt won't match the ones generated with the previous salt. If you lose the original salt, you might lose the connection between a given file id and its file on disk, which would be catastrophic.
	 * If such catastrophe happens, you might still be able to recover the original file names: Since the file names are obfuscated by adding a dot and a hash to the base file name, removing this dot and hash from the file names will leave you with the original files as if they weren't obfuscated.
	 * It's recommended that you backup the salts you use in your projects in a safe place.
	 * 
	 * @var string $fileNameObfuscationSalt A salt for the hashing algorithm that will be used to obfuscate file names on disk to prevent file scrapping attacks.
	 */
	protected $fileNameObfuscationSalt = false;

	/**
	 * __construct
	 *
	 * Creates the image object filling the data by using one of the load methods provided
	 *
	 * Setup keys:
	 *
	 * * loadMethod: If specified, it loads the Item using the given method, available methods:
	 * 	- fromId: Loads the image from a disk folder structure based on the given id
	 *
	 * When "fromId" method is used, additional keys are required, see them in the loadFromId method
	 *
	 * @param array $setup Specifications on how to create the Image object
	 * @return boolean Whether the Image could be initialized ok or not
	 */
	function __construct($setup = false) {
		if (!$setup)
			return $this->init();

		if ($setup["loadMethod"])
			switch($setup["loadMethod"]) {
				case "fromId":
					return $this->loadFromId($setup["id"]);
					break;
			}

		return $this->init();
	}

	/**
	 * init
	 *
	 * Initializes the Image object
	 *
	 * @return  boolean Whether the Image could be initialized ok or not
	 */
	function init() {
		// Add Hd sizes to the sizes array
		while (list($sizeName, $sizeSetup) = each($this->sizes)) {
			if ($sizeSetup["isHd"]) {
				if ($sizeSetup["width"])
					$sizeSetup["width"] = $sizeSetup["width"] * 2;

				if ($sizeSetup["height"])
					$sizeSetup["height"] = $sizeSetup["height"] * 2;

				$hdSizes[$sizeName.".hd"] = $sizeSetup;
			}
		}
		reset($this->sizes);
		if ($hdSizes)
			$this->sizes = array_merge($this->sizes, $hdSizes);
		return true;
	}

	/**
	 * loadFromId
	 *
	 * Loads the Image object from a disk folder structure based on the given id
	 *
	 * @param integer $id The id to use
	 * @return boolean True if success, false otherwise
	 */
	function loadFromId($id) {
		$this->setFileName($id);
		return $this->init();
	}

	/**
	 * Sets the file name that will be used to save this image to disk, and all its size variants if any.
	 * 
	 * @param string $fileName The file name, without extension
	 */
	function setFileName($fileName) {
		$this->fileName = $fileName;
	}

	/**
	 * Sets the base file name without extension. The actual file name will vary depending on the size, and file obfuscation settings.
	 * 
	 * If fileNameObfuscationSalt is set, the file name will be obfuscated with it to prevent file scrapping attacks.
	 * To obfuscate the file name, a dot and a hash based on the original file name, the size name (if any) and the fileNameObfuscationSalt is used.
	 * We use md4 because it seems to be the fastest, and we're using it for creating a salted one-way hash, so no need for cryptographic strength.
	 * 
	 * @param string $sizeName The size name
	 * @return string The file name, without extension
	 */
	function getFileName($sizeName = false) {
		if (!$this->fileNameObfuscationSalt)
			return $this->fileName;

		return $this->fileName.".".hash("md4", $this->fileName.$sizeName.$this->fileNameObfuscationSalt);
	}
	
	/**
	 * @param string $fileDirectory The file directory
	 */
	function setFileDirectory($fileDirectory) {
		$this->fileDirectory = $fileDirectory;
	}

	/**
	 * Sets the base directory to store the file. The actual directory name will vary depending on the size, and file obfuscation settings.
	 * 
	 * If fileDirectoryDeepness is set, the directory will be added a subdirectory structure based on the first characters of the filename to improve disk efficiency.
	 * 
	 * @param string $sizeName The size name
	 * @return string The directory where this image with the given $sizeName is stored
	 */
	function getFileDirectory($sizeName = false) {
		if (!$this->fileDirectoryDeepness)
			return $this->fileDirectory;

		return $this->fileDirectory."/".self::buildDeepSubdirectoryName($this->getFileName($sizeName), $this->fileDirectoryDeepness);
	}
	

	/**
	 * buildDeepSubdirectoryName
	 *
	 * Builds a subdirectory structure based on the given id. This is usually used to avoid storing too many files in a single directory.
	 * A depth of 3 is usually enough for any environment with huge amounts of files.
	 *
	 * @param mixed $id The id that will be used to build the structure, usually the same id as the name of the file to be stored, when the file is named after an id.
	 * @param integer $depth The depth of the subdirectory tree
	 * @return string The subdirectory name, with a leading slash, or null if no deep subdirectory tree is being used
	 */
	static function buildDeepSubdirectoryName($id, $depth = 3) {
		if (!$depth)
			return null;
		$r .= substr($id, strlen($id)-1, 1);
		for($i=2; $i<=$depth; $i++)
			$r .= "/".(strlen($id) < $i ? "0" : substr($id, strlen($id)-$i, 1));
		return $r;
	}

	/**
	 * Checks if the directory where this image should be stored exists on the disk, including the deep directory structure if specified. Creates it if it doesn't exists.
	 * 
	 * @param string $sizeName The size name
	 * @return boolean True if the creation went ok, or if the directory already existed.
	 */
	function createFileDirectory($sizeName = false) {
		$directory = $this->getFileDirectory($sizeName);
		if (file_exists($directory) || is_dir($directory))
			return true;
		return mkdir($directory, 0777, true);
	}

	/**
	 * getUrl
	 *
	 * @param string $sizeName The size for which to obtain the image URL. Leave to false or do not specify if this image is not available on different sizes.
	 * @param boolean $isHd Whether to use the high density version of the image if available, or not. Defaults to false.
	 * @return string The URL of the image file for the specified $sizeName
	 */
	function getUrl($sizeName = false, $isHd = false) {
		if ($isHd)
			$sizeName .= ".hd";
		return
			"/".
			$this->getFileDirectory($sizeName).
			"/".
			$this->getFileName($sizeName).
			".".
			($this->sizes[$sizeName] ?
				$sizeName.
				".".
				$this->sizes[$sizeName]["imageFormat"]
			:
			$this->imageFormatWhenNoSizes);
	}

	/**
	 * getAbsoluteLocalPath
	 *
	 * @param string $sizeName The size for which to obtain the image absolute local path. Leave to false or do not specify if this image is not available on different sizes.
	 * @param boolean $isHd Whether to use the high density version of the image if available, or not. Defaults to false.
	 * @return string The path
	 */
	function getAbsoluteLocalPath($sizeName = false, $isHd = false) {
		return $_SERVER['DOCUMENT_ROOT'].$this->getUrl($sizeName, $isHd);
	}

	/**
	 * getWidth
	 *
	 * @param string $sizeName If the image has sizes, the size for which to get the width for
	 * @param boolean $isHd Whether to use the high density version of the image if available, or not. Defaults to false.
	 * @return integer The width of the image in pixels
	 */
	function getWidth($sizeName = false, $isHd = false) {
		if (!$result = getimagesize($this->getAbsoluteLocalPath($sizeName, $isHd))) {
			global $e;
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Can't get image width from given file",
				"errorVariables" => array_merge(
					$this->getDebugErrorVariables(),
					[
						"sizeName" => $sizeName,
						"isHd" => $isHd,
						"file" => $this->getAbsoluteLocalPath($sizeName, $isHd)
					]
				)
			]);
			return false;
		}

		list($width) = $result;
		return $width;
	}

	/**
	 * getHeight
	 *
	 * @param string $sizeName If the image has sizes, the size for which to get the height for
	 * @param boolean $isHd Whether to use the high density version of the image if available, or not. Defaults to false.
	 * @return integer The height of the image in pixels
	 */
	function getHeight($sizeName = false, $isHd = false) {
		if (!$result = getimagesize($this->getAbsoluteLocalPath($sizeName, $isHd))) {
			global $e;
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Can't get image height from given file",
				"errorVariables" => array_merge(
					$this->getDebugErrorVariables(),
					[
						"sizeName" => $sizeName,
						"isHd" => $isHd,
						"file" => $this->getAbsoluteLocalPath($sizeName, $isHd)
					]
				)
			]);
			return false;
		}

		list(, $height) = $result;
		return $height;
	}

	/**
	 * isFileExists
	 *
	 * Checks if a file for this Image exists or not. Checks for both Hd and non-Hd version of the image, if Hd is configured for the given $sizeName.
	 *
	 * @param string $sizeName Optional, if this Image has sizes, the size name file to check
	 * @return boolean Whether the image file exists or not. If a $size is passed and this Image has sizes, the file checked is the one corresponding to that size
	 */
	function isFileExists($sizeName = false) {
		return is_file($this->getAbsoluteLocalPath($sizeName, $this->sizes[$sizeName]["isHd"]));
	}

	/**
	 * isFilesExists
	 *
	 * Checks if all files for this Image exists or not. All files are checked if this image has sizes. Only the first size is checked if $isCheckOnlyFirstSize is true. If the image hasn't sizes, only the corresponding file is checked
	 *
	 * @param  boolean $isCheckOnlyFirstSize Whether to check only the first size (if this image has sizes) for improved performance.
	 * @return boolean Whether all the image files exist or not.
	 */
	function isFilesExists($isCheckOnlyFirstSize = false) {
		$isAllFilesExist = true;
		if ($this->sizes) {
			while (list($sizeName) = each($this->sizes)) {
				if (!$this->isFileExists($sizeName))
					$isAllFilesExist = false;
				if ($isCheckOnlyFirstSize)
					break;
			}
			reset($this->sizes);
			return $isAllFilesExist;
		}
		else
			return $this->isFileExists();
	}

	/**
	 * Creates the image files from the given $sourceFileName as specified by the setup sizes.
	 * The object must be loaded with the proper configurations in order to work.
	 *
	 * @param string $sourceFileName The source image file, must include any required path (absolute or relative) and extension
	 * @param boolean $isCreateDirectory Whether to create the directory where the files will be stored if it doesn't exists.
	 * @return boolean True if all files creation has gone ok, false otherwise
	 */
	function buildFromFile($sourceFileName, $isCreateDirectory = true) {
		$isDebug = false;

		if (!$this->sizes) {
			global $e;
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Can't create size files from image because no sizes were defined"
			]);
			return false;
		}

		if (!$result = getimagesize($sourceFileName)) {
			global $e;
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Can't get image information from given file",
				"errorVariables" => array_merge(
					$this->getDebugErrorVariables(),
					[
						"sourceFileName" => $sourceFileName
					]
				)
			]);
			return false;
		}

		list($sourceWidth, $sourceHeight, $imageType) = $result;

		switch ($imageType){
			case IMAGETYPE_BMP:
				$sourceImage = imagecreatefrombmp($sourceFileName);
				break;
			case IMAGETYPE_GIF:
				$sourceImage = imagecreatefromgif($sourceFileName);
				break;
			case IMAGETYPE_JPEG:
				$sourceImage = imagecreatefromjpeg($sourceFileName);
				break;
			case IMAGETYPE_PNG:
				$sourceImage = imagecreatefrompng($sourceFileName);
				break;
			case IMAGETYPE_WBMP:
				$sourceImage = imagecreatefromwbmp($sourceFileName);
				break;
			case IMAGETYPE_WEBP:
				$sourceImage = imagecreatefromwebp($sourceFileName);
				break;
		}

		if (!$sourceImage) {
			global $e;
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Can't create a GD image resource from given file",
				"errorVariables" => array_merge(
					$this->getDebugErrorVariables(),
					[
						"sourceFileName" => $sourceFileName
					]
				)
			]);
			return false;
		}

		while (list($sizeName, $sizeSetup) = each($this->sizes)) {
			$finalWidth = false;
			$finalHeight = false;
			if($isDebug) echo "Size ".$sizeName." source size: ".$sourceWidth."x".$sourceHeight." ";

			if ($isCreateDirectory && !$this->createFileDirectory($sizeName)) {
				global $e;
				$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
					"errorDescription" => "Can't create file directory for image"
				]);
				return false;
			}

			$finalFileName = $this->getFileDirectory($sizeName)."/".$this->getFileName($sizeName).".".$sizeName.".".$sizeSetup["imageFormat"];

			if($isDebug) echo "final filename: ".$finalFileName." ";

			if ($sizeSetup["imageResizeMethod"] != "copy") { // When resizing methods other than "copy" are requested

				switch ($sizeSetup["imageResizeMethod"]) {
					case "maximumWidthOrHeight":
						if ($sourceWidth > $sourceHeight) { // Landscape format
							if ($sourceWidth <= $sizeSetup["width"]) { // If source width is smaller or equal than desired width, don't resize
								$finalWidth = $sourceWidth;
								$finalHeight = $sourceHeight;
							}
							else { // If source width is greater than the desired width, downscale
								$finalWidth = $sizeSetup["width"];
								$finalHeight = ceil( ($sourceHeight * $finalWidth) / $sourceWidth);
							}
						}
						else { // Portrait format
							if ($sourceHeight <= $sizeSetup["height"]) { // If source height is smaller or equal than desired height, don't resize
								$finalWidth = $sourceWidth;
								$finalHeight = $sourceHeight;
							}
							else { // If source height is greater than the desired height, downscale
								$finalHeight = $sizeSetup["height"];
								$finalWidth = ceil( ($sourceWidth * $finalHeight) / $sourceHeight);
							}
						}
						break;

					case "forceWidthAndHeight":
						break;

					case "noResize":
						$finalWidth = $sourceWidth;
						$finalHeight = $sourceHeight;
						break;
				}

				if($isDebug) echo "final size: ".$finalWidth."x".$finalHeight." ";

				$tempImage = imageCreateTrueColor($finalWidth, $finalHeight);
				imagecopyresampled($tempImage, $sourceImage, 0, 0, 0, 0, $finalWidth, $finalHeight, $sourceWidth, $sourceHeight);

				switch ($sizeSetup["imageFormat"]) {
					case "jpg":
						if ($sizeSetup["isProgressive"])
							imageinterlace($tempImage, true);

						if (!imagejpeg($tempImage, $finalFileName, $sizeSetup["jpgCompression"])) {
							global $e;
							$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
								"errorDescription" => "Can't create JPG image",
								"errorVariables" => array_merge(
									$this->getDebugErrorVariables(),
									[
										"sourceFileName" => $sourceFileName,
										"finalFileName" => $finalFileName,
										"isProgressive" => $sizeSetup["isProgressive"],
										"jpgCompression" => $sizeSetup["jpgCompression"]
									]
								)
							]);
							return false;
						}
						break;

					case "png":
						if (!imagepng($tempImage, $finalFileName, $sizeSetup["pngCompression"])) {
							global $e;
							$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
								"errorDescription" => "Can't create PNG image",
								"errorVariables" => array_merge(
									$this->getDebugErrorVariables(),
									[
										"sourceFileName" => $sourceFileName,
										"finalFileName" => $finalFileName,
										"pngCompression" => $sizeSetup["pngCompression"]
									]
								)
							]);
							return false;
						}
						break;

					case "gif":
						if (!imagegif($tempImage, $finalFileName)) {
							global $e;
							$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
								"errorDescription" => "Can't create GIF image",
								"errorVariables" => array_merge(
									$this->getDebugErrorVariables(),
									[
										"sourceFileName" => $sourceFileName,
										"finalFileName" => $finalFileName
									]
								)
							]);
							return false;
						}
						break;
				}

				imagedestroy($tempImage);
			}
			else { // When "copy" image resize method is requested
				if (!copy($sourceFileName, $finalFileName)) {
					global $e;
					$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
						"errorDescription" => "Can't copy image",
						"errorVariables" => array_merge(
							$this->getDebugErrorVariables(),
							[
								"sourceFileName" => $sourceFileName,
								"finalFileName" => $finalFileName
							]
						)
					]);
					return false;
				}
			}

			if ($isDebug) echo "<a href=\"".$finalFileName."\" target=\"_newwindow\">see it</a> ";

			if($isDebug) echo "<br>\n";
		}
		reset($this->sizes);

		return true;
	}

	/**
	 * getDebugErrorVariables
	 *
	 * Returns an array suitable for the Errors::trigger method containing identificative and useful information about this Image for debugging
	 *
	 * return array A hash array of variables
	 */
	function getDebugErrorVariables() {
		return [
			"base fileDirectory" => $this->fileDirectory,
			"base fileName" => $this->fileName,
			"sizeNames" => implode(",", array_keys($this->sizes))
		];
	}
}