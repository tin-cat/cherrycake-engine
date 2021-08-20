<?php

namespace Cherrycake\Language;

/**
 * The Language module provides text translations for multilingual sites, working in conjunction with the Locale module.
 *
 * @package Cherrycake
 * @category Modules
 */
class Language extends \Cherrycake\Module {
	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	var $dependentCoreModules = [
		"Errors",
		"Cache",
		"Locale"
	];
}
