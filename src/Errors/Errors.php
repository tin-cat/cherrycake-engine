<?php

namespace Cherrycake\Errors;

/**
 * Module to manage application and core errors.
 * Errors will be shown on screen if isDevel is set to true or if client's IP is on underMaintenanceExceptionIps, both variables from config/cherrycake.config.php
 *
 * Configuration example for patterns.config.php:
 * <code>
 * $errorsConfig = [
 *  "isHtmlOutput" => true, // Whether to dump HTML formatted errors or not when not using a pattern to show errors. Defaults to true
 * 	"patternNames" => [
 *		\Cherrycake\ERROR_SYSTEM => "errors/error.html",
 *		\Cherrycake\ERROR_APP => "errors/error.html",
 *		\Cherrycake\ERROR_NOT_FOUND => "errors/error.html"
 *		\Cherrycake\ERROR_NO_PERMISSION => "errors/error.html"
 *	], // An array of pattern names to user when an error occurs. If a patterns is not specified, a generic error is triggered.
 * 	"isLogSystemErrors" => true, // Whether or not to log system errors. Defaults to true
 * 	"isLogAppErrors" => true // Whether or not to log app errors.  Defaults to true
 *	"isLogNotFoundErrors" => false // Whether or not to log "Not found" errors. Defaults to false
 *	"isLogNoPermissionErrors" => false // Whether or not to log "No permission errors. Defaults to false
 *  "isEmailSystemErrors" => true, // Whether or not to email system errors. Defaults to true
 *  "isEmailAppErrors" => false, // Whether or not to email app errors. Defaults to false
 *  "isEmailNotFoundErrors" => false, // Whether or not to email "Not found" errors. Defaults to false
 *  "isEmailNoPermissionErrors" => false, // Whether or not to email "No permission" errors. Defaults to false
 *  "notificationEmail" => false // The email address to send the error report.
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Errors  extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"isHtmlOutput" => true,
		"patternName" => [
			\Cherrycake\ERROR_SYSTEM => "errors/error.html",
			\Cherrycake\ERROR_APP => "errors/error.html",
			\Cherrycake\ERROR_NOT_FOUND => "errors/error.html",
			\Cherrycake\ERROR_NO_PERMISSION => "errors/error.html"
		],
		"isLogSystemErrors" => true,
		"isLogAppErrors" => true,
		"isLogNotFoundErrors" => false,
		"isLogNoPermissionErrors" => false,
		"isEmailSystemErrors" => true,
		"isEmailAppErrors" => false,
		"isEmailNotFoundErrors" => false,
		"isEmailNoPermissionErrors" => false,
		"notificationEmail" => false
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Output"
	];

	/**
	 * Initializes the module
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
		if (!parent::init())
			return false;

		return true;
	}

	/**
	 * To be called when an error is detected.
	 * @param integer $type The error type, one of the available error types. Private errors are meant to not be shown to the user in production state. Public errors are meant to be shown to the user.
	 * @param string $subType Code to easily group this type or errors later
	 * @param string $description Description of the error
	 * @param array $variables Additional variables relevant to the error.
	 * @param bool|null $isForceLog Whether to force this error to be logged or to not be logged in SystemLog even if isLogSystemErrors and/or isLogAppErrors is set to false. Defaults to null, which means that it must obey isLogSystemErrors and isLogAppErrors
	 * @param bool $isSilent If set to true, nothing will be outputted. Used for only logging and/or sending email notification of the error
	 */
	function trigger(
		int $type,
		string $subType = '',
		string $description = '',
		array $variables = [],
		bool|null $isForceLog = null,
		bool $isSilent = false
	) {
		global $e;

		if (is_array($description))
			$description = print_r($description, true);

		// Build error backtrace array
		$backtrace = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS);

		for ($i=0; $i<sizeof($backtrace); $i++)
			$backtrace_info[] =
				(isset($backtrace[$i]["file"]) ? $backtrace[$i]["file"] : "Unknown file").
					":".
					"<b>".(isset($backtrace[$i]["line"]) ? $backtrace[$i]["line"] : "Unknown line")."</b>".
					(isset($backtrace[$i]["class"]) ?
						" Class: ".
						"<b>".$backtrace[$i]["class"]."</b>"
					: null).
					(isset($backtrace[$i]["function"]) ?
						" Method: ".
						"<b>".$backtrace[$i]["function"]."</b>"
					: null);

		if (
			$e->isModuleLoaded("SystemLog")
			&&
			(
				($type == \Cherrycake\ERROR_SYSTEM && $this->getConfig("isLogSystemErrors"))
				||
				($type == \Cherrycake\ERROR_APP && $this->getConfig("isLogAppErrors"))
				||
				($type == \Cherrycake\ERROR_NOT_FOUND && $this->getConfig("isLogNotFoundErrors"))
				||
				($type == \Cherrycake\ERROR_NO_PERMISSION && $this->getConfig("isLogNoPermissionErrors"))
				||
				isset($isForceLog) && $isForceLog == true
			)
		)
			$e->SystemLog->event(new \Cherrycake\SystemLog\SystemLogEventError([
				"subType" => isset($subType) ? $subType : false,
				"description" => isset($description) ? $description : false,
				"data" => isset($variables) ? $variables : false
			]));

		if (
			($type == \Cherrycake\ERROR_SYSTEM && $this->getConfig("isEmailSystemErrors"))
			||
			($type == \Cherrycake\ERROR_APP && $this->getConfig("isEmailAppErrors"))
			||
			($type == \Cherrycake\ERROR_NOT_FOUND && $this->getConfig("isEmailNotFoundErrors"))
			||
			($type == \Cherrycake\ERROR_NO_PERMISSION && $this->getConfig("isEmailNoPermissionErrors"))
			||
			isset($isForceEmail) && $isForceEmail == true
		)
			$this->emailNotify([
				"description" => isset($description) ? $description : false,
				"variables" => isset($variables) ? $variables : false,
				"backtrace" => implode("<br>Backtrace: ", $backtrace_info)
			]);

		if (isset($isSilent) && $isSilent && !$e->isDevel())
			return;

		$patternNames = $this->getConfig("patternNames");

		if ($e->isCli()) {
			echo
				\Cherrycake\ANSI_LIGHT_RED."🧁 Cherrycake ".\Cherrycake\ANSI_LIGHT_BLUE."cli\n".
				\Cherrycake\ANSI_WHITE.$e->getAppName()." ".[
					\Cherrycake\ERROR_SYSTEM => \Cherrycake\ANSI_RED."System error",
					\Cherrycake\ERROR_APP => \Cherrycake\ANSI_ORANGE."App error",
					\Cherrycake\ERROR_NOT_FOUND => \Cherrycake\ANSI_PURPLE."Not found",
					\Cherrycake\ERROR_NO_PERMISSION => \Cherrycake\ANSI_CYAN."No permission"
				][$type]."\n".
				\Cherrycake\ANSI_NOCOLOR.
				(isset($subType) ? \Cherrycake\ANSI_DARK_GRAY."Subtype: ".\Cherrycake\ANSI_WHITE.$subType."\n" : null).
				(isset($description) ? \Cherrycake\ANSI_DARK_GRAY."Description: ".\Cherrycake\ANSI_WHITE.$description."\n" : null).
				(isset($variables) ?
					\Cherrycake\ANSI_DARK_GRAY."Variables:\n".\Cherrycake\ANSI_WHITE.
					substr(print_r($variables, true), 8, -3).
					"\n"
				: null).
				($e->isDevel() ? \Cherrycake\ANSI_DARK_GRAY."Backtrace:\n".\Cherrycake\ANSI_YELLOW.strip_tags(implode("\n", $backtrace_info))."\n" : null);
				\Cherrycake\ANSI_NOCOLOR;
			return;
		}

		// If this error generated before we couldn't get a action
		if (!$e->Actions->currentAction) {
			$outputType = "pattern";
		}
		else {
			switch (get_class($e->Actions->currentAction)) {
				case "Cherrycake\ActionHtml":
					$outputType = "pattern";
					break;
				case "Cherrycake\ActionAjax":
					$outputType = "ajax";
					break;
				default:
					$outputType = "plain";
					break;
			}
		}

		switch ($outputType) {

			case "pattern":
				if (isset($patternNames[$type])) {
					$e->loadCoreModule("Patterns");
					$e->loadCoreModule("HtmlDocument");

					$e->Patterns->out(
						$patternNames[$type],
						[
							"variables" => [
								"type" => $type,
								"description" => isset($description) ? $description : false,
								"variables" => isset($variables) ? $variables : false,
								"backtrace" => $backtrace
							]
						],
						[
							\Cherrycake\ERROR_SYSTEM => \Cherrycake\Output\RESPONSE_INTERNAL_SERVER_ERROR,
							\Cherrycake\ERROR_APP => \Cherrycake\Output\RESPONSE_INTERNAL_SERVER_ERROR,
							\Cherrycake\ERROR_NOT_FOUND => \Cherrycake\Output\RESPONSE_NOT_FOUND,
							\Cherrycake\ERROR_NO_PERMISSION => \Cherrycake\Output\RESPONSE_NO_PERMISSION
						][$type]
					);
				}
				else {
					if ($e->isDevel()) {
						if ($this->getConfig("isHtmlOutput")) {

							$variables = "";

							if (isset($variables)) {
								foreach ($variables as $key => $value) {
									$variables .= "<br><b>".$key."</b>: ".(is_array($value) ? json_encode($value) : $value);
								}
							}

							trigger_error(
								$description.$variables,
								[
									\Cherrycake\ERROR_SYSTEM => E_USER_ERROR,
									\Cherrycake\ERROR_APP => E_USER_ERROR,
									\Cherrycake\ERROR_NOT_FOUND => E_USER_ERROR,
									\Cherrycake\ERROR_NO_PERMISSION => E_USER_ERROR
								][$type]
							);
						}
						else {

							echo
								"Error: ".$description." in ".$backtrace_info[0];
						}
					}
					else {
						if ($this->getConfig("isHtmlOutput"))
							echo
								"<div style=\"margin: 10px; padding: 10px; background-color: crimson; border-bottom: solid #720 1px; color: #fff; font-family: Calibri, Sans-serif; font-size: 11pt; -webkit-border-radius: 5px; -border-radius: 5px; -moz-border-radius: 5px;\">".
									"<b>Error</b> ".
								"</div>";
						else
							echo
								"Error";

					}
				}
				break;

			case "ajax":

				if ($e->isDevel()) {
					$ajaxResponse = new \Cherrycake\AjaxResponseJson([
						"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR,
						"description" =>
							"Cherrycake Error / ".$e->getAppName()." / ".[
								\Cherrycake\ERROR_SYSTEM => "System error",
								\Cherrycake\ERROR_APP => "App error",
								\Cherrycake\ERROR_NOT_FOUND => "Not found",
								\Cherrycake\ERROR_NO_PERMISSION => "No permission"
							][$type]."<br>".
							($subType ? "Subtype: ".$subType."<br>" : null).
							($description ? "Description: ".$description."<br>" : null).
							($variables ? "Variables:<br>".print_r($variables, true)."<br>" : null).
							"Backtrace:<br>".strip_tags(implode("<br>", $backtrace_info)),
						"messageType" => \Cherrycake\AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL
					]);
					$ajaxResponse->output();
				}
				else {
					$ajaxResponse = new \Cherrycake\AjaxResponseJson([
						"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR,
						"description" => "Sorry, we've got an unexpected error",
						"messageType" => \Cherrycake\AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL
					]);
					$ajaxResponse->output();
				}
				break;

			case "plain":
				if ($e->isDevel()) {
					$e->Output->setResponse(new \Cherrycake\Actions\ResponseTextHtml(
						code: \Cherrycake\Output\RESPONSE_INTERNAL_SERVER_ERROR,
						payload:
							"Cherrycake Error / ".$e->getAppName()." / ".[
								\Cherrycake\ERROR_SYSTEM => "System error",
								\Cherrycake\ERROR_APP => "App error",
								\Cherrycake\ERROR_NOT_FOUND => "Not found",
								\Cherrycake\ERROR_NO_PERMISSION => "No permission"
							][$type]."\n".
							($subType ?? false ? "Subtype: ".$subType."\n" : null).
							($description ?? false ? "Description: ".$description."\n" : null).
							($variables ?? false ? "Variables:\n".print_r($variables, true)."\n" : null).
							"Backtrace:\n".strip_tags(implode("\n", $backtrace_info))
					));
				}
				else {
					$e->Output->setResponse(new \Cherrycake\Actions\ResponseTextHtml(
						code: \Cherrycake\Output\RESPONSE_INTERNAL_SERVER_ERROR,
						payload: "Error"
					));
				}
				break;
		}


		$e->end();
		die;
	}

	/**
	 * emailNotify
	 *
	 * Sends an email to the configured "notificationEmail"
	 *
	 * @param mixed $data A hash array of data to include in the notification, or a simple string
	 */
	function emailNotify($data) {
		global $e;

		$message = "";

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$message .= "<p><b>".$key.":</b><br><ul>";
					foreach ($value as $key2 => $value2) {
						if (is_array($value2)) {
							$message .= "<b>".$key2."</b><pre>".print_r($value2, true)."</pre>";
						}
						else
							$message .= "<p><b>".$key2.":</b><br>".$value2."</p>";
					}
					$message .= "</ul>";
				}
				else
					$message .= "<p><b>".$key.":</b><br>".$value."</p>";
			}
		}
		else
			$message = $data;

		$e->loadCoreModule("Email");
		$e->Email->send(
			[[$this->getConfig("notificationEmail")]],
			"[".$e->getAppNamespace()."] Error",
			[
				"contentHTML" => $message
			]
		);
	}
}
