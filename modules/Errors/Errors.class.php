<?php

/**
 * Errors
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const ERROR_SYSTEM = 0; // Errors caused by bad programming
const ERROR_APP = 1; // Errors caused by bad usering
const ERROR_NOT_FOUND = 2; // Errors caused when something requested was not found
const ERROR_NO_PERMISSION = 3; // Errors causes when the user didn't have permission to access what they've requested

/**
 * Errors
 *
 * Module to manage application errors in a neat way.
 * It takes configuration from the App-layer configuration file.
 * Errors will be shown on screen if IS_DEVEL_ENVIRONMENT is set to true or if client's IP is on $underMaintenanceExceptionIps, both variables from config/cherrycake.config.php
 *
 * Configuration example for patterns.config.php:
 * <code>
 * $errorsConfig = [
 *  "isHtmlOutput" => true, // Whether to dump HTML formatted errors or not when not using a pattern to show errors. Defaults to true
 * 	"patternNames" => [
 *		\Cherrycake\Modules\ERROR_SYSTEM => "errors/error.html",
 *		\Cherrycake\Modules\ERROR_APP => "errors/error.html",
 *		\Cherrycake\Modules\ERROR_NOT_FOUND => "errors/error.html"
 *		\Cherrycake\Modules\ERROR_NO_PERMISSION => "errors/error.html"
 *	], // An array of pattern names to user when an error occurs. If a patterns is not specified, a generic error is triggered.
 * 	"isLogSystemErrors" => true, // Whether or not to log system errors. Defaults to true
 * 	"isLogAppErrors" => true // Whether or not to log app errors.  Defaults to true
 *	"isLogNotFoundErrors" => false // Whether or not to log "Not found" errors. Defaults to false
 *	"isLogNoPermissionErrors" => false // Whether or not to log "No permission errors. Defaults to false
 *  "isEmailSystemErrors" => true, // Whether or not to email system errors. Defaults to true
 *  "isEmailAppErrors" => false, // Whether or not to email app errors. Defaults to false
 *  "isEmailNotFoundErrors" => false, // Whether or not to email "Not found" errors. Defaults to false
 *  "isEmailNoPermissionErrors" => false, // Whether or not to email "No permission" errors. Defaults to false
 *  "notificationEmail" => ADMIN_TECHNICAL_EMAIL // The email address to send the error report. Defaults to ADMIN_TECHNICAL_EMAIL
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Errors extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"isHtmlOutput" => true,
		"patternName" => [
			ERROR_SYSTEM => "errors/error.html",
			ERROR_APP => "errors/error.html",
			ERROR_NOT_FOUND => "errors/error.html",
			ERROR_NO_PERMISSION => "errors/error.html"
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
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Output",
		"SystemLog",
		"Locale",
		"Email"
	];

	/**
	 * init
	 *
	 * Initializes the module and sets the PHP error level
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		return true;
	}

	/**
	 * trigger
	 *
	 * To be called when an error is detected.
	 *
	 * @param integer $errorType The error type, one of the available error types. Private errors are meant to not be shown to the user in production state. Public errors are meant to be shown to the user.
	 * @param array $setup Additional setup with the following possible keys:
	 * * errorSubType: Additional, optional string code to easily group this type or errors later
	 * * errorDescription: Additional, optional description of the error
	 * * errorVariables: A hash array of additional variables relevant to the error.
	 * * isForceLog: Whether to force this error to be logged or to not be logged in SystemLog even if isLogSystemErrors and/or isLogAppErrors is set to false. Defaults to null, which means that it must obey isLogSystemErrors and isLogAppErrors
	 * * isSilent: If set to true, nothing will be outputted. Used for only logging and/or sending email notification of the error
	 */
	function trigger($errorType, $setup = false) {
		global $e;

		if (is_array($setup["errorDescription"]))
			$setup["errorDescription"] = print_r($setup["errorDescription"], true);

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
			($errorType == ERROR_SYSTEM && $this->getConfig("isLogSystemErrors"))
			||
			($errorType == ERROR_APP && $this->getConfig("isLogAppErrors"))
			||
			($errorType == ERROR_NOT_FOUND && $this->getConfig("isLogNotFoundErrors"))
			||
			($errorType == ERROR_NO_PERMISSION && $this->getConfig("isLogNoPermissionErrors"))
			||
			isset($setup["isForceLog"]) && $setup["isForceLog"] == true
		)
			$e->SystemLog->event(new \Cherrycake\SystemLogEventError([
				"subType" => isset($setup["errorSubType"]) ? $setup["errorSubType"] : false,
				"description" => isset($setup["errorDescription"]) ? $setup["errorDescription"] : false,
				"data" => isset($setup["errorVariables"]) ? $setup["errorVariables"] : false
			]));

		if (
			($errorType == ERROR_SYSTEM && $this->getConfig("isEmailSystemErrors"))
			||
			($errorType == ERROR_APP && $this->getConfig("isEmailAppErrors"))
			||
			($errorType == ERROR_NOT_FOUND && $this->getConfig("isEmailNotFoundErrors"))
			||
			($errorType == ERROR_NO_PERMISSION && $this->getConfig("isEmailNoPermissionErrors"))
			||
			isset($setup["isForceEmail"]) && $setup["isForceEmail"] == true
		)
			$this->emailNotify([
				"errorDescription" => isset($setup["errorDescription"]) ? $setup["errorDescription"] : false,
				"errorVariables" => isset($setup["errorVariables"]) ? $setup["errorVariables"] : false,
				"backtrace" => implode("<br>Backtrace: ", $backtrace_info)
			]);

		if (isset($setup["isSilent"]) && $setup["isSilent"] && !IS_DEVEL_ENVIRONMENT)
			return;

		$patternNames = $this->getConfig("patternNames");

		if (IS_CLI) {
			echo
				\Cherrycake\ANSI_LIGHT_RED."🧁 Cherrycake ".\Cherrycake\ANSI_LIGHT_BLUE."cli\n".
				\Cherrycake\ANSI_WHITE.\Cherrycake\APP_NAME." ".[
					ERROR_SYSTEM => \Cherrycake\ANSI_RED."System error",
					ERROR_APP => \Cherrycake\ANSI_ORANGE."App error",
					ERROR_NOT_FOUND => \Cherrycake\ANSI_PURPLE."Not found",
					ERROR_NO_PERMISSION => \Cherrycake\ANSI_CYAN."No permission"
				][$errorType]."\n".
				\Cherrycake\ANSI_NOCOLOR.
				(isset($setup["errorSubType"]) ? \Cherrycake\ANSI_DARK_GRAY."Subtype: ".\Cherrycake\ANSI_WHITE.$setup["errorSubType"]."\n" : null).
				(isset($setup["errorDescription"]) ? \Cherrycake\ANSI_DARK_GRAY."Description: ".\Cherrycake\ANSI_WHITE.$setup["errorDescription"]."\n" : null).
				(isset($setup["errorVariables"]) ?
					\Cherrycake\ANSI_DARK_GRAY."Variables:\n".\Cherrycake\ANSI_WHITE.
					substr(print_r($setup["errorVariables"], true), 8, -3).
					"\n"
				: null).
				(IS_DEVEL_ENVIRONMENT ? \Cherrycake\ANSI_DARK_GRAY."Backtrace:\n".\Cherrycake\ANSI_YELLOW.strip_tags(implode("\n", $backtrace_info))."\n" : null);
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
				if (isset($patternNames[$errorType])) {
					$e->loadCherrycakeModule("Patterns");
					$e->loadCherrycakeModule("HtmlDocument");

					$e->Patterns->out(
						$patternNames[$errorType],
						[
							"variables" => [
								"errorType" => $errorType,
								"errorDescription" => isset($setup["errorDescription"]) ? $setup["errorDescription"] : false,
								"errorVariables" => isset($setup["errorVariables"]) ? $setup["errorVariables"] : false,
								"backtrace" => $backtrace
							]
						],
						[
							ERROR_SYSTEM => \Cherrycake\Modules\RESPONSE_INTERNAL_SERVER_ERROR,
							ERROR_NOT_FOUND => \Cherrycake\Modules\RESPONSE_NOT_FOUND,
							ERROR_NO_PERMISSION => \Cherrycake\Modules\RESPONSE_NO_PERMISSION
						][$errorType]
					);
				}
				else {
					if (IS_DEVEL_ENVIRONMENT) {
						if ($this->getConfig("isHtmlOutput")) {

							$errorVariables = "";

							if (isset($setup["errorVariables"]))
								foreach ($setup["errorVariables"] as $key => $value)
									$errorVariables .= "<br><b>".$key."</b>: ".$value;

							trigger_error($setup["errorDescription"].$errorVariables);
						}
						else {

							echo
								"Error: ".$setup["errorDescription"]." in ".$backtrace_info[0];
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
			
				if (IS_DEVEL_ENVIRONMENT) {
					$ajaxResponse = new \Cherrycake\AjaxResponseJson([
						"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR,
						"description" =>
							"Cherrycake Error / ".\Cherrycake\APP_NAME." / ".[
								ERROR_SYSTEM => "System error",
								ERROR_APP => "App error",
								ERROR_NOT_FOUND => "Not found",
								ERROR_NO_PERMISSION => "No permission"
							][$errorType]."<br>".
							($setup["errorSubType"] ? "Subtype: ".$setup["errorSubType"]."<br>" : null).
							($setup["errorDescription"] ? "Description: ".$setup["errorDescription"]."<br>" : null).
							($setup["errorVariables"] ? "Variables:<br>".print_r($setup["errorVariables"], true)."<br>" : null).
							"Backtrace:<br>".strip_tags(implode($backtrace_info, "<br>")),
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
				if (IS_DEVEL_ENVIRONMENT) {
					$e->Output->setResponse(new \Cherrycake\ResponseTextHtml([
						"code" => \Cherrycake\Modules\RESPONSE_INTERNAL_SERVER_ERROR,
						"payload" =>
							"Cherrycake Error / ".\Cherrycake\APP_NAME." / ".[
								ERROR_SYSTEM => "System error",
								ERROR_APP => "App error",
								ERROR_NOT_FOUND => "Not found",
								ERROR_NO_PERMISSION => "No permission"
							][$errorType]."\n".
							($setup["errorSubType"] ? "Subtype: ".$setup["errorSubType"]."\n" : null).
							($setup["errorDescription"] ? "Description: ".$setup["errorDescription"]."\n" : null).
							($setup["errorVariables"] ? "Variables:\n".print_r($setup["errorVariables"], true)."\n" : null).
							"Backtrace:\n".strip_tags(implode($backtrace_info, "\n"))
					]));
				}
				else {
					$e->Output->setResponse(new \Cherrycake\ResponseTextHtml([
						"code" => \Cherrycake\Modules\RESPONSE_INTERNAL_SERVER_ERROR,
						"payload" => "Error"
					]));
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

		$e->Email->send(
			[[$this->getConfig("notificationEmail")]],
			"[".$e->getAppNamespace()."] Error",
			[
				"contentHTML" => $message
			]
		);
	}
}