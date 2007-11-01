<?php

require_once UNICORN . 'vendors' . DS . 'spyc.php';

class Unicorn {
	
}

class Registry implements ArrayAccess {
	private $vars = array();

	function __construct() {
		
	}
	
	function getInstance() {
		static $instance = array();

		if (empty($instance)) {
			$instance[0] = new Registry();
		}

		return $instance[0];
	}
	
	function set($key, $var) {
		$_this = Registry::getInstance();

		if (isset($_this->vars[$key]) == true) {
			throw new Exception('Unable to set var `' . $key . '`. Already set.');
		}

		$_this->vars[$key] = $var;
		return true;
	}

	function get($key) {
		$_this = Registry::getInstance();

		if (isset($_this->vars[$key]) == false) {
			return null;
		}

		return $_this->vars[$key];
	}

	function remove($var) {
		$_this = Registry::getInstance();

		unset($_this->vars[$key]);
	}

	function offsetExists($offset) {
		$_this = Registry::getInstance();

		return isset($_this->vars[$offset]);
	}

	function offsetGet($key) {
		$_this = Registry::getInstance();

		$_this->get($key);
	}
	
	function offsetSet($key, $value) {
		$_this = Registry::getInstance();

		$_this->set($key, $value);
	}

	function offsetUnset($offset) {
		$_this = Registry::getInstance();

		$_this->remove($offset);
	}

}

class Configure {
	private $_data = array();
	
	function getInstance() {
		static $instance = array();

		if (empty($instance)) {
			$instance[0] = new Configure();
		}

		return $instance[0];
	}

	function get($key) {
		
	}

	function set($key, $value) {
		
	}

	function load($fileName = 'config', $dirName = 'config') {
		$_this = Configure::getInstance();

		$fileName = $dirName . DS . $fileName;

		$data = $_this->getParsed($fileName);

		die('<pre>' . print_r($data, true) . '</pre>');
		return $data;
	}

	function getParsed($name) {
		$_this = Configure::getInstance();

		$parsedName = APP . 'tmp' . DS . $name . '.php';

		if (!file_exists($parsedName)) {
			$_this->parse($name);
		} else {
			include $parsedName;
			$_this->_data[$name] = $parsed;
		}

		return $_this->_data[$name];
	}

	function parse($name) {
		$_this = Configure::getInstance();

		$unParsedName = APP . $name . '.yml';
		if (file_exists($unParsedName)) {
			$parsed = Spyc::YAMLLoad($unParsedName);
		} else {
			$parsed = null;
		}


		$_this->_data[$name] = $parsed;

		$_this->save($name, $parsed);
	}

	function save($name, $data) {
		$_this = Configure::getInstance();
		$parsedName = APP . 'tmp' . DS . $name . '.php';
		$content = "<?php\n\n";
		
		$content .= "\$parsed = ";
		$content .= arrayToPhp($data);
		$content .= ";";
		
		$f = fopen($parsedName, 'w');
		fwrite($f, $content);
		fclose($f);
	}

}


function __autoload($className) {
	
}

function arrayToPhp($array) {
	$o = 'array(';
	foreach($array as $key => $value) {
		if (is_array($value)) {
			$value = arrayToPhp($value);
		} else {
			$value = "'" . addslashes($value) . "'";
		}
		$o .= "'$key'=>" . $value . ",";
	}
	$o .= ')';

	return $o;
}