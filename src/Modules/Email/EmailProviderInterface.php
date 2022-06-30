<?php

namespace Cherrycake\Modules\Email;

/**
 * Interface for all email providers
 */
interface EmailProviderInterface {
	/**
	 * @param array $recipients The recipients for the email, where each array item is an email address or a hash array with the keys:
	 *	- address: The email address of the recipient
	 * 	- name: The name of the recipient
	 * @param string $subject The email subject
	 * @param string $htmlBody Optionally, the HTML body of the email
	 * @param string $plainBody Optionally, the plain text body of the email. If not specified, the $htmlBody will be automatically converted
	 * @param string|array $from The sender's email address, or an array with the keys:
	 *  - address: The email address of the sender
	 * 	- name: The name of the sender
	 * @param array $replyTo The 'reply to's for the email, where each array item is a 'reply to' email address or a hash array with the keys:
	 *	- address: The email address of the 'reply to'
	 * 	- name: The name of the 'reply to'
	 * @param array $carbonCopy The 'carbon copy's for the email, where each array item is a 'carbon copy' email address or a hash array with the keys:
	 *	- address: The email address of the 'carbon copy'
	 * 	- name: The name of the 'carbon copy'
	 * @param array $blindCarbonCopy The 'blind carbon copy's for the email, where each array item is a 'blind carbon copy' email address or a hash array with the keys:
	 *	- address: The email address of the 'blind carbon copy'
	 * 	- name: The name of the 'blind carbon copy'
	 * @param array $attachFiles The files attached to the email, where each array item is a hash array with the keys:
	 * 	- path: The local path of the file
	 * 	- name: The file name
	 * @return bool Whether the email was delivered
	 * @throws EmailProviderException When there was some problem delivering the email
	 */
	public function send(
		array $recipients,
		string $subject,
		?string $htmlBody = null,
		?string $plainBody = null,
		string|array $from = null,
		?array $replyTo = null,
		?array $carbonCopy = null,
		?array $blindCarbonCopy = null,
		?array $attachFiles = null,
	): bool;

	/**
	 * Finalizes the provider. Normally, by closing any connections.
	 */
	public function end();
}
