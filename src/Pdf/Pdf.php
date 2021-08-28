<?php

namespace Cherrycake\Pdf;

/**
 * Pdf
 * Includes the mPDF library to generates PDF files (https://github.com/mpdf/mpdf)
 * Reqires PHP >= 5.6 && <=7.3, mbstring and gd extensions. zlib, bcmath and xml extensions are required for some extended functionality.
 *
 * Generates PDF files
 *
 * @package Cherrycake
 * @category Modules
 */
class Pdf extends \Cherrycake\Module {
    function init(): bool {
        if (!parent::init())
            return false;
        require_once APP_DIR."/vendor/autoload.php";
        return true;
    }
}
