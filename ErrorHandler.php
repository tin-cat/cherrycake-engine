<?php

/**
 * Error handler
 *
 * @package Cherrycake
 */

namespace Cherrycake;

function logError(
	$errNo,
	$errStr = false,
	$errFile = false,
	$errLine = false,
	$errContext = false
) {
	switch ($errNo) {
		case E_ERROR:
		case E_WARNING:
		// case E_NOTICE:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_CORE_WARNING:
		case E_COMPILE_ERROR:
		case E_COMPILE_WARNING:
		case E_USER_ERROR:
		case E_USER_WARNING:
		case E_USER_NOTICE:
		default:
		
			handleError(
				$errNo,
				$errStr,
				$errFile,
				$errLine,
				$errContext,
				debug_backtrace()
			);
			break;
	}
	return true;
}

function checkForFatal() {
	if ($error = error_get_last())
		handleError(
			$error["type"],
			$error["message"],
			$error["file"],
			$error["line"]
		);
}

function handleError(
	$errNo,
	$errStr,
	$errFile = false,
	$errLine = false,
	$errContext = false,
	$stack = false
) {
	global $e;

	// Build error backtrace array
	$backtrace = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS);

	for ($i=0; $i<sizeof($backtrace); $i++)
		$backtrace_info[] =
			(isset($backtrace[$i]["file"]) ? $backtrace[$i]["file"] : null).
			(isset($backtrace[$i]["line"]) ? ":<b>".$backtrace[$i]["line"]."</b>" : null).
			(isset($backtrace[$i]["class"]) ? " ".$backtrace[$i]["class"] : null).
			(isset($backtrace[$i]["function"]) ? "::".$backtrace[$i]["function"] : null);

	if (IS_CLI) {
		echo
			\Cherrycake\ANSI_LIGHT_RED."🧁 Cherrycake ".\Cherrycake\ANSI_LIGHT_BLUE."cli\n".
			\Cherrycake\ANSI_WHITE.$e->getAppName()." Error ".\Cherrycake\ANSI_WHITE.$errNo."\n".
			\Cherrycake\ANSI_NOCOLOR.
			\Cherrycake\ANSI_DARK_GRAY."Message: ".\Cherrycake\ANSI_WHITE.$errStr."\n".
			\Cherrycake\ANSI_DARK_GRAY."File: ".\Cherrycake\ANSI_WHITE.$errFile."\n".
			\Cherrycake\ANSI_DARK_GRAY."Line: ".\Cherrycake\ANSI_WHITE.$errLine."\n".
			\Cherrycake\ANSI_NOCOLOR.
			($e->isDevel() ? \Cherrycake\ANSI_DARK_GRAY."Backtrace:\n".\Cherrycake\ANSI_YELLOW.strip_tags(implode("\n", $backtrace_info))."\n" : null);

		exit();
	}

	// Build standard HTML error
	$html = "
		<style>
			.errorReport {
				text-align: left;
				margin: 40px;
			}
			.errorReport > table.error {
				font-family: Inconsolata, 'Courier New';
				color: #000;
				font-size: 9pt;
				line-height: 1.4em;
				background: #c15;
				border: solid #c15 1px;
				border-top: none;
				width: 100%;
				border-radius: 10px;
			}
			.errorReport > table.error td {
				padding: 10pt;
				border-bottom: solid #c15 1px;
				vertical-align: top;
				background: white;
			}
			.errorReport > table.error tr:nth-last-child(2) > td {
				border-bottom: none;
			}
			.errorReport > table.error th {
				font-weight: normal;
				padding: 5pt 10pt;
				color: white;
				text-align: left;
			}
			.errorReport > table.error th.title {
			}
			.errorReport > table.error th .cherrycakeLogo {
				white-space: pre-wrap;
				white-space: -moz-pre-wrap;
				white-space: -pre-wrap;
				white-space: -o-pre-wrap;
				word-wrap: break-word;

				line-height: 1em;
				margin: 5px 0;
				
				font-family: 'Courier new';
			}
			.errorReport > table.error td.key {
				color: #c15;
				width: 1%;
			}
			.errorReport > table.error td.value {
			}
			.errorReport > table.error td.stack {
				padding: 0;
			}
			.errorReport table {
				width: 100%;
				outline: dashed red 1px;
			}
			.errorReport .stack .call {
				padding: 5pt;
				color: #c15;
			}
			.errorReport .stack .call:last-child {
				border-bottom: none;
			}
			.errorReport .stack .call > .class {
				font-weight: bold;
			}
			.errorReport .stack .call > .type {
			}
			.errorReport .stack .call > .function {
				
			}
			.errorReport .stack .call > .args {
				
			}
			.errorReport .stack .call > .args > .arg {
				color: pink;
				font-size: 9pt;
				font-weight: bold;
			}
			.errorReport .stack .line {
				color: black;
			}
			.errorReport .stack .file {
				color: #aaa;
			}
			.errorReport .source {
				margin-top: 1em;
				overflow-x: auto;
			}
			.errorReport .source > .line {
				position: relative;
				clear: both;
				white-space: nowrap;
				line-height: 1.7em;
			}
			.errorReport .source > .line:nth-child(even) {
				background: rgba(0, 0, 0, 0.03);
			}
			.errorReport .source > .line.highlighted {
				background: rgba(0, 184, 255, 0.25);
			}
			.errorReport .source > .line.highlighted > .number:after {
				position: absolute;
				content: '►';
				left: 0;
				font-size: 8pt;
				color: #fff;
			}
			.errorReport .source > .line > .number {
				display: inline-block;
				width: 50px;
				text-align: right;
				color: #aaa;
				vertical-align: top;
			}
			.errorReport .source > .line > .code {
				display: inline-block;
				white-space: normal;
				margin-left: 1em;

				font-family: Hack, Inconsolata, 'Courier New';
				font-size: 8pt;

				white-space: pre-wrap;
				white-space: -moz-pre-wrap;
				white-space: -pre-wrap;
				white-space: -o-pre-wrap;
				word-wrap: break-word;
			}
		</style>
		<div class='errorReport'>
		<table class='error' border=0 cellpadding=0 cellspacing=0>
			<tr><th colspan=2 class='head'>
				<div class='cherrycakeLogo'>".
					"🧁 Cherrycake".
				"</div>
			</th></tr>
			<tr>
				<td class='key'>
					".
						[
							E_ERROR => "Error",
							E_WARNING => "Warning",
							E_PARSE => "Parse error",
							E_NOTICE => "Notice",
							E_CORE_ERROR => "Core error",
							E_CORE_WARNING => "Core warning",
							E_COMPILE_ERROR => "Compile error",
							E_COMPILE_WARNING => "Compile warning",
							E_USER_ERROR => "User error",
							E_USER_WARNING => "User warning",
							E_USER_NOTICE => "User notice",
							E_STRICT => "Strict",
							E_RECOVERABLE_ERROR => "Recoverable error",
							E_DEPRECATED => "Deprecated",
							E_USER_DEPRECATED => "User deprecated"
						][$errNo].
					"
				</td>
				<td class='value'>
					".nl2br($errStr)."	
				</td>
			</tr>
	";

	if ($errFile) {
		
		// Check specific error for pattern parsing in order to show later the pattern itself
		if (
			(
				strstr($errFile, "patterns.class.php") !== false
				||
				strstr($errFile, "eval()'d") !== false
			)
			&&
			isset($e->Patterns)
		) {
			$patternParsingErrorLine = $errLine;
			$errFile = $e->Patterns->getLastTreatedFile();
			$sourceLines = explode("\n", $e->Patterns->getLastEvaluatedCode());
		}
		else {
			$filename = substr($errFile, 0, strpos($errFile, ".php")+4);
			if (is_readable($filename))
				$sourceLines = file($filename);
		}

		if (is_array($sourceLines)) {
			$highlightedSource = "<div class='source'>";
			$lineNumber = 1;
			foreach ($sourceLines as $line) {
				if ($lineNumber >= $errLine - 10 && $lineNumber <= $errLine + 10)
					$highlightedSource .= "<div class='line".($lineNumber == $errLine ? " highlighted" : "")."'><div class='number'>".$lineNumber."</div><div class='code'>".htmlspecialchars($line)."</div></div>";
				$lineNumber ++;
			}
			$highlightedSource .= "</div>";
		}

		$html .=
		"
			<tr>
				<td class='key'>File</td>
				<td class='value'>
					".($errLine ? "<span class='line'>Line $errLine</span> " : "").$errFile."
					".(isset($highlightedSource) ? $highlightedSource : "")."
				</td>
			</tr>
		";

	}

	if (is_array($backtrace)) {
		$backtrace = array_reverse($backtrace);
		$html .=
		"
			<tr>
				<td class='key'>Stack</td>
				<td class='stack'>
		";
		$count = 0;
		foreach ($backtrace as $stackItem) {
			$html .=
				"<div class='call'>\n".
					"&darr; ".
					(isset($stackItem["class"]) ? "<span class='class'>".$stackItem["class"]."</span>\n<span class='type'>".$stackItem["type"]."</span>\n" : "").
					"<span class='function'>".$stackItem["function"]."</span>\n";

				if (isset($stackItem["args"]) && is_array($stackItem["args"])) {
					$html .= "<span class='args'>(\n";
					foreach ($stackItem["args"] as $idx => $arg)
						$html .=
							"<span class='arg'>".
								getHtmlDebugForArg($arg).
							"</span>\n".
							($idx < sizeof($stackItem["args"])-1 ? ", " : "");
					$html .= ")</span>\n";
				}

				$html .=
					"</span>\n".
					"<br>".
					"&nbsp;&nbsp;".
					(isset($stackItem["line"]) ? "<span class='line'>Line ".number_format($stackItem["line"])."</span>\n " : "").
					(isset($stackItem["file"]) ? "<span class='file'>".$stackItem["file"]."</span>\n" : "");

				// Check for specific errors about pattern parsing, as detected above and stored on $patternParsingErrorLine
				if (
					isset($patternParsingErrorLine)
					&&
					isset($stackItem["class"]) && $stackItem["class"] == "Cherrycake\\Patterns"
					&&
					isset($stackItem["function"]) && $stackItem["function"] == "parse"
				) {

					// We have a pattern parsing error, and we're now dumping the Cherrycake\Patterns->parse method stack call

					global $e;
					$patternFileName = $e->Patterns->getPatternFileName($stackItem["args"][0]);
					
					$sourceLines = explode("<br />", highlight_string(file_get_contents($patternFileName), true));

					$highlightedSource = "<div class='source'>\n";
					$lineNumber = 1;
					foreach ($sourceLines as $line) {
						if ($lineNumber >= $errLine - 10 && $lineNumber <= $errLine + 10)
							$highlightedSource .= "<div class='line".($lineNumber == $errLine ? " highlighted" : "")."'>\n<div class='number'>".$lineNumber."</div>\n<div class='code'>".$line."</div>\n</div>\n";
						$lineNumber ++;
					}
					$highlightedSource .= "</div>\n";

					$html .= $highlightedSource;
				}

			$html .=
				"</div>";
		}
		$html .=
		"
				</td>
			</tr>
		";
	}

	$html .=
	"
			<tr>
				<th colspan=2>
					".date("Y/n/j H:i.s")."
				</th>
			</tr>
		</table>
		</div>
	";

	$currentActionClass = false;
	if (isset($e->Actions) && $e->Actions->currentAction)
		$currentActionClass = get_class($e->Actions->currentAction);

	// If we don't have the engine, dump the HTML error straightaway
	if (!$currentActionClass) {
		echo $html;
	}
	// If we have the engine and the current Action class, dump the error using it
	else if ($currentActionClass) {
		
		switch ($currentActionClass) {

			case "Cherrycake\ActionHtml":
				$response = new \Cherrycake\ResponseTextHtml([
					"payload" => $html
				]);
				break;

			case "Cherrycake\ActionAjax":
				if ($e->isDevel()) {
					$ajaxResponseJson = new \Cherrycake\AjaxResponseJson([
						"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR,
						"description" =>
							"Cherrycake Error / ".$e->getAppName()." / ".[
								E_ERROR => "Error",
								E_WARNING => "Warning",
								E_PARSE => "Parse error",
								E_NOTICE => "Notice",
								E_CORE_ERROR => "Core error",
								E_CORE_WARNING => "Core warning",
								E_COMPILE_ERROR => "Compile error",
								E_COMPILE_WARNING => "Compile warning",
								E_USER_ERROR => "User error",
								E_USER_WARNING => "User warning",
								E_USER_NOTICE => "User notice",
								E_STRICT => "Strict",
								E_RECOVERABLE_ERROR => "Recoverable error",
								E_DEPRECATED => "Deprecated",
								E_USER_DEPRECATED => "User deprecated"
							][$errNo]."<br>".
							"Description: ".$errStr."<br>".
							"File: ".$errFile."<br>".
							"Line: ".$errLine."<br>".
							"Backtrace:<br>".implode($backtrace_info, "<br>"),
						"messageType" => \Cherrycake\AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL
					]);
					$response = $ajaxResponseJson->getResponse();
				}
				else {
					$ajaxResponseJson = new \Cherrycake\AjaxResponseJson([
						"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR,
						"description" => "Sorry, we've got an unexpected error",
						"messageType" => \Cherrycake\AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL
					]);
					$response = $ajaxResponseJson->getResponse();
				}
				break;
			
			default:
				if ($e->isDevel()) {
					$response = new \Cherrycake\ResponseTextHtml([
						"code" => \Cherrycake\Modules\RESPONSE_INTERNAL_SERVER_ERROR,
						"payload" =>
							"Cherrycake Error / ".$e->getAppName()." / ".[
								E_ERROR => "Error",
								E_WARNING => "Warning",
								E_PARSE => "Parse error",
								E_NOTICE => "Notice",
								E_CORE_ERROR => "Core error",
								E_CORE_WARNING => "Core warning",
								E_COMPILE_ERROR => "Compile error",
								E_COMPILE_WARNING => "Compile warning",
								E_USER_ERROR => "User error",
								E_USER_WARNING => "User warning",
								E_USER_NOTICE => "User notice",
								E_STRICT => "Strict",
								E_RECOVERABLE_ERROR => "Recoverable error",
								E_DEPRECATED => "Deprecated",
								E_USER_DEPRECATED => "User deprecated"
							][$errNo]."\n".
							"Description: ".$errStr."\n".
							"File: ".$errFile."\n".
							"Line: ".$errLine."\n".
							"Backtrace:\n".strip_tags(implode("\n", $backtrace_info))
					]);
				}
				else {
					$response = new \Cherrycake\ResponseTextHtml([
						"code" => \Cherrycake\Modules\RESPONSE_INTERNAL_SERVER_ERROR,
						"payload" => "Error"
					]);
				}
				break;
		}

		$e->Output->sendResponse($response);
	}

	exit();
}

function getHtmlDebugForArg($arg) {
	$r = "";
	switch (gettype($arg)) {
		case "integer":
			$r .= $arg;
			break;
		case "string":
			$r .=
				(
					strlen($arg) <= 20
					?
					"\"".htmlspecialchars($arg)."\""
					:
					"\"</span>...<span class='arg'> ".htmlspecialchars(substr($arg, strlen($arg)-20))."\""
				);
			break;
		case "boolean":
			$r .= $arg ? "true" : "false";
			break;
		case "array":
			$r .= "&lt;array ".sizeof($arg)."&gt;";
			break;
		case "object":
			$r .= "&lt;".get_class($arg)."&gt;";
			break;
		default:
			$r .= "&lt;".gettype($arg)."&gt;";
	}
	return $r;
}

register_shutdown_function("\\Cherrycake\\checkForFatal");
set_error_handler("\\Cherrycake\\logError");
ini_set("display_errors", false);
error_reporting(E_ERROR | E_WARNING | E_NOTICE | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);