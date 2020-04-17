<?php

/**
 * Pdf
 * Includes the mPDF library to generates PDF files (https://github.com/mpdf/mpdf)
 * Reqires PHP >= 5.6 && <=7.3, mbstring and gd extensions. zlib, bcmath and xml extensions are required for some extended functionality.
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * Pdf
 *
 * Generates PDF files
 *
 * @package Cherrycake
 * @category Modules
 */
class Pdf extends \Cherrycake\Module {
    function init() {
        if (!parent::init())
            return false;
        require_once ENGINE_DIR."/vendor/autoload.php";
        return true;
    }
}