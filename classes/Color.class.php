<?php

/**
 * Color
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Color
 *
 * Class that represents a color.
 *
 * @package Cherrycake
 * @category Classes
 */
class Color {
	/**
	 * @var $r The red component
	 */
	private $r;

	/**
	 * @var $r The green component
	 */
	private $g;

	/**
	 * @var $r The blue component
	 */
	private $b;

	/**
	 * @var $alpha The alpha value
	 */
	private $alpha = 1;

	/**
	 * Constructor factory
	 *
	 * @param string $with How to populate the created color object. Leave to false for unpopulated request.
	 */
	function __construct($with = false, $parameter = false) {
		switch ($with)
		{
			case "withRgb":
				$this->setRgb($parameter);
				break;
			case "withHex":
				$this->setHex($parameter);
				break;
			case "withHsl":
				$this->setHsl($parameter);
				break;
			case "withHsv":
				$this->setHsv($parameter);
				break;
		}
	}

	/**
	 * getClone
	 *
	 * Clones the current color
	 *
	 * @return Color A cloned Color object
	 */
	function getClone() {
		return unserialize(serialize($this));
	}

	/**
	 * setRgb
	 *
	 * Sets the color to the passed RGB values
	 *
	 * @param array $rgb An array containing r, g and b values. Can optionally have a fourth alpha value.
	 * @return Color The same object to allow for chaining
	 */
	function setRgb($rgb) {
		list($this->r, $this->g, $this->b) = $rgb;
		if (isset($rgb[3]))
			$this->setAlpha($rgb[3]);
		return $this;
	}

	/**
	 * setHex
	 *
	 * Sets the color to the passed hexadecimal value
	 *
	 * @param string $hex The hexadecimal color value
	 * @return Color The same object to allow for chaining
	 */
	function setHex($hex) {
		if ($hex[0] == '#')
			$hex = substr($hex, 1);

		if (strlen($hex) == 6)
			list($r, $g, $b) = [
				$hex[0].$hex[1],
				$hex[2].$hex[3],
				$hex[4].$hex[5]
			];
		else
		if (strlen($hex) == 3)
			list($r, $g, $b) = [
				$hex[0].$hex[0],
				$hex[1].$hex[1],
				$hex[2].$hex[2]
			];
		else
			return false;

		$this->r = hexdec($r);
		$this->g = hexdec($g);
		$this->b = hexdec($b);

		return $this;
	}

	/**
	 * setHsl
	 *
	 * Sets the color to the passed HSL values (Hue, Saturation, Lightness)
	 *
	 * @param array $hsl The HSL values
	 * @return Color The same object to allow for chaining
	 */
	function setHsl($hsl)
	{
		list($h, $s, $l) = $hsl;

		if ($s == 0)
		{
			$r = $l;
			$g = $l;
			$b = $l;
		}
		else
		{
			if ($l < .5)
				$t2 = $l * (1.0 + $s);
			else
				$t2 = ($l + $s) - ($l * $s);

			$t1 = 2.0 * $l - $t2;

			$rt3 = $h + 1.0/3.0;
			$gt3 = $h;
			$bt3 = $h - 1.0/3.0;

			if ($rt3 < 0) $rt3 += 1.0;
			if ($rt3 > 1) $rt3 -= 1.0;
			if ($gt3 < 0) $gt3 += 1.0;
			if ($gt3 > 1) $gt3 -= 1.0;
			if ($bt3 < 0) $bt3 += 1.0;
			if ($bt3 > 1) $bt3 -= 1.0;

			if (6.0 * $rt3 < 1)
				$r = $t1 + ($t2 - $t1) * 6.0 * $rt3;
			else
			if (2.0 * $rt3 < 1)
				$r = $t2;
			else
			if (3.0 * $rt3 < 2)
				$r = $t1 + ($t2 - $t1) * ((2.0/3.0) - $rt3) * 6.0;
			else
				$r = $t1;

			if (6.0 * $gt3 < 1)
				$g = $t1 + ($t2 - $t1) * 6.0 * $gt3;
			else
			if (2.0 * $gt3 < 1)
				$g = $t2;
			else
			if (3.0 * $gt3 < 2)
				$g = $t1 + ($t2 - $t1) * ((2.0/3.0) - $gt3) * 6.0;
			else
				$g = $t1;

			if (6.0 * $bt3 < 1)
				$b = $t1 + ($t2 - $t1) * 6.0 * $bt3;
			else
			if (2.0 * $bt3 < 1)
				$b = $t2;
			else
			if (3.0 * $bt3 < 2)
				$b = $t1 + ($t2 - $t1) * ((2.0/3.0) - $bt3) * 6.0;
			else
				$b = $t1;
		}

		$this->r = (int)round(255.0 * $r);
		$this->g = (int)round(255.0 * $g);
		$this->b = (int)round(255.0 * $b);

		return $this;
	}

	/**
	 * setHsv
	 *
	 * Sets the color to the passed HSV values (Hue, Saturation, Value)
	 *
	 * @param array $hsv The HSV values
	 * @return Color The same object to allow for chaining
	 */
	function setHsv($hsv)
	{
		list($h, $s, $v) = $hsv;

		$rgb = [];

		if ($s == 0)
			$r = $g = $b = $v * 255;
		else
		{
			$var_H = $h * 6;
			$var_i = floor( $var_H );
			$var_1 = $v * ( 1 - $s );
			$var_2 = $v * ( 1 - $s * ( $var_H - $var_i ) );
			$var_3 = $v * ( 1 - $s * (1 - ( $var_H - $var_i ) ) );

			if ($var_i == 0) {
				$var_R = $v;
				$var_G = $var_3;
				$var_B = $var_1 ;
			}
			else
			if ($var_i == 1) {
				$var_R = $var_2;
				$var_G = $v;
				$var_B = $var_1 ;
			}
			else
			if ($var_i == 2) {
				$var_R = $var_1;
				$var_G = $v;
				$var_B = $var_3;
			}
			else
			if ($var_i == 3) {
				$var_R = $var_1;
				$var_G = $var_2;
				$var_B = $v;
			}
			else
			if ($var_i == 4) {
				$var_R = $var_3;
				$var_G = $var_1;
				$var_B = $v;
			}
			else {
				$var_R = $v;
				$var_G = $var_1;
				$var_B = $var_2;
			}

			$r = $var_R * 255;
			$g = $var_G * 255;
			$b = $var_B * 255;
		}

		$this->r = $r;
		$this->g = $g;
		$this->b = $b;

		return $this;
	}

	/**
	 * setAlpha
	 *
	 * Sets the alpha value for the color.
	 *
	 * @param float $alpha The alpha value, from 0 to 1
	 * @return Color The same object to allow for chaining
	 */
	function setAlpha($alpha) {
		$this->alpha = $alpha;
		return $this;
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
	 * getCss
	 *
	 * @return string Basic Css hexadecimal representation of the color. i.e: #ffc020
	 */
	function getCss() {
		if ($this->alpha)
			return $this->getCssRgba();

		$r = intval($this->r);
		$g = intval($this->g);
		$b = intval($this->b);

		$r = dechex($r<0?0:($r>255?255:$r));
		$g = dechex($g<0?0:($g>255?255:$g));
		$b = dechex($b<0?0:($b>255?255:$b));

		$color = (strlen($r) < 2?'0':'').$r;
		$color .= (strlen($g) < 2?'0':'').$g;
		$color .= (strlen($b) < 2?'0':'').$b;
		return "#".$color;
	}

	/**
	 * getCssRgba
	 *
	 * @return string The Css color representation in the rgba(r, g, b, a) format.
	 */
	function getCssRgba() {
		return "rgba(".$this->r.", ".$this->g.", ".$this->b.", ".number_format($this->alpha, 2, ".", "").")";
	}

	/**
	 * getRgb
	 *
	 * Returns the RGB values in the form of an array
	 *
	 * @return array The RGB values
	 */
	function getRgb() {
		return [$this->r, $this->g, $this->b];
	}

	/**
	 * getHex
	 *
	 * Returns the hexadecimmal value as a string, suitable for HTML and CSS
	 * By Sverri: http://snipplr.com/view/39498/rgb2hex
	 *
	 * @return string The hexadecimal value
	 */
	function getHex() {
		$out = "";
		if ($shorten && ($this->r + $this->g + $this->b) % 17 !== 0)
			$shorten = false;

		foreach (array($this->r, $this->g, $this->b) as $c) {
			$hex = base_convert($c, 10, 16);

			if ($shorten)
				$out .= $hex[0];
			else
				$out .= ($c < 16) ? ("0".$hex) : $hex;
		}
		return $uppercase ? strtoupper($out) : $out;
	}

	/**
	 * lighten
	 *
	 * Lightens the color
	 *
	 * @param $amount Amount of light to add
	 * @return Color The same object to allow for chaining
	 */
	function lighten($amount) {
		$this->r += $amount;
		$this->g += $amount;
		$this->b += $amount;

		if($this->r > 255)
			$this->r = 255;

		if($this->g > 255)
			$this->g = 255;

		if($this->b > 255)
			$this->b = 255;

		return $this;
	}
	
	/**
	 * darken
	 *
	 * Darkens the color
	 *
	 * @param $amount Amount of light to substract
	 * @return Color The same object to allow for chaining
	 */
	function darken($amount) {
		$this->r -= $amount;
		$this->g -= $amount;
		$this->b -= $amount;

		if($this->r < 0)
			$this->r = 0;

		if($this->g < 0)
			$this->g = 0;

		if($this->b < 0)
			$this->b = 0;

		return $this;
	}

	/**
	 * invert
	 *
	 * Inverts the color
	 * @return Color The same object to allow for chaining
	 */
	function invert()
	{
		$this->r = 255 - $this->r;
		$this->g = 255 - $this->g;
		$this->b = 255 - $this->b;

		return $this;
	}

	/**
	 * @return integer The luminosity of the color, where 0 is completely dark and 255 is completely light
	 */
	function getLuminosity() {
		return ($this->r + $this->g + $this->b) / 3;
	}

	/**
	 * @param integer $threshold The minimum luminosity to consider a color is light instead of dark
	 * @return boolean Whether the color is considered to be light
	 */
	function isLight($threshold = 128) {
		return $this->getLuminosity() > $threshold;
	}

	/**
	 * @param integer $threshold The maximum luminosity to consider a color is dark instead of light
	 * @return boolean Whether the color is considered to be dark
	 */
	function isDark($threshold = 128) {
		return !$this->isLight($threshold);
	}
}