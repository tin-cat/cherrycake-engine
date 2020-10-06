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

	if (defined("STDIN")) {
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
				border-radius: 5px;
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
				color: white;
				text-align: left;
			}
			.errorReport > table.error th.title {
			}
			.errorReport > table.error th.head > .headMosaic {
				display: flex;
				justify-content: flex-start;
				align-items: top;
				margin: 5pt 8pt;
			}
			.errorReport > table.error th.head > .headMosaic > .logo {	
				flex-grow: 0;
				flex-basis: 50px;
				width: 50px;
				height: 60px;
				background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PHN2ZyAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIgICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgICB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiICAgd2lkdGg9IjY3bW0iICAgaGVpZ2h0PSI4MG1tIiAgIHZpZXdCb3g9IjAgMCA2NyA4MCIgICB2ZXJzaW9uPSIxLjEiICAgaWQ9InN2ZzgiICAgaW5rc2NhcGU6dmVyc2lvbj0iMC45Mi41ICgyMDYwZWMxZjlmLCAyMDIwLTA0LTA4KSIgICBzb2RpcG9kaTpkb2NuYW1lPSJDaGVycnljYWtlIGxvZ28uc3ZnIj4gIDx0aXRsZSAgICAgaWQ9InRpdGxlNDc2NSI+Q2hlcnJ5Y2FrZSBsb2dvPC90aXRsZT4gIDxkZWZzICAgICBpZD0iZGVmczIiPiAgICA8bGluZWFyR3JhZGllbnQgICAgICAgaW5rc2NhcGU6Y29sbGVjdD0iYWx3YXlzIiAgICAgICBpZD0ibGluZWFyR3JhZGllbnQ4NDIiPiAgICAgIDxzdG9wICAgICAgICAgc3R5bGU9InN0b3AtY29sb3I6IzU4MGYxZDtzdG9wLW9wYWNpdHk6MC4zODI0NTYxNSIgICAgICAgICBvZmZzZXQ9IjAiICAgICAgICAgaWQ9InN0b3A4MzgiIC8+ICAgICAgPHN0b3AgICAgICAgICBzdHlsZT0ic3RvcC1jb2xvcjojZGMwMDJlO3N0b3Atb3BhY2l0eTowLjAzMTU3ODk1IiAgICAgICAgIG9mZnNldD0iMSIgICAgICAgICBpZD0ic3RvcDg0MCIgLz4gICAgPC9saW5lYXJHcmFkaWVudD4gICAgPGxpbmVhckdyYWRpZW50ICAgICAgIGlua3NjYXBlOmNvbGxlY3Q9ImFsd2F5cyIgICAgICAgaWQ9ImxpbmVhckdyYWRpZW50NDc1OSI+ICAgICAgPHN0b3AgICAgICAgICBzdHlsZT0ic3RvcC1jb2xvcjojZjEzNDU1O3N0b3Atb3BhY2l0eToxIiAgICAgICAgIG9mZnNldD0iMCIgICAgICAgICBpZD0ic3RvcDQ3NTUiIC8+ICAgICAgPHN0b3AgICAgICAgICBzdHlsZT0ic3RvcC1jb2xvcjojNTdjZWJmO3N0b3Atb3BhY2l0eTowOyIgICAgICAgICBvZmZzZXQ9IjEiICAgICAgICAgaWQ9InN0b3A0NzU3IiAvPiAgICA8L2xpbmVhckdyYWRpZW50PiAgICA8cmFkaWFsR3JhZGllbnQgICAgICAgaW5rc2NhcGU6Y29sbGVjdD0iYWx3YXlzIiAgICAgICB4bGluazpocmVmPSIjbGluZWFyR3JhZGllbnQ0NzU5IiAgICAgICBpZD0icmFkaWFsR3JhZGllbnQ0NzYxIiAgICAgICBjeD0iMTQ1LjMxNjc3IiAgICAgICBjeT0iMTQxLjMwNDg2IiAgICAgICBmeD0iMTQ1LjMxNjc3IiAgICAgICBmeT0iMTQxLjMwNDg2IiAgICAgICByPSIxNy4wNjgyNDUiICAgICAgIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoMS4xOTcyNTUzLDAsMCwwLjQ5Nzk4NDEyLC0yOC43NTYyNjUsMTE5LjY3NzQ5KSIgICAgICAgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIC8+ICAgIDxyYWRpYWxHcmFkaWVudCAgICAgICBpbmtzY2FwZTpjb2xsZWN0PSJhbHdheXMiICAgICAgIHhsaW5rOmhyZWY9IiNsaW5lYXJHcmFkaWVudDg0MiIgICAgICAgaWQ9InJhZGlhbEdyYWRpZW50ODQ0IiAgICAgICBjeD0iMTQ1LjQ1MDY4IiAgICAgICBjeT0iMTYzLjYxMjM1IiAgICAgICBmeD0iMTQ1LjQ1MDY4IiAgICAgICBmeT0iMTYzLjYxMjM1IiAgICAgICByPSIyLjcxMjc4NTUiICAgICAgIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoMS42MjQ3NjgxLDAsMCwwLjU0OTc1OTI5LC05MC44MDEzMSw3My45Njg2MTcpIiAgICAgICBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgICAgICAgc3ByZWFkTWV0aG9kPSJwYWQiIC8+ICA8L2RlZnM+ICA8c29kaXBvZGk6bmFtZWR2aWV3ICAgICBpZD0iYmFzZSIgICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIgICAgIGJvcmRlcmNvbG9yPSIjNjY2NjY2IiAgICAgYm9yZGVyb3BhY2l0eT0iMS4wIiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAuMCIgICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiICAgICBpbmtzY2FwZTp6b29tPSIxIiAgICAgaW5rc2NhcGU6Y3g9IjMzOS42NjciICAgICBpbmtzY2FwZTpjeT0iLTEwNi41ODM5NiIgICAgIGlua3NjYXBlOmRvY3VtZW50LXVuaXRzPSJtbSIgICAgIGlua3NjYXBlOmN1cnJlbnQtbGF5ZXI9Imc0NTQ3IiAgICAgc2hvd2dyaWQ9ImZhbHNlIiAgICAgaW5rc2NhcGU6d2luZG93LXdpZHRoPSIyMTM0IiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTM3NiIgICAgIGlua3NjYXBlOndpbmRvdy14PSI3NDAiICAgICBpbmtzY2FwZTp3aW5kb3cteT0iMjciICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIwIiAgICAgc2hvd2d1aWRlcz0iZmFsc2UiICAgICBpbmtzY2FwZTpzbmFwLWJib3g9InRydWUiICAgICBpbmtzY2FwZTpiYm94LXBhdGhzPSJ0cnVlIiAgICAgaW5rc2NhcGU6YmJveC1ub2Rlcz0idHJ1ZSIgICAgIGlua3NjYXBlOnNuYXAtYmJveC1lZGdlLW1pZHBvaW50cz0idHJ1ZSIgICAgIGlua3NjYXBlOnNuYXAtYmJveC1taWRwb2ludHM9InRydWUiICAgICBpbmtzY2FwZTpzbmFwLWdsb2JhbD0iZmFsc2UiICAgICBpbmtzY2FwZTpzbmFwLW9iamVjdC1taWRwb2ludHM9InRydWUiICAgICBpbmtzY2FwZTpzbmFwLWdyaWRzPSJmYWxzZSIgICAgIGlua3NjYXBlOnNuYXAtdG8tZ3VpZGVzPSJmYWxzZSIgICAgIGlua3NjYXBlOm9iamVjdC1wYXRocz0iZmFsc2UiICAgICBpbmtzY2FwZTpzbmFwLWludGVyc2VjdGlvbi1wYXRocz0iZmFsc2UiICAgICBpbmtzY2FwZTpzbmFwLXNtb290aC1ub2Rlcz0iZmFsc2UiICAgICBpbmtzY2FwZTpzbmFwLW1pZHBvaW50cz0iZmFsc2UiICAgICBpbmtzY2FwZTpzbmFwLWNlbnRlcj0idHJ1ZSIgICAgIGlua3NjYXBlOnNuYXAtcGFnZT0idHJ1ZSIgICAgIGlua3NjYXBlOm9iamVjdC1ub2Rlcz0iZmFsc2UiPiAgICA8aW5rc2NhcGU6Z3JpZCAgICAgICB0eXBlPSJ4eWdyaWQiICAgICAgIGlkPSJncmlkMzc0OSIgICAgICAgc3BhY2luZ3g9IjIuNjQ1ODMzMyIgICAgICAgc3BhY2luZ3k9IjIuNjQ1ODMzMyIgICAgICAgc25hcHZpc2libGVncmlkbGluZXNvbmx5PSJmYWxzZSIgICAgICAgZG90dGVkPSJmYWxzZSIgICAgICAgZW1wc3BhY2luZz0iNSIgLz4gIDwvc29kaXBvZGk6bmFtZWR2aWV3PiAgPG1ldGFkYXRhICAgICBpZD0ibWV0YWRhdGE1Ij4gICAgPHJkZjpSREY+ICAgICAgPGNjOldvcmsgICAgICAgICByZGY6YWJvdXQ9IiI+ICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4gICAgICAgIDxkYzp0eXBlICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIiAvPiAgICAgICAgPGRjOnRpdGxlPkNoZXJyeWNha2UgbG9nbzwvZGM6dGl0bGU+ICAgICAgICA8Y2M6bGljZW5zZSAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwczovL2NoZXJyeWNha2UuaW8iIC8+ICAgICAgICA8ZGM6Y3JlYXRvcj4gICAgICAgICAgPGNjOkFnZW50PiAgICAgICAgICAgIDxkYzp0aXRsZT5Mb3JlbnpvIEhlcnJlcmEgLyBsb3JlbnpvQHRpbi5jYXQgLyBodHRwczovL2NoZXJyeWNha2UuaW88L2RjOnRpdGxlPiAgICAgICAgICA8L2NjOkFnZW50PiAgICAgICAgPC9kYzpjcmVhdG9yPiAgICAgICAgPGRjOmRhdGU+MjAyMDwvZGM6ZGF0ZT4gICAgICAgIDxkYzpkZXNjcmlwdGlvbj5CYXNlZCBvbiB0aGUgaWNvbiBjcmVhdGVkIGJ5IFZlY3RvcnMgTWFya2V0IChodHRwczovL3d3dy5mbGF0aWNvbi5jb20vZnJlZS1pY29uL2Nha2VfNjA5NjMxKTwvZGM6ZGVzY3JpcHRpb24+ICAgICAgPC9jYzpXb3JrPiAgICA8L3JkZjpSREY+ICA8L21ldGFkYXRhPiAgPGcgICAgIGlua3NjYXBlOmxhYmVsPSJMYXllciAxIiAgICAgaW5rc2NhcGU6Z3JvdXBtb2RlPSJsYXllciIgICAgIGlkPSJsYXllcjEiICAgICB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLC0yMTcpIiAgICAgc3R5bGU9ImRpc3BsYXk6aW5saW5lIj4gICAgPGcgICAgICAgaWQ9Imc0NTQ3IiAgICAgICB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMTExLjUyOTE3LDcyLjQyNDk5OSkiPiAgICAgIDxwYXRoICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXlkcGk9IjYwMC4xMzY3OCIgICAgICAgICBpbmtzY2FwZTpleHBvcnQteGRwaT0iNjAwLjEzNjc4IiAgICAgICAgIHNvZGlwb2RpOm5vZGV0eXBlcz0iY2NzY2NjYyIgICAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAgICAgICAgIGlkPSJwYXRoMzczMi0zLTAiICAgICAgICAgZD0ibSAxMTkuMDYyNSwxOTguNTk5ODUgdiA2LjMyMDk3IGMgMWUtNSw3LjMwNjI2IDExLjg0NTgxLDEzLjIyOTE2IDI2LjQ1ODMzLDEzLjIyOTE2IDE0LjYxMjUyLDAgMjYuNDU4MzEsLTUuOTIyOSAyNi40NTgzMywtMTMuMjI5MTYgdiAtNi40MDg4NiBsIC0yNi40NTgzMywwLjI3Mjg1IHoiICAgICAgICAgc3R5bGU9ImRpc3BsYXk6aW5saW5lO2ZpbGw6IzQzYzhiNztmaWxsLW9wYWNpdHk6MTtzdHJva2Utd2lkdGg6MC4yNjAzMDIzNCIgLz4gICAgICA8cGF0aCAgICAgICAgIGlua3NjYXBlOmV4cG9ydC15ZHBpPSI2MDAuMTM2NzgiICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXhkcGk9IjYwMC4xMzY3OCIgICAgICAgICBzb2RpcG9kaTpub2RldHlwZXM9ImNjc2NjY2MiICAgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgICAgICAgICBpZD0icGF0aDM3MzItMy0wLTQiICAgICAgICAgZD0ibSAxMTkuMDYyNSwxOTIuNDg0NDQgdiA2LjA4NjI5IGMgMWUtNSw3LjMwNjI2IDExLjg0NTgxLDEzLjIyOTE2IDI2LjQ1ODMzLDEzLjIyOTE2IDE0LjYxMjUyLDAgMjYuNDU4MzIsLTUuOTIyOSAyNi40NTgzMywtMTMuMjI5MTYgdiAtNi4wNTMxNCBsIC0yNi40NTgzMywtMC4xNDkxNiB6IiAgICAgICAgIHN0eWxlPSJkaXNwbGF5OmlubGluZTtmaWxsOiNmZmZmZTk7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlLXdpZHRoOjAuMjYwMzAyMzQiIC8+ICAgICAgPHBhdGggICAgICAgICBpbmtzY2FwZTpleHBvcnQteWRwaT0iNjAwLjEzNjc4IiAgICAgICAgIGlua3NjYXBlOmV4cG9ydC14ZHBpPSI2MDAuMTM2NzgiICAgICAgICAgc29kaXBvZGk6bm9kZXR5cGVzPSJjY3NjY2NjIiAgICAgICAgIGlua3NjYXBlOmNvbm5lY3Rvci1jdXJ2YXR1cmU9IjAiICAgICAgICAgaWQ9InBhdGgzNzMyLTMtMC00NyIgICAgICAgICBkPSJNIDExOS4wNjI1LDE4NS40Mzc2MSBWIDE5Mi40IGMgMWUtNSw3LjMwNjI2IDExLjg0NTgxLDEzLjIyOTE2IDI2LjQ1ODMzLDEzLjIyOTE2IDE0LjYxMjUyLDAgMjYuNDU4MzIsLTUuOTIyOSAyNi40NTgzMywtMTMuMjI5MTYgdiAtNi45Njg2IGwgLTI2LjQ1ODMzLDAuMjY5NzYgeiIgICAgICAgICBzdHlsZT0iZGlzcGxheTppbmxpbmU7ZmlsbDojNDNjOGI3O2ZpbGwtb3BhY2l0eToxO3N0cm9rZS13aWR0aDowLjI2MDMwMjM0IiAvPiAgICAgIDxlbGxpcHNlICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXlkcGk9IjYwMC4xMzY3OCIgICAgICAgICBpbmtzY2FwZTpleHBvcnQteGRwaT0iNjAwLjEzNjc4IiAgICAgICAgIHN0eWxlPSJmaWxsOiM2OWQzYzU7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlLXdpZHRoOjAuMjYwMzAyMzQiICAgICAgICAgcnk9IjEzLjIyOTE3NCIgICAgICAgICByeD0iMjYuNDU4MzM2IiAgICAgICAgIGN5PSIxODUuNDcwNTciICAgICAgICAgY3g9IjE0NS41MjA4MyIgICAgICAgICBpZD0icGF0aDM3MzItMyIgLz4gICAgICA8ZWxsaXBzZSAgICAgICAgIGlua3NjYXBlOmV4cG9ydC15ZHBpPSI2MDAuMTM2NzgiICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXhkcGk9IjYwMC4xMzY3OCIgICAgICAgICByeT0iMTAuMzMzMjUyIiAgICAgICAgIHJ4PSIyMi4yNzEzNjgiICAgICAgICAgY3k9IjE4OC4xNzI5OSIgICAgICAgICBjeD0iMTQ1LjUyMDgzIiAgICAgICAgIGlkPSJwYXRoNDcxMiIgICAgICAgICBzdHlsZT0iZmlsbDp1cmwoI3JhZGlhbEdyYWRpZW50NDc2MSk7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOiM0NTVhNzM7c3Ryb2tlLXdpZHRoOjA7c3Ryb2tlLWxpbmVjYXA6cm91bmQ7c3Ryb2tlLW1pdGVybGltaXQ6NDtzdHJva2UtZGFzaGFycmF5Om5vbmU7c3Ryb2tlLW9wYWNpdHk6MSIgLz4gICAgICA8cGF0aCAgICAgICAgIGlua3NjYXBlOmV4cG9ydC15ZHBpPSI2MDAuMTM2NzgiICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXhkcGk9IjYwMC4xMzY3OCIgICAgICAgICBzb2RpcG9kaTpub2RldHlwZXM9InNzc3Nzc3MiICAgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgICAgICAgICBpZD0icGF0aDQ2NzYiICAgICAgICAgZD0ibSAxNjUuMjQ2MTcsMTc0LjQwODA3IGMgMCw5Ljg1OTMyIC04LjgzMTM0LDE3Ljg1MTg4IC0xOS43MjUzNCwxNy44NTE4OCAtMTAuODk0LDAgLTE5LjcyNTM0LC03Ljk5MjU2IC0xOS43MjUzNCwtMTcuODUxODggMCwtNi41MjI3MiAzLjg1MDM0LC0xNC4yODQ4NSAxMS43NDU0OSwtMTQuMjg0ODUgNC4xNjIxNCwwIDUuMzM0MDIsMS44MDA0NiA3Ljk3OTg1LDEuODAwNDYgMi42NDU4MywwIDMuNzcwNjEsLTEuODA3MzEgNy45NDM3MywtMS44MDczMSA3LjkzMTI3LDAgMTEuNzgxNjEsNy43NjQyIDExLjc4MTYxLDE0LjI5MTcgeiIgICAgICAgICBzdHlsZT0iZmlsbDojZWQ1OTY4O2ZpbGwtb3BhY2l0eToxO3N0cm9rZS13aWR0aDowLjI2NDU4MzMyIiAvPiAgICAgIDxlbGxpcHNlICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXlkcGk9IjYwMC4xMzY3OCIgICAgICAgICBpbmtzY2FwZTpleHBvcnQteGRwaT0iNjAwLjEzNjc4IiAgICAgICAgIHRyYW5zZm9ybT0icm90YXRlKC0yMC4zMzE3MDIpIiAgICAgICAgIHJ5PSI0LjI2NTYyNDUiICAgICAgICAgcng9IjIuNjcxODc0OCIgICAgICAgICBjeT0iMjEzLjY0MjMyIiAgICAgICAgIGN4PSI4Ny4yNTE5NjEiICAgICAgICAgaWQ9InBhdGg0NzA4IiAgICAgICAgIHN0eWxlPSJmaWxsOiNmZmY1ZjU7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOiM0NTVhNzM7c3Ryb2tlLXdpZHRoOjA7c3Ryb2tlLWxpbmVjYXA6cm91bmQ7c3Ryb2tlLW1pdGVybGltaXQ6NDtzdHJva2UtZGFzaGFycmF5Om5vbmU7c3Ryb2tlLW9wYWNpdHk6MSIgLz4gICAgICA8cGF0aCAgICAgICAgIGlua3NjYXBlOmV4cG9ydC15ZHBpPSI2MDAuMTM2NzgiICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXhkcGk9IjYwMC4xMzY3OCIgICAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAgICAgICAgIGlkPSJwYXRoNDY3Ni0xIiAgICAgICAgIGQ9Im0gMTI4LjQxNDM4LDE2NS4yNTQ5OSBjIC0xLjc1MTgzLDIuNjkwNDMgLTIuNjE4OTYsNi4wNjAzMiAtMi42MTg5Niw5LjE1MjkzIDAsOS44NTkzMiA4LjgzMTQxLDE3Ljg1MjE0IDE5LjcyNTQxLDE3Ljg1MjE0IDUuMzQ0ODMsMCAxMC4xODk4NSwtMS45MjcwNSAxMy43NDIzMSwtNS4wNTEzNyBhIDIzLjk5NzQzNiwyMy45OTc0MzYgMCAwIDEgLTYuODg3NDMsMS4wMzA0MiAyMy45OTc0MzYsMjMuOTk3NDM2IDAgMCAxIC0yMy45NjEzMywtMjIuOTg0MTIgeiIgICAgICAgICBzdHlsZT0iZGlzcGxheTppbmxpbmU7ZmlsbDojZTg0YTU4O2ZpbGwtb3BhY2l0eToxO3N0cm9rZS13aWR0aDowLjI2NDU4MzMyIiAvPiAgICAgIDxwYXRoICAgICAgICAgaW5rc2NhcGU6ZXhwb3J0LXlkcGk9IjYwMC4xMzY3OCIgICAgICAgICBpbmtzY2FwZTpleHBvcnQteGRwaT0iNjAwLjEzNjc4IiAgICAgICAgIHNvZGlwb2RpOm5vZGV0eXBlcz0iY2MiICAgICAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgICAgICAgICBpZD0icGF0aDQ2ODEiICAgICAgICAgZD0ibSAxNDUuNDc2MTcsMTYzLjY1ODU0IGMgMCwwIC0wLjIyNTk3LC03LjMzNDAyIDcuMTYzNTMsLTExLjE0MTIzIiAgICAgICAgIHN0eWxlPSJmaWxsOm5vbmU7c3Ryb2tlOiM0NTVhNzM7c3Ryb2tlLXdpZHRoOjEuMjAwMDAwMDU7c3Ryb2tlLWxpbmVjYXA6cm91bmQ7c3Ryb2tlLWxpbmVqb2luOm1pdGVyO3N0cm9rZS1taXRlcmxpbWl0OjQ7c3Ryb2tlLWRhc2hhcnJheTpub25lO3N0cm9rZS1vcGFjaXR5OjEiIC8+ICAgICAgPGVsbGlwc2UgICAgICAgICBzdHlsZT0iZmlsbDp1cmwoI3JhZGlhbEdyYWRpZW50ODQ0KTtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZTtzdHJva2Utd2lkdGg6MS43NjMwNzg1NztzdHJva2UtbGluZWNhcDpyb3VuZDtzdHJva2UtbWl0ZXJsaW1pdDo0O3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2Utb3BhY2l0eToxIiAgICAgICAgIGlkPSJwYXRoODM2IiAgICAgICAgIGN4PSIxNDUuNTc5MyIgICAgICAgICBjeT0iMTYzLjkwMDUiICAgICAgICAgcng9IjQuNDA3NjQ3NiIgICAgICAgICByeT0iMS40OTEzNzkiIC8+ICAgIDwvZz4gIDwvZz48L3N2Zz4=);
				background-size: contain;
			}
			.errorReport > table.error th.head > .headMosaic > .text {
				flex-grow: 1;
				margin: 17px 10px;
				font-family: 'Courier New';
			}
			.errorReport > table.error th.head > .headMosaic > .text > .title{
				font-weight: bold;
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
			.errorReport .engineStatus div {
				font-size: 7pt;
				max-height: 400px;
				overflow: auto;
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
				<div class='headMosaic'>
					<div class='logo'></div>
					<div class='text'>
						<div class='title'>
							<span>Cherrycake</span>
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
						</div>
						<div class='subTitle'>
							".nl2br($errStr)."	
						</div>
					</div>
				</div>
			</th></tr>
	";

	if ($errFile) {
		
		// Check specific error for pattern parsing in order to show later the pattern itself
		if (
			(
				strstr($errFile, "Patterns.class.php") !== false
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
					
					if (is_readable($patternFileName)) {
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
					else {
						$html .= "Could not open file \"".$patternFileName."\" for debugging.";
					}
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

	if (is_array($backtrace)) {
		$backtrace = array_reverse($backtrace);
		$html .=
		"
			<tr>
				<td class='key'>Engine status</td>
				<td class='engineStatus'>
					<div>".$e->getStatusHtml()."</div>
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
					"payload" => $e->isDevel() ? $html : "Sorry, we've got an unexpected error<br>"
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
							"Backtrace:<br>".implode("<br>", $backtrace_info),
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
						"code" => \Cherrycake\RESPONSE_INTERNAL_SERVER_ERROR,
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
						"code" => \Cherrycake\RESPONSE_INTERNAL_SERVER_ERROR,
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