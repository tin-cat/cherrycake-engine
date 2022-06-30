<?php

namespace Cherrycake\Modules\Email;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Cache Provider based on APC. It provides a very fast memory caching but limited to a relatively small amount of cached objects, depending on memory available on the APC server configuration.
 */
class EmailProviderSmtp extends EmailProvider implements EmailProviderInterface {

	const SMTP_ENCRYPTION_TLS = 0;
	const SMTP_ENCRYPTION_SSL = 1;

    private $phpMailer;

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
	): bool {

		// Default variables to config
		foreach([
			'from',
			'replyTo',
			'carbonCopy',
			'blindCarbonCopy',
		] as $key) {
			if (is_null($$key) && $this->getConfig($key))
				$$key = $this->getConfig($key);
		}

		if (!$replyTo && $this->getConfig('replyTo'))
			$replyTo = $this->getConfig('replyTo');

		set_time_limit(30);

        $this->phpMailer = new PHPMailer(true);

        try {

            $this->phpMailer->CharSet = "UTF-8";

			$this->phpMailer->isSMTP();
			$this->phpMailer->SMTPKeepAlive = true;
			$this->phpMailer->SMTPDebug = false;
			$this->phpMailer->Host = $this->getConfig("host");
			$this->phpMailer->Port = $this->getConfig("port");

			if ($this->getConfig("isAuth")) {
				$this->phpMailer->SMTPAuth = true;
				$this->phpMailer->SMTPSecure = [self::SMTP_ENCRYPTION_TLS => "tls", self::SMTP_ENCRYPTION_SSL => "ssl"][$this->getConfig("authMethod")];
				$this->phpMailer->Username = $this->getConfig("username");
				$this->phpMailer->Password = $this->getConfig("password");
			}

            if ($from) {
                $this->phpMailer->setFrom(
					is_array($from) ? $from['address'] : $from,
					is_array($from) ? $from['name'] : false,
				);
			}

            foreach ($recipients as $recipient) {
                $this->phpMailer->addAddress(
					is_array($recipient) ? $recipient['address'] : $recipient,
					is_array($recipient) ? $recipient['name'] : false,
				);
			}

			if ($replyTo) {
				foreach ($replyTo as $eachReplyTo) {
					$this->phpMailer->addReplyTo(
						is_array($eachReplyTo) ? $eachReplyTo['address'] : $eachReplyTo,
						is_array($eachReplyTo) ? $eachReplyTo['name'] : false
					);
				}
			}

			if ($carbonCopy) {
				foreach ($carbonCopy as $eachCarbonCopy) {
					$this->phpMailer->addCC(
						is_array($eachCarbonCopy) ? $eachCarbonCopy['address'] : $eachCarbonCopy,
						is_array($eachCarbonCopy) ? $eachCarbonCopy['name'] : false
					);
				}
			}

			if ($blindCarbonCopy) {
				foreach ($blindCarbonCopy as $eachBlindCarbonCopy) {
					$this->phpMailer->addBCC(
						is_array($eachBlindCarbonCopy) ? $eachBlindCarbonCopy['address'] : $eachBlindCarbonCopy,
						is_array($eachBlindCarbonCopy) ? $eachBlindCarbonCopy['name'] : false
					);
				}
			}

			if ($attachFiles) {
				foreach ($attachFiles as $attachFile) {
                    $this->phpMailer->addAttachment(
						$attachFile['path'],
						$attachFile['name']
					);
				}
			}

            $this->phpMailer->Subject = $subject;

            if ($htmlBody) {
                $this->phpMailer->isHTML(true);
                $this->phpMailer->Body = $htmlBody;
                $this->phpMailer->AltBody = $plainBody ?: strip_tags($htmlBody);
            }
            else
                $this->phpMailer->Body = $plainBody;

            $this->phpMailer->send();
            $this->phpMailer->ClearAddresses();

        } catch (\PHPMailer\PHPMailer\Exception $e) {
			throw new EmailProviderException('Error sending email: '.$e->getMessage());
        }
        return true;
	}

	public function end() {
        if ($this->phpMailer)
            $this->phpMailer->SmtpClose();
    }
}
