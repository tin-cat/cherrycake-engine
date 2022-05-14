<?php

namespace Cherrycake\Modules\Validate;

use Cherrycake\Classes\Engine;

/**
 * Module to validate different types of data.
 *
 * Configuration example for Validate.config.php:
 * <code>
 * $validateConfig = [
 * 	"emailValidationMethod" => \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_SIMPLE, // The method to use by default to validate emails, one of the available VALIDATE_EMAIL_METHOD_*
 * 	"emailValidationMailgunConfig" => [ // Configuration data for the Mailgun email validation method
 * 		"endpoint" => "https://api.mailgun.net/v3/address/validate",
 * 		"publicKey" => ""
 * 	],
 * 	"emailValidationMailboxLayerConfig" => [ // Configuration data for the Mailbox Layer email validation method
 * 		"endpoint" => "http://apilayer.net/api/check",
 * 		"apiKey" => ""
 *  ],
 *  "passwordStrengthValidationMinChars" => 8, // For password strength validation, minimum number of characters. Set to false to not check.
 *  "passwordStrengthValidationIsAtLeastOneNumber" => true, // For password strength validation, whether at least one number is required
 *  "passwordStrengthValidationIsAtLeastOneLetter" => true, // For password strength validation, whether at least one letter is required
 *  "passwordStrengthValidationIsRequireUppercaseAndLowercase" => true, // For password strength validation, whether to require at least one uppercase letter and a lowercase letter
 *  "passwordStrengthValidationIsRequireNotEqualToLogin" => true // For password strength validation, whether to require the password to be different than the login
 * ];
 * </code>
 */
class Validate extends \Cherrycake\Classes\Module {

	const USERNAME_INSTAGRAM = 0; // Value must be a valid Instagram username
	const USERNAME_TWITTER = 1; // Value must be a valid Twitter username

	const EMAIL_METHOD_SIMPLE = 0; // Most simple method of validating an email, just checking its syntax.
	const EMAIL_METHOD_MAILGUN = 1; // Advanced method using Mailgun third party.
	const EMAIL_METHOD_MAILBOXLAYER = 2; // Advanced method using Mailboxlayer third party.

	const PASSWORD_STRENGTH_WEAKNESS_TOO_SHORT = 0;
	const PASSWORD_STRENGTH_WEAKNESS_AT_LEAST_ONE_NUMBER = 1;
	const PASSWORD_STRENGTH_WEAKNESS_AT_LEAST_ONE_LETTER = 2;
	const PASSWORD_STRENGTH_WEAKNESS_UPPERCASE_AND_LOWERCASE = 3;
	const PASSWORD_STRENGTH_WEAKNESS_MATCHES_USERNAME = 4;

	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"emailValidationMethod" => \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_SIMPLE,
		"emailValidationMailgunConfig" => [
			"endpoint" => "https://api.mailgun.net/v3/address/validate",
			"publicKey" => ""
		],
		"emailValidationMailboxLayerConfig" => [
			"endpoint" => "http://apilayer.net/api/check",
			"apiKey" => ""
		],
		"passwordStrengthValidationMinChars" => 8,
		"passwordStrengthValidationIsAtLeastOneNumber" => true,
		"passwordStrengthValidationIsAtLeastOneLetter" => true,
		"passwordStrengthValidationIsRequireUppercaseAndLowercase" => true,
		"passwordStrengthValidationIsRequireNotEqualToLogin" => true
	];

	/**
	 * @param mixed $value The value to validate
	 * @param mixed $validations One of the available VALIDATE_* validations to perform, or an array of them
	 * @return Result Whether all the validations passed, or not. With the following additional payload:
	 * * description: An array containing the list of validation errors found when checking the value
	 * @todo Implement correct Instagram and Twitter username validations
	 */
	function isValid($value, $validations) {
		if (!is_array($validations))
			$validations[] = $validations;

		foreach ($validations as $validation) {
			switch ($validation) {
				case \Cherrycake\Modules\Validate\Validate::USERNAME_INSTAGRAM:
					if (!preg_match("/^(@)?([A-Za-z0-9_-])+$/", $value)) {
						$isError = true;
						$descriptions[] = "Invalid Instagram username";
					}
					break;
				case \Cherrycake\Modules\Validate\Validate::USERNAME_TWITTER:
					if (!preg_match("/^(@)?([A-Za-z0-9_-])+$/", $value)) {
						$isError = true;
						$descriptions[] = "Invalid Twitter username";
					}
					break;
			}
		}

		if ($isError)
			return new \Cherrycake\Classes\ResultKo([
				"descriptions" => $descriptions
			]);
		else
			return new \Cherrycake\Classes\ResultOk;
	}

	/**
	 * Validates the given email address
	 * @param string $email The email address to validate
	 * @param boolean $forceMethod If specified, this email validation method will be used instead of the configured one. One of the available VALIDATE_EMAIL_METHOD_*
	 * @param boolean $isFallbackToSimpleMethod Whether to use the VALIDATE_EMAIL_METHOD_SIMPLE method if the configured or forced method is not available
	 * @return Result A Result object
	 */
	function email(
		string $email,
		int|bool $forceMethod = false,
		bool $isFallbackToSimpleMethod = true
	): \Cherrycake\Classes\Result {
		$method = $forceMethod ? $forceMethod : $this->getConfig("emailValidationMethod");

		if ($isFallbackToSimpleMethod && $method > \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_SIMPLE)
			if ($method == \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_MAILGUN && !$this->getConfig("emailValidationMailgunConfig")["publicKey"])
				$method = \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_SIMPLE;
			else
			if ($method == \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_MAILBOXLAYER && !$this->getConfig("emailValidationMailboxLayerConfig")["apiKey"])
				$method = \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_SIMPLE;

		switch ($method) {
			case \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_SIMPLE:
				if (filter_var($email, FILTER_VALIDATE_EMAIL))
					return new \Cherrycake\Classes\ResultOk;
				else
					return new \Cherrycake\Classes\ResultKo;
				break;
			case \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_MAILGUN:
				return $this->emailValidateWithMailgun($email);
				break;
			case \Cherrycake\Modules\Validate\Validate::EMAIL_METHOD_MAILBOXLAYER:
				return $this->emailValidateWithMailboxLayer($email);
				break;
		}
		return false;
	}

	/**
	 * Validates an email using Mailgun
	 * @param string $email The email address to validate
	 * @return Result A Result object
	 */
	function emailValidateWithMailgun($email) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->getConfig("emailValidationMailgunConfig")["endpoint"]."?address=".$email);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_USERPWD, "api:".$this->getConfig("emailValidationMailgunConfig")["publicKey"]);
		if (!$result = curl_exec($ch)) {
			Engine::e()->SystemLog->event(new SystemLogEventError([
				"description" => "Error when trying to curl Mailgun API to validate an email",
				"data" => [
					"Email" => $email,
					"Curl error" => curl_error($ch)
				]
			]));
			return new \Cherrycake\Classes\ResultKo;
		}

		curl_close($ch);

		$result = json_decode($result);

		if (!$result->is_valid) {
			if ($result->did_you_mean)
				return new \Cherrycake\Classes\ResultKo([
					"didYouMean" => $result->did_you_mean
				]);
			return new \Cherrycake\Classes\ResultKo;
		}
		else
			return new \Cherrycake\Classes\ResultOk;
	}

	/**
	 * Validates an email using Mailbox Layer
	 * @param string $email The email address to validate
	 * @return array An result array where the first element is a boolean indicating whether the operation has gone ok or not, and the second element is a hash array of additional information.
	 */
	function emailValidateWithMailboxLayer($email) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,
			$this->getConfig("emailValidationMailboxLayerConfig")["endpoint"].
			"?access_key=".$this->getConfig("emailValidationMailboxLayerConfig")["apiKey"].
			"&email=".$email.
			"&smtp=1".
			"&format=1"
		);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_POST, false);
		if (!$result = curl_exec($ch)) {
			Engine::e()->SystemLog->event(new SystemLogEventError([
				"description" => "Error when trying to curl Mailboxlayer API to validate an email",
				"data" => [
					"Email" => $email,
					"Curl error" => curl_error($ch)
				]
			]));
			return new \Cherrycake\Classes\ResultKo;
		}

		if (!$result) {
			$this->system_log_and_email("Error while trying to verify an email via Mailboxlayer", array("Email" => $email, "Mailboxlayer error" => curl_error($ch)));
			return new \Cherrycake\Classes\ResultOk;
		}

		curl_close($ch);

		$result = json_decode($result);

		$isValid =
			$result->format_valid
			&&
			$result->mx_found
			&&
			$result->smtp_check
			&&
			!$result->disposable;

		if (!$isValid) {
			if ($result->did_you_mean)
				return new \Cherrycake\Classes\ResultKo([
					"didYouMean" => $result->did_you_mean
				]);
			return new \Cherrycake\Classes\ResultKo;
		}
		else
			return new \Cherrycake\Classes\ResultOK;
	}

	/**
	 * Validates the strength of the given password
	 * @param string $password The password to validate
	 * @param string $login The login associated with this password, used for some validations
	 * @return array A Result object
	 */
	function passwordStrength(
		string $password,
		string|bool $login = false
	) {
		$resultInfo["weaknesses"] = [];

		if (
			$this->getConfig("passwordStrengthValidationMinChars")
			&&
			strlen($password) < $this->getConfig("passwordStrengthValidationMinChars")
		)
			$resultInfo["weaknesses"][] = \Cherrycake\Modules\Validate\Validate::PASSWORD_STRENGTH_WEAKNESS_TOO_SHORT;

		if (
			$this->getConfig("passwordStrengthValidationIsAtLeastOneNumber")
			&&
			!preg_match("#[0-9]+#", $password)
		)
			$resultInfo["weaknesses"][] = \Cherrycake\Modules\Validate\Validate::PASSWORD_STRENGTH_WEAKNESS_AT_LEAST_ONE_NUMBER;

		if (
			$this->getConfig("passwordStrengthValidationIsAtLeastOneLetter")
			&&
			!preg_match("#[a-zA-Z]+#", $password)
		)
			$resultInfo["weaknesses"][] = \Cherrycake\Modules\Validate\Validate::PASSWORD_STRENGTH_WEAKNESS_AT_LEAST_ONE_LETTER;

		if (
			$this->getConfig("passwordStrengthValidationIsRequireUppercaseAndLowercase")
			&&
			(
				!preg_match("#[A-Z]+#", $password)
				||
				!preg_match("#[a-z]+#", $password)
			)
		)
			$resultInfo["weaknesses"][] = \Cherrycake\Modules\Validate\Validate::PASSWORD_STRENGTH_WEAKNESS_UPPERCASE_AND_LOWERCASE;

		if (
			$this->getConfig("passwordStrengthValidationIsRequireNotEqualToLogin")
			&&
			$login
			&&
			strtolower($password) == strtolower($login)
		)
			$resultInfo["weaknesses"][] = \Cherrycake\Modules\Validate\Validate::PASSWORD_STRENGTH_WEAKNESS_MATCHES_USERNAME;

		if ($resultInfo["weaknesses"])
			return new \Cherrycake\Classes\ResultKo($resultInfo);
		else
			return new \Cherrycake\Classes\ResultOk;
	}

}
