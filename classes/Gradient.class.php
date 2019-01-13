<?php

/**
 * Gradient
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Gradient
 *
 * Class that represents a color gradient.
 *
 * @package Cherrycake
 * @category Classes
 */
class Gradient
{
	const STYLE_HORIZONTAL = 0;
	const STYLE_VERTICAL = 1;
	const STYLE_RADIAL = 2;
	const STYLE_DIAGONAL_UP = 3;
	const STYLE_DIAGONAL_DOWN = 4;

	/**
	 * @var $style The gradient style
	 */
	private $style = STYLE_VERTICAL;

	/**
	 * @var $steps The color steps
	 */
	private $colorSteps;

	/**
	 * Constructor factory
	 *
	 * @param string $with How to populate the created gradient object. Leave to false for unpopulated request.
	 */
	function __construct($with = false, $parameter = false, $style = false) {
		if ($style)
			$this->setStyle($style);

		switch ($with)
		{
			case "withColorSteps":
				$this->addColorSteps($parameter);
				break;
			case "withColorStepsAutoPositioned":
				$this->addColorStepsAutoPositioned($parameter);
				break;
		}
	}

	/**
	 * getClone
	 *
	 * Clones the current gradient
	 *
	 * @return Color A cloned Gradient object
	 */
	function getClone() {
		return unserialize(serialize($this));
	}

	/**
	 * setStyle
	 *
	 * Sets the gradient style
	 *
	 * @param int $style The style of the gradient, one of the defined constants STYLE_*
	 */
	function setStyle($style) {
		$this->style = $style;
	}

	/**
	 * addColorStep
	 *
	 * Adds a color step
	 *
	 * @param Color $color The color object
	 * @param int $position The percentual position of the color in the gradient
	 */
	function addColorStep($color, $position) {
		$this->colorSteps[$position] = $color;
	}

	/**
	 * addColorSteps
	 *
	 * Adds multiple color steps
	 *
	 * @param array $colorSteps an array of Colors and its positions in the gradient, in the form of [<position> => <Color>, ...]
	 */
	function addColorSteps($colorSteps) {
		while(list($position, $color) = each($colorSteps))
			$this->addColorStep($color, $position);
	}

	/**
	 * addColorStepsAutoPositioned
	 *
	 * Adds the passed colors to the gradient placing them automatically by generating adequate position percentages to locate them throughout the gradient at equal distances, covering the 100% of the gradient, and in the order that have been passed on the array.
	 *
	 * @param array $colors A one-dimension array of Color objects
	 */
	function addColorStepsAutoPositioned($colors) {
		$position = 0;
		$positionDelta = 100 / (sizeof($colors) - 1);
		foreach ($colors as $color) {
			$this->addColorStep($color, $position);
			$position += $positionDelta;
		}
	}

	/**
	 * getDowngradeColor
	 *
	 * @return Color The plain color that must be shown as a downgraded version of the gradient, when the browser is not compatible with Css gradients.
	 */
	function getDowngradeColor() {
		$color = each($this->colorSteps);
		reset ($this->colorSteps);
		return $color[1];
	}

	/**
	 * getCss
	 *
	 * Gets the Css definition of this gradient
	 *
	 * @param int $style The gradient style one of the defined consts with the syntax STYLE_*. If not passed, it uses the default gradient style
	 * @return string Css gradient definition
	 */
	function getCss($style = false) {
		global $e;

		if (!$style)
			$style = $this->style;

		if ($style == STYLE_RADIAL)
			$baseParameter = "radial-gradient(center, ellipse cover,";
		else
			$baseParameter =
				"linear-gradient(".
					($this->style == STYLE_HORIZONTAL ? "left" : null).
					($this->style == STYLE_VERTICAL ? "top" : null).
					($this->style == STYLE_DIAGONAL_UP ? "45deg" : null).
					($this->style == STYLE_DIAGONAL_DOWN ? "-45deg" : null).
					",";

		while (list($position, $color) = each($this->colorSteps))
			$gradientValues .= $color->getCssRgba()." ".$e->Css->unit($position, "%").", ";
		reset($this->colorSteps);
		$gradientValues = substr($r, 0, -2);

		return
			"-webkit-".$baseParameter." ".$gradientValues.");\n".
			"-moz-".$baseParameter." ".$gradientValues.");\n".
			"-o-".$baseParameter." ".$gradientValues.");\n".
			$baseParameter." ".$gradientValues.");\n";
	}

	/**
	 * getCssBackground
	 *
	 * Gets the Css definition for a background using this gradient. Also adds the default fallback color for browsers not supporting Css gradients
	 *
	 * @param int $style The gradient style one of the defined consts with the syntax STYLE_*. If not passed, it uses the default gradient style
	 * @return string Css background gradient definition
	 */
	function getCssBackground($style = false) {
		global $e;

		if (!$style)
			$style = $this->style;

		return
			"background: ".$this->getDowngradeColor().";\n".
			"background: ".$this->getCss().";\n";
	}

	/**
	 * __toString
	 *
	 * Wrapper for __toString magic method
	 *
	 * @returns string Css hexadecimal representation of the color
	 */
	function __toString() {
		return $this->getCss();
	}

	/**
	 * lighten
	 *
	 * Lightens the gradient
	 *
	 * @param $amount Amount of light to add
	 */
	function lighten($amount) {
		while (list($position) = each($this->colorSteps))
			$this->colorSteps[$position]->lighten($amount);
		reset($this->colorSteps);
	}

	/**
	 * getGradientLightened
	 *
	 * @param $amount Amount of light to add
	 * @return Gradient A new Gradient object based on this one,  lightened
	 */
	function getGradientLightened($amount) {
		$gradient = $this->getClone();
		$gradient->lighten($amount);
		return $gradient;
	}

	/**
	 * darken
	 *
	 * Darkens the gradient
	 *
	 * @param $amount Amount of light to substract
	 */
	function darken($amount) {
		while (list($position) = each($this->colorSteps))
			$this->colorSteps[$position]->darken($amount);
		reset($this->colorSteps);
	}

	/**
	 * getGradientDarkened
	 *
	 * @param $amount Amount of light to substract
	 * @return Gradient A new Gradient object based on this one, darkened
	 */
	function getGradientDarkened($amount) {
		$gradient = $this->getClone();
		$gradient->darken($amount);
		return $gradient;
	}
}