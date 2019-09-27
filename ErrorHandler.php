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
	if (IS_CLI) {
		$message = "Cherrycake error\nType: ".$errNo."\nMessage: ".$errStr."\nFile: ".$errFile."\nLine: ".$errLine."\n";
		echo $message;
	}
	else
	if (IS_DEVEL_ENVIRONMENT) {
		$html =
		"
			<style>
				.errorReport .cherrycakeLogo {
					width: 45px;
					height: 45px;
					background-size: contain;
					background-repeat: no-repeat;
					background-position: center;
					background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjIuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHdpZHRoPSI1MTJweCIgaGVpZ2h0PSI2MTlweCIgdmlld0JveD0iMCAwIDUxMiA2MTkiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDUxMiA2MTk7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojRUY0OTc0O30KCS5zdDF7ZmlsbDojRjE2QjdGO30KCS5zdDJ7ZmlsbDojRkZBQkNEO30KCS5zdDN7ZmlsbDojRkY4RkI0O30KCS5zdDR7ZmlsbDojRkZDMkUxO30KCS5zdDV7ZmlsbDojQ0NGOEZGO30KCS5zdDZ7ZmlsbDojOUZGNkZGO30KCS5zdDd7ZmlsbDojMjhCQ0I3O30KPC9zdHlsZT4KPGNpcmNsZSBjbGFzcz0ic3QwIiBjeD0iMjMxLjYiIGN5PSIyNDEiIHI9IjY4LjkiLz4KPHBhdGggY2xhc3M9InN0MSIgZD0iTTIzNy40LDE3Mi40Yy0yMiwxNC45LTM2LjUsNDAuMS0zNi41LDY4LjZzMTQuNSw1My43LDM2LjUsNjguNmMzNS4zLTIuOSw2My4xLTMyLjUsNjMuMS02OC42CglTMjcyLjcsMTc1LjMsMjM3LjQsMTcyLjR6Ii8+CjxwYXRoIGNsYXNzPSJzdDIiIGQ9Ik0xMzcuNCwzNTBjMCwwLTIzLjQtNjguNyw0MC44LTg5LjljNjQuMi0yMS4xLDExOS4zLDQuNSwxMzkuNy0zNGMwLDAsNTUuOSwyNS43LDQ1LjMsODUuNAoJYzAsMC0xNS44LDUzLjYtMTA3LjIsNTMuNkMxNjQuNiwzNjUuMiwxMzcuNCwzNTAsMTM3LjQsMzUweiIvPgo8cGF0aCBjbGFzcz0ic3QzIiBkPSJNMjEyLjcsMjUyLjVjLTExLjEsMS40LTIyLjYsMy43LTM0LjUsNy42QzExNCwyODEuMywxMzcuNCwzNTAsMTM3LjQsMzUwczIzLDEyLjgsOTcuNywxNC44CglDMTkzLjIsMzIwLjEsMjEyLjcsMjUyLjUsMjEyLjcsMjUyLjV6Ii8+CjxwYXRoIGNsYXNzPSJzdDQiIGQ9Ik0xMTcsNDM5LjJjMCwwLTMxLjctNTguOSw5LjgtOTguOXMxODUuMSwzNi4zLDIzNi40LTI4LjdjMC44LTAuOCw2NywzMy4xLDMxLDEyNy42CglDMzk0LjIsNDM5LjksMTE3LDQzOS4yLDExNyw0MzkuMnoiLz4KPHBhdGggY2xhc3M9InN0MiIgZD0iTTIxNC4yLDMzMS43Yy0zNy4yLTQuOC03MC42LTcuNi04Ny40LDguNmMtNDEuNSw0MC05LjgsOTguOS05LjgsOTguOXM1MS4zLDAuMywxMjkuOCwwLjMKCUMxODkuNSw0MDcuOSwyMTQuMiwzMzEuNywyMTQuMiwzMzEuN3oiLz4KPHBhdGggY2xhc3M9InN0NSIgZD0iTTQwNC44LDQwNi41TDM3Niw0MjYuM2MtNi41LDQuNS0xNS4xLDQuOC0yMS45LDAuN2wtMzEuNi0xOWMtNi4zLTMuOC0xNC4yLTMuOS0yMC42LTAuM0wyNjYsNDI4CgljLTYuMiwzLjUtMTMuOCwzLjUtMjAsMGwtMzUuOS0yMC40Yy02LjQtMy42LTE0LjMtMy41LTIwLjYsMC4zbC0zMS42LDE5Yy02LjgsNC4xLTE1LjQsMy44LTIxLjktMC43bC0yOC44LTE5LjkKCWMtOC01LjUtMTguNiwxLjYtMTYuNSwxMS4xTDExOSw1NTBjNC44LDIyLjcsMjQuOSwzOC45LDQ4LDM4LjlIMzQ1YzIzLjIsMCw0My4yLTE2LjIsNDgtMzguOWwyOC4zLTEzMi41CglDNDIzLjQsNDA4LDQxMi44LDQwMSw0MDQuOCw0MDYuNXoiLz4KPHBhdGggY2xhc3M9InN0NiIgZD0iTTI0Niw0MjhsLTM1LjktMjAuNGMtNi40LTMuNi0xNC4zLTMuNS0yMC42LDAuM2wtMzEuNiwxOWMtMy40LDIuMS03LjQsMy0xMS4yLDIuOWwzNS4xLDE1OWg3My40VjQzMC43CglDMjUyLDQzMC41LDI0OC45LDQyOS43LDI0Niw0Mjh6Ii8+CjxwYXRoIGNsYXNzPSJzdDYiIGQ9Ik0zOTMsNTUwbDI4LjMtMTMyLjVjMi05LjUtOC42LTE2LjYtMTYuNS0xMS4xTDM3Niw0MjYuM2MtMy43LDIuNS04LDMuNy0xMi4zLDMuNWwtMzUuMSwxNTlIMzQ1CglDMzY4LjEsNTg4LjksMzg4LjIsNTcyLjcsMzkzLDU1MHoiLz4KPHBhdGggY2xhc3M9InN0NyIgZD0iTTIxOS4zLDE4MS42YzIuNi0xLjIsMi4yLTUuNSwyLjItNS41cy0yMy41LTQ1LjEtMTUtOTEuNnMxMS4xLTQ5LjksMTEuMi01Mi41YzAuMS0yLjctMC40LTMuNi0zLjctNC41CgljLTQuNS0xLjItOS40LTAuNS0xMCwyLjRjLTAuNiwyLjYtMTAuNCw1Ni42LTYuOCw4NmMzLDI0LjgsMTYuNCw2Mi42LDE3LjksNjQuMVMyMTcuNCwxODIuNCwyMTkuMywxODEuNnoiLz4KPC9zdmc+Cg==');
				}
				.errorReport {
					text-align: left;
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
				}
				.errorReport > table.error td {
					padding: 10pt;
					border-bottom: solid #c15 1px;
					vertical-align: top;
					background: white;
				}
				.errorReport > table.error tr:last-child > td {
					border-bottom: none;
				}
				.errorReport > table.error th {
					font-weight: normal;
					padding: 5pt 10pt;
					color: white;
					text-align: left;
				}
				.errorReport > table.error th.title {
					font-size: 14pt;
					line-height: 45px;
				}
				.errorReport > table.error th .cherrycakeLogo {
					float: left;
					vertical-align: middle;
					margin-right: 8pt;
				}
				.errorReport > table.error td.key {
					color: #c15;
				}
				.errorReport > table.error td.value {
				}
				.errorReport > table.error td.stack {
					padding: 0;
				}
				.errorReport table {
					width: 100%;
					outline: solid red 1px;
				}
				.errorReport .stack .call {
					padding: 10pt;
					color: #c15;
					border-bottom: solid #c15 1px;
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
				}
				.errorReport .source > .line {
					position: relative;
					clear: both;
					white-space: nowrap;
				}
				.errorReport .source > .line:nth-child(even) {
					background: rgba(0, 0, 0, 0.03);
				}
				.errorReport .source > .line.highlighted {
					background: rgba(255, 190, 0, 0.2);
				}
				.errorReport .source > .line.highlighted > .number:after {
					position: absolute;
					content: '►';
					left: -1.3em;
					top: 0px;
					line-height: 1em;
					color: rgba(255, 190, 0, 0.2);
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
					border-left: solid rgba(0, 0, 0, 0.05) 2px;
					padding-left: 1em;
					margin-left: 0.5em;
				}
			</style>
			<div class='errorReport'>
			<table class='error' border=0 cellpadding=0 cellspacing=0>
				<tr><th colspan=2 class='title'>
					<div class='cherrycakeLogo'></div>
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
				</th></tr>
				<tr>
					<td class='value' colspan=2>".nl2br($errStr)."</td>
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
				$e->Patterns
			) {
				$patternParsingErrorLine = $errLine;
				global $e;
				$errFile = $e->Patterns->getLastTreatedFile();
				$sourceLines = explode("\n", $e->Patterns->getLastEvaluatedCode());
			}
			else {
				$filename = substr($errFile, 0, strpos($errFile, ".php")+4);
				if (is_readable($filename))
					$sourceLines = explode("<br />", highlight_string(file_get_contents($filename), true));
			}

			if (is_array($sourceLines)) {
				$highlightedSource = "<div class='source'>";
				$lineNumber = 1;
				foreach ($sourceLines as $line) {
					if ($lineNumber >= $errLine - 10 && $lineNumber <= $errLine + 10)
						$highlightedSource .= "<div class='line".($lineNumber == $errLine ? " highlighted" : "")."'><div class='number'>".$lineNumber."</div><div class='code'>".$line."</div></div>";
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

		if (is_array($stack)) {
			$stack = array_reverse($stack);
			$html .=
			"
				<tr>
					<td class='key'>Stack</td>
					<td class='stack'>
			";
			$count = 0;
			foreach ($stack as $stackItem) {
				$html .=
					"<div class='call'>\n".
						(++$count)." ".
						(isset($stackItem["class"]) ? "<span class='class'>".$stackItem["class"]."</span>\n<span class='type'>".$stackItem["type"]."</span>\n" : "").
						"<span class='function'>".$stackItem["function"]."</span>\n";

					if (isset($stackItem["args"]) && is_array($stackItem["args"])) {
						$html .= "<span class='args'>(\n";
						while (list($idx, $arg) = each($stackItem["args"]))
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
					<th>
						".date("Y/n/j H:i.s")."
					</th>
				</tr>
			</table>
			</div>
		";

		echo $html;
	}
	else {
		$message = "Cherrycake error Type:".$errNo." Message:".$errStr." File:".$errFile." Line:".$errLine;
		error_log($message);
		header('HTTP/1.1 500 Internal Server Error');
		header("location: /errors/fatal.html");
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
