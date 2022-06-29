<?php

namespace Cherrycake\Modules\Email;

/**
 * Manages email providers.
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 */
class Email extends \Cherrycake\Classes\Module {

	protected bool $isConfigFileRequired = false;

	function init(): bool {
		if (!parent::init())
			return false;

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers")))
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], $provider["config"] ?? []);

		return true;
	}

	/**
	 * Adds an email provider
	 * @param string $key The key to later access the email provider
	 * @param string $providerClassName The email provider class name
	 * @param array $config The configuration for the email provider
	 */
	function addProvider(
		string $key,
		string $providerClassName,
		?array $config,
	) {
		eval("\$this->".$key." = new \\Cherrycake\\Modules\\Email\\".$providerClassName."();");
		$this->$key->config($config);
	}






	const SMTP_ENCRYPTION_TLS = 0;
	const SMTP_ENCRYPTION_SSL = 1;

    private $phpMailer;

    /**
     * Sends an email
     * @param array $tos An array where each element represents a recipient and is an array where the first element is its email address, and the second element is its name (optional)
     * @param string $subject The subject of the email
     * @param array $setup A hash array of additional setup keys, from the next possible ones:
     * * contentHTML: The HTML content of the message
     * * contentPlain: The plain text content of the message. If it's not specified, it will be automatically generated from the given contentHTML
     * * from: An array where the first element is the email address sending this message, and the second its name (optional)
     * * replyTo: An array where each element represents a replyTo recipient and is an array where the first element is its email address, and the second element is its name (optional)
     * * carbonCopy: An array where each element represents a CC recipient and is an array where the first element is its email address, and the second element is its name (optional)
     * * blindCarbonCopy: An array where each element represents a BCC recipient and is an array where the first element is its email address, and the second element is its name (optional)
     * * attachments: An array where each element represents a file to be attached to the email and is an array where the first element is the route to the file, and the second element is the file name (optional)
     * @return boolean Whether the email could be sent or not
     */
	function send($tos, $subject, $setup) {
        set_time_limit(30);
        $this->phpMailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {

            $this->phpMailer->CharSet = "UTF-8";
            if ($this->getConfig("method") == "internal")
                $this->phpMailer->isMail();
            else
            if ($this->getConfig("method") == "SMTP") {
                $this->phpMailer->isSMTP();
                $this->phpMailer->SMTPKeepAlive = true;
                $this->phpMailer->SMTPDebug = false;
                $this->phpMailer->Host = $this->getConfig("SMTPHost");
                $this->phpMailer->Port = $this->getConfig("SMTPPort");
                if ($this->getConfig("SMTPAuth")) {
                    $this->phpMailer->SMTPAuth = true;
                    $this->phpMailer->SMTPSecure = [self::SMTP_ENCRYPTION_TLS => "tls", self::SMTP_ENCRYPTION_SSL => "ssl"][$this->getConfig("SMTPSecure")];
                    $this->phpMailer->Username = $this->getConfig("SMTPUsername");
                    $this->phpMailer->Password = $this->getConfig("SMTPPassword");
                }
            }
            if (isset($setup["from"]))
                $this->phpMailer->setFrom($setup["from"][0], $setup["from"][1]);
            foreach ($tos as $to)
                $this->phpMailer->addAddress($to[0], isset($to[1]) ? $to[1] : false);
            if (isset($setup["replyTo"]) && is_array($setup["replyTo"]))
                foreach ($setup["replyTo"] as $replyTo)
                    $this->phpMailer->addReplyTo($replyTo[0], $replyTo[1]);
            if (isset($setup["carbonCopy"]) && is_array($setup["carbonCopy"]))
                foreach ($setup["carbonCopy"] as $carbonCopy)
                    $this->phpMailer->addCC($carbonCopy[0], $carbonCopy[1]);
            if (isset($setup["blindCarbonCopy"]) && is_array($setup["blindCarbonCopy"]))
                foreach ($setup["blindCarbonCopy"] as $blindCarbonCopy)
                    $this->phpMailer->addBCC($blindCarbonCopy[0], $blindCarbonCopy[1]);
            if (isset($setup["attachments"]) && is_array($setup["attachments"]))
                foreach ($setup["attachments"] as $attachment)
                    $this->phpMailer->addAttachment($attachment[0], $attachment[1]);

            $this->phpMailer->Subject = $subject;

            if (isset($setup["contentHTML"])) {
                $this->phpMailer->isHTML(true);
                $this->phpMailer->Body = $setup["contentHTML"];
                if (!isset($setup["contentPlain"]))
                    $setup["contentPlain"] = strip_tags($setup["contentHTML"]);
                $this->phpMailer->AltBody = $setup["contentPlain"];
            }
            else
                $this->phpMailer->Body = $setup["contentPlain"];

            $this->phpMailer->send();
            $this->phpMailer->ClearAddresses();

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return new \Cherrycake\Classes\ResultKo(["descriptions" => [$this->phpMailer->ErrorInfo]]);
        }
        return new \Cherrycake\Classes\ResultOk;
    }

    function end() {
        if ($this->phpMailer)
            $this->phpMailer->SmtpClose();
    }
}
