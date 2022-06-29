<?php

namespace Cherrycake\Modules\Email;

use Cherrycake\Classes\RichException;

/**
 * An exception to be thrown by Email providers whenever there's a problem delivering an email
 */
class EmailProviderException extends RichException {
}
