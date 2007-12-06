#!/usr/bin/php -q
<?php

class ShellDispatcher {
	function ShellDispatcher($args = array()) {
		$this->__construct($args);
	}

	function __construct($args = array()) {
		$this->__initConstants();
		$this->parseParams($args);
		$this->__initEnvironment();
		$this->dispatch();
		die("\n");
	}

	function __initConstants() {
		if (function_exists('ini_set')) {
			ini_set('display_errors', '1');
			ini_set('error_reporting', E_ALL);
			ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 60 * 5);
		}
		define('PHP5', (phpversion() >= 5));
		define('DS', DIRECTORY_SEPARATOR);
	}

	function __initEnvironment() {
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		if (!isset($this->args[0]) || !isset($this->params['working'])) {
			$this->stdout("\nUnicorn Shell: ");
			$this->stdout("This file has been loaded incorrectly and cannot continue.\n");
			exit();
		}

		$this->__bootstrap();
		
		$this->shellPaths = array(
								APP . 'vendors' . DS . 'shells' . DS,
								VENDORS . 'shells' . DS,
								CONSOLE_LIBS
							);
	}

	function parseParams($params) {
		$out = array();
		for ($i = 0; $i < count($params); $i++) {
			if (strpos($params[$i], '-') === 0) {
				$this->params[substr($params[$i], 1)] = str_replace('"', '', $params[++$i]);
			} else {
				$this->args[] = $params[$i];
			}
		}
		
	}
	
	function stdout($string, $newline = true) {
		if ($newline) {
			fwrite($this->stdout, $string . "\n");
		} else {
			fwrite($this->stdout, $string);
		}
	}

	function stderr($string) {
		fwrite($this->stderr, 'Error: '. $string);
	}

}

if (!defined('DISABLE_AUTO_DISPATCH')) {
	$dispatcher = new ShellDispatcher($argv);
}
