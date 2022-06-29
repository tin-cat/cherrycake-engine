<?php

namespace Cherrycake\Modules\Email;

/**
 * Interface for all email providers
 */
interface EmailProviderInterface {
	/**
	 * @return bool Whether the email was delivered
	 * @throws EmailProviderException When there was some problem delivering the email
	 */
	public function send(
		array $recipients,
		string $subject,
		string $body,
		?array $from = null,
		?array $replyTo = null,
	): bool;
}
