<?php

abstract class Behaviour {
	
}

abstract class Datasource {
	function read($id) {
		
	}
	
	function write($data) {
		
	}
}

abstract class Model implements ArrayAccess {
	public 
		$_name,
		$_data = array();

	protected 
		$belongsTo = false,
		$hasOne = false,
		$hasMany = false,
		$hasAndBelongsToMany = false,
		$_datasource = null;

	function __construct($array = null) {
		if (null == $this->name) {
			$this->name = get_class($this);
		}
	}

	public function set($key = null, $value = null) {
		// TypeCast $key and $value, makes $data array
		if (is_string($key)) {
			$data = array($key => $value);
		} elseif(is_array($key)) {
			$data = $key;
		} elseif(null == $key) {
			return;
		} else {
			$data = array();
		}
		
		if (!isset($data[$this->name])) {
			$data = array($this->name => $data);
		}
		
		foreach($data as $model => $values) {
			if ($model == $this->name) {
				foreach($values as $key => $value) {
					$this->_data[$key] = $value;
				}
			} else {
				$this->$model->set($values);
			}
		}
	}
	
	function save($data = null) {
		$this->set($data);

		if ($this->_modified) {
			// Save some data
			$this->_modified = false;
			return true;
		} else {
			return;
		}
	}

	public function create($data = null) {
		$this->set($data);

		$this->_modified = false;
	}

	public function read($what = null, $params = null) {

	}

	public function update($data = array()) {
		
	}

	public function delete($id = null) {
		
	}

	function offsetExists($key) {
		return isset($this->_data[$key]);
	}
	
	function offsetGet($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return null;
		}
	}
	
	function offsetSet($key, $value) {
		if (isset($this->_data[$key])) {
			if ($this->_data[$key] != $value) {
				$this->_data[$key] = $value;
				$this->_modified = true;
			}
		} 
		return null;
	}
	
	function offsetUnset($key) {
		if (isset($this->_data[$key])) {
			unset($this->_data[$key]);
			$this->_modified = true;
		}
	}
}

class DbModel extends Model {
	private $_database = 'default';
	private $_schema = array();
	private $_associations = array(
		'belongsTo' => array(),
		'hasOne' => array(),
		'hasMany' => array(),
		'hasAndBelongsToMany' => array(),
	);

	function __construct($array = null) {
		if (null == $this->name) {
			$this->name = get_class($this);
		}
		
		if (empty($this->_schema)) {
			$this->_schema = $this->getDb()->getSchema($this->_name);
		}
		
		$this->bindModels(array(
			'belongsTo' => $this->belongsTo, 
			'hasOne' => $this->hasOne, 
			'hasMany' => $this->hasMany,
			'hasAndBelongsToMany' => $this->hasAndBelongsToMany,
		));
	}
	
	function bindModels($assoc) {
		foreach($assoc as $type => $params) {
			if ($params) {
				
				
				$this->_associations[$type] = $params;
			}
		}
	}
}