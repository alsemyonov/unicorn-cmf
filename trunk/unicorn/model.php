<?php

class Behaviour {
	
}

class Datasource {
	
}

abstract class Model implements ArrayAccess {
	private $belongsTo = false;
	private $hasOne = false;
	private $hasMany = false;
	private $hasAndBelongsToMany = false;

	public $_name;
	public $_database = 'default';
	public $_data = array();
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

	public function set($key = null, $value = null) {
		// TypeCast $key and $value, makes $data array
		if (is_string($key)) {
			$data = array($key => $value);
		} elseif(is_array($key)) {
			$data = $key;
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

	public function create($data = null) {
		$this->set($data);

	}

	public function read($what = null, $params = null) {

	}

	public function update($data = array()) {
		
	}

	public function delete($id = null) {
		
	}
}
