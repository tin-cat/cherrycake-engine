<?php

/**
 * UiComponentIcons
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentIcons
 *
 * A Ui component to use vectorial icons
 *
 * Configuration example for UiComponenticons.config.php:
 * <code>
 *  $UiComponentIconsConfig = [
 *      "directory" => "res/icons", // Where the icon files reside, each subdirectory is named after an style, containing the SVG icons for that style
 *      "sizes" => [16, 32, 64, 128, 256],  // The icon sizes to generate. Defaults to 16, 32, 64, 128, 256
 *      "sizeUnits" => "px", // The unit on which sizes are specified. Defaults to "px"
 *      "defaultSize" => 16, // The default icon size to use when no size Css class is specified. Defaults to 16
 *      "spinningIcons" => ["loading"] // An array of icon names that must be spinning
 *      "spinningSpeed" => 1, // The number of seconds a spinning icon takes to do a full turn. Defaults to 1
 *      "loadingIcon" => "loading", // The name of the icon that will be used as a loading icon, in order to put it at the end of the CSS to overwrite all other icons when applied.
 *      "isEmbedInline" => true, // Wheter to embed icon VG files on the CSS itself in Base 64 to avoid multiple HTTP calls or not.
 *      "method" => "backgroundImage", // The method to use to build css icons; "backgroundImage" or "mask". "backgroundImage" does not allows for coloring but is more compatible. "mask" allows for coloring via css but is less cross-browser compatible (Doesn't works in march 2017 Firefox)
 *      "defaultIconColor" => "#000" // The icons default color when using "mask" method.
 *  );
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentIcons extends UiComponent
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Holds the default configuration for this UiComponent
	 */
	protected $config = [
		"directory" => "res/icons",
		"sizes" => [16, 32, 64, 128, 256],  // The icon sizes to generate
		"sizeUnits" => "px", // The unit on which sizes are specified
		"defaultSize" => 16, // The default icon size to use when no size Css class is specified
		"spinningSpeed" => 1, // The number of seconds a spinning icon takes to do a full turn. Defaults to 1
		"loadingIcon" => "loading", // The name of the icon that will be used as a loading icon, in order to put it at the end of the CSS to overwrite all other icons when applied.
		"method" => "backgroundImage", // The method to use to build css icons; "backgroundImage" or "mask". "backgroundImage" does not allows for coloring but is more compatible. "mask" allows for coloring via css but is less cross-browser compatible
		"defaultIconColor" => "#000" // The icons default color when using "mask" method.
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addCssToSet($this->getConfig("cssSetName"), $this->generateCss());
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentIcons.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentIcons.js");
	}

	/**
	 * generateCss
	 *
	 * Generates the Icons Css
	 *
	 * @return string The Css
	 */
	function generateCss() {
		global $e;

		$r .=
			".UiComponentIcon {\n".
				($this->getConfig("method") == "mask" ?
					($this->getConfig("defaultIconColor") ? "background-color: ".$this->getConfig("defaultIconColor").";\n" : "").
					"-webkit-mask-repeat: no-repeat;\n".
					"mask-repeat: no-repeat;\n".
					"-webkit-mask-position: center;\n".
					"mask-position: center;\n"
				: "").
				($this->getConfig("method") == "backgroundImage" ?
					"background-repeat: no-repeat;\n".
					"background-position: center;\n"
				: "").
			"}\n";

		foreach ($this->getConfig("sizes") as $size) {
			$r .=
				($size == $this->getConfig("defaultSize") ? ".UiComponentIcon,\n" : null).
				".UiComponentIcon.size".$size." {\n".
					"width: ".$e->Css->unit($size, $this->getConfig("sizeUnits")).";\n".
					"height: ".$e->Css->unit($size, $this->getConfig("sizeUnits")).";\n".
					($this->getConfig("method") == "mask" ?
						"-webkit-mask-size: ".$e->Css->unit($size, $this->getConfig("sizeUnits")).";\n".
						"mask-size: ".$e->Css->unit($size, $this->getConfig("sizeUnits")).";\n"
					: "").
					($this->getConfig("method") == "backgroundImage" ?
						"background-size: ".$e->Css->unit($size, $this->getConfig("sizeUnits")).";\n"
					: "").
				"}\n";
		}

		if (!is_dir($this->getConfig("directory")))
			return false;

		// Load subdirectories that indicate different icon styles (i.e: colors)
		if (!$handler = opendir($this->getConfig("directory"))) {
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "Can't open directory ".$this->getConfig("directory")]);
			return false;
		}

		while (false !== ($fileName = readdir($handler)))
			if ($fileName != "." && $fileName != ".." && is_dir($this->getConfig("directory")."/".$fileName))
				$subDirectories[] = $fileName;

		if (!is_array($subDirectories))
			$subDirectories[] = "./";

		foreach ($subDirectories as $styleName)
			if ($handler = opendir($this->getConfig("directory")."/".$styleName))
				while (false !== ($fileName = readdir($handler)))
					if (strtolower(substr(strrchr($fileName, "."), 1)) == "svg" && substr($fileName, 0, 1) != "." && $fileName != "." && $fileName != "..")
						$styles[$styleName][] = $fileName;

		closedir($handler);

		if (is_array($styles)) {
			while (list($styleName, $fileNames) = each($styles)) {

				foreach ($fileNames as $fileName) {
					$fileNameWithoutExtension = strtolower(strstr($fileName, ".", true));
					$iconNames = explode("-", $fileNameWithoutExtension);
					foreach ($iconNames as $iconName) {
						$url =
							$this->getConfig("isEmbedInline")
							?
							$e->Css->getFileDataBase64($this->getConfig("directory")."/".$styleName."/".$fileName)
							:
							$this->getConfig("directory").($styleName != "./" ? "/".$styleName : "")."/".$fileName;
						$r .=
							".UiComponentIcon".($styleName != "./" ? ".".$styleName : "").".".$iconName." { ".
								($this->getConfig("method") == "backgroundImage" ?
									"background-image: url(".$url.")".
									($iconName == $this->getConfig("loadingIcon") ? " !important" : null).
									";"
								: "").
								($this->getConfig("method") == "mask" ?
									"-webkit-mask-image: url(".$url.")".
									($iconName == $this->getConfig("loadingIcon") ? " !important" : null).
									";".
									" mask-image: url(".$url.")".
									($iconName == $this->getConfig("loadingIcon") ? " !important" : null).
									";"
								: "").
							" }\n";
					}
				}
			}
		}

		if (is_array($this->getConfig("spinningIcons"))) {
			$r .=
				"@-moz-keyframes spin {".
					"from { -moz-transform: rotate(0deg); }".
					"to { -moz-transform: rotate(360deg); }".
				"}\n".
				"@-webkit-keyframes spin {".
					"from { -webkit-transform: rotate(0deg); }".
					"to { -webkit-transform: rotate(360deg); }".
				"}\n".
				"@keyframes spin {".
					"from {transform:rotate(0deg);}".
					"to {transform:rotate(360deg);}".
				"}\n";

			foreach ($this->getConfig("spinningIcons") as $spinningIconName)
				$r .=
					".UiComponentIcon.".$spinningIconName.",\n";

			$r = substr($r, 0, -2);

			$r .=
				"{".
					"-webkit-animation: spin ".$this->getConfig("spinningSpeed")."s infinite linear;".
					"-moz-animation: spin ".$this->getConfig("spinningSpeed")."s infinite linear;".
					"-ms-animation: spin ".$this->getConfig("spinningSpeed")."s infinite linear;".
				"}\n";
		}

		return $r;
	}
}