<?php

/**
 * Implementation of the ActiveRecord pattern. Uses the PDO database drivers
 * which come with PHP 5.1 and can be installed with PHP 5.0+
 * 
 * This implementation of ActiveRecord determines object relationships on the
 * fly and caches the database metadata for optimization. Using the location
 * of foreign keys and the plurality of the lookup (e.g. $person->addresses or
 * $person->address) ActiveRecord can know how the two objects relate.
 * 
 * In addition, this implementation of ActiveRecord keeps all non-model properties
 * out of the class (such as $table, $class, etc) in order to keep the object
 * clean for passing to other applications such as webservices or Flash remoting.
 * 
 * The default database schema may be modified for different
 * database layouts. Variables which may be included in the strings are:
 * %table% %plural_table% %singular_table%
 * 
 * Example of use:
 * 
 * require("ActiveRecord.php");
 * ActiveRecord::$db = new PDO("mysql:host=localhost;dbname=mystickies", "root", "");
 * 
 * class Tag extends ActiveRecord {}
 * 
 * class Note extends ActiveRecord {}
 * 
 * $n = Note::findFirst(array("user_id = ?", $_GET['user_id']));
 * foreach ($note->tags as $tag) {
 *     echo $tag->name;
 * }
 * 
 */
abstract class ActiveRecord {
	
	// database layout rules
	public static $tableTransform = "plural";      // plural or singular
	public static $tableFormat = "under_score";      // camelBack, CamelBack (capitalized first letter), or under_score
	public static $pk = "id";                      // other examples: %table%_id, %singular_table%ID
	public static $fk = "%singular_table%_id";      // examples: %table%_id
	public static $joinTable = "%table%_%table%";  // examples: %singular_table%%singular_table%
	public static $modifiedField = "updated";      // the standard date field which is set each time the object is saved
	public static $creationField = "created";      // the standard date field which is set when object is first saved
	
	/**
	 * PDO Database connection
	 * @var PDO
	 */
	public static $db; // give ActiveRecord a database connection to work with
	
	
	public $id;
	
	/*****************************************************/
	/* METHODS TO BE OVERRIDDEN                          */
	/*****************************************************/
	
	public function preload() {
		
	}
	
	public function postload() {
		
	}
	
	public function presave() {
		
	}
	
	public function postsave() {
		
	}
	
	
	
	// stores the fields for each table (part of the cache
	protected static $tables;
	protected static $tablesLoaded = false;
	protected static $typeconv = array(
		"date|time" => "date",
		"int|double|decimal|long|short" => "numeric",
		"string|blob",
		"string"
	);
	protected static $prepStmts = array();
	
	/**
	 * Constructor of object
	 *
	 * @param int $id [Optional] If id is present, will load object
	 */
	public function __construct($id = null) {
		if (is_numeric($id)) {
			$this->load($id);
		} elseif (is_array($id)) {
			$this->setProperties($id);
		} else {
			$this->setProperties();
		}
	}
	
	/**
	 * Loads the object from the database by the id passed
	 *
	 * @param int $id
	 * @return boolean Whether the object was successfully loaded
	 */
	public function load($id) {
		$tableName = self::getTableName(get_class($this));
		$pk = self::getPKName($tableName);
		$stmt = self::getStatement("SELECT * FROM $tableName WHERE $pk = ?");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$found = $stmt->execute(array($id));
		if ($found) {
			$row = $stmt->fetch();
			$stmt->closeCursor();
			$this->setProperties($row);
		}
		
		return $found;
	}
	
	/**
	 * Loads the object from the database by the conditions passed
	 *
	 * @param string $conditions Conditions to be passed
	 * @param [mixed $properties...]
	 * @return boolean Whether the object was succesfully loaded
	 */
	public function loadBy($conditions) {
		$this->preload();
		
		$conditionsValues = func_get_args();
		array_shift($conditionsValues); // remove condition string from array
		
		$tableName = self::getTableName(get_class($this));
		$stmt = self::getStatement("SELECT * FROM $tableName WHERE $conditions");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$found = $stmt->execute($conditionsValues);
		if ($found) {
			$row = $stmt->fetch();
			$stmt->closeCursor();
			$this->setProperties($row);
		}
	
		$this->postload();
		
		return $found;
	}
	
	/**
	 * Saves the object to the database.
	 * 
	 * @return boolean Whather the object successfully saved
	 */
	public function save() {
		
		$this->presave();
		
		$table = self::getTableName(get_class($this));
		$pk = self::getPKName($table);
		
		// set the automatic timestamps
		if (!isset($this->id) && $this->hasProperty(self::$creationField))
			$this->{self::$creationField} = time();
		
		if ($this->hasProperty(self::$modifiedField))
			$this->{self::$modifiedField} = time();
		
		$props = $this->getProperties();
		$prop_values = array_values($props);
		$prop_keys = array_keys($props);
		
		if (isset($this->id)) {
			$stmt = self::getStatement("UPDATE $table SET " .
				implode(' = ?, ', $prop_keys) .
				" = ? WHERE $pk = ?"
			);
			$prop_values[] = $this->id;
		} else {
			// TODO generate a new primary key here for databases without autoincrement
			$stmt = self::getStatement("INSERT INTO $table (" .
					implode(', ', $prop_keys) .
				") VALUES (?" .
					str_repeat(", ?", count($prop_keys) - 1) .
				")"
			);
		}
		if (!$stmt->execute($prop_values)) {
			$error = $stmt->errorInfo();
			throw new Exception($error[2]);
		}
		
		if (!isset($this->id)) {
			$this->load(self::$db->lastInsertId());
		}
		
		$this->postsave();
		
		return $success;
	}
	
	public function hasProperty($propName) {
		return array_key_exists($propName, get_object_vars($this));
	}
	
	public function getProperties($join = "") {
		$table = self::getTableName(get_class($this));
		$pk = self::getPKName($table);
		$fields = self::getFields($table);
		$properties = array();
		if ($join) {
			$fields = self::getFields($join);
		}
		foreach ($fields as $field => $info) {
			if ($field == $pk) {
				continue;
			}
			$value = $this->$field;
			
			if ($info->type == "date" && is_int($value)) {
				$value = date("Y-m-d H:i:s", $value);
			} elseif ($info->type == "boolean" && is_bool($value)) {
				$value = $value ? "1" : "0";
			} elseif (is_null($value) && $info->notNull) {
				continue;
			}
			$properties[$field] = $value;
		}
		return $properties;
	}
	
	public function setProperties($properties = array()) {
		$this->preload();
		$table = self::getTableName(get_class($this));
		$pk = self::getPKName($table);
		$fields = self::getFields($table);
		if (!$fields) {
			return false;
		}
		foreach ($fields as $field => $info) {
			if ($field == $pk) {
				if (isset($properties[$pk]))
					$this->id = $properties[$pk];
				elseif (isset($properties["id"]))
					$this->id = $properties["id"];
				continue;
			}
			if (!isset($properties[$field])) {
				$this->$field = null;
				continue;
			}
			
			$value = $properties[$field];
			if ($info->type == "date") {
				$value = preg_match('/^[-0: ]+$/', $value) ? null : strtotime($value);
			} elseif ($info->type == "boolean" && is_numeric($value)) {
				$value = $value ? true : false;
			}
			$this->$field = $value;
		}
		$this->postload();
	}
	
	/*****************************************************/
	/* STATIC METHODS                                    */
	/*****************************************************/
	
	
	/**
	 * Return object found based on id
	 *
	 * @param string $class The class of the object to load
	 * @param int $id The id of the object in the database
	 * @return Model Subclass object of type class
	 */
	public static function find($id) {
		$class = self::getCaller();
		$pk = self::getPKName(self::getTableName($class));
		$result = self::findAll("$pk = $id", null, 1);
		return $result[0];
	}
	
	/**
	 * Returns first object found based on parameters
	 *
	 * @param string $class The class of the object to load
	 * @param string $conditions
	 * @param string $order
	 * @param string $joins
	 * @return Model
	 */
	public static function findFirst($conditions = null, $order = null, $joins = null) {
		$result = self::findAll($conditions, $order, 1, $joins);
		return $result[0];
	}
	
	/**
	 * Returns array of objects based on parameters
	 *
	 * @param string $class The class of the objects to load
	 * @param string $conditions
	 * @param string $order
	 * @param string $limit
	 * @param string $joins
	 * @return array
	 */
	public static function findAll($conditions = "", $order = "", $limit = "", $joins = "") {
		$class = self::getCaller();
		$table = self::getTableName($class);
		$pk = self::getPKName($table);
		
		// get the fields cached in advance
		self::getFields($table);
		
		//sql (make sure we have the original table's pk last so if there are joins, they don't interfere
		$sql = "SELECT *, $table.$pk FROM $table";
		$stmt = self::executeSQL($sql, $conditions, $limit, $order, $joins);
		
		$i = 0;
		$num = 0;
		$result = array();
		if ($limit) {
			$limit = explode(',', $limit);
			$total = $limit[0];
			$i = isset($limit[1]) ? $limit[1] : 0;
		}
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $i++)) {
			$result[] = new $class($row);
			if (++$num == $total)
				break;
		}
		$stmt->closeCursor();
		return $result;
	}
	
	/**
	 * Returns array of objects based on the full sql statement
	 *
	 * @param string $class The class of the objects to load
	 * @param string $sql
	 * @param array $params
	 * @param string $limit
	 * @return array
	 */
	public static function findBySql($sql, $params = null, $limit = "") {
		$class = self::getCaller();
		$stmt = self::getStatement($sql, array(PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL));
		if (!$stmt->execute($params)) {
			$error = $stmt->errorInfo();
			throw new Exception($error[2]);
		}
		
		$i = 0;
		$num = 0;
		$total = 0;
		$result = array();
		if ($limit) {
			$limit = explode(',', $limit);
			$i = $limit[0];
			$total = isset($limit[1]) ? $limit[1] : 0;
		}
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $i++)) {
			$result[] = new $class($row);
			if (++$num == $total) break;
		}
		$stmt->closeCursor();
		return $result;
	}
	
	/**
	 * Returns whether or not the object exists in the database
	 *
	 * @param string $class The class of the objects to load
	 * @param int $id
	 * @return boolean
	 */
	public static function exists($id) {
		$class = self::getCaller();
		$table = self::getTableName($class);
		$pk = self::getPKName($table);
		return (self::count("$pk = $id") > 0);
	}
	
	/**
	 * Creates new object, populates the attributes from the array, 
	 * saves it if it validates, and returns it
	 *
	 * @param string $class The class of the object to create
	 * @param array $properties
	 * @return Model
	 */
	public static function create($properties = null) {
		$class = self::getCaller();
		$obj = new $class($properties);
		$obj->save();
		return $obj;
	}
	
	/**
	 * Updates an object already stored in the database with the properties passed
	 *
	 * @param string $class The class of the object to update
	 * @param int $id The id of the class in the database
	 * @param string/array $properties
	 * @return boolean Whether it was successfully updated
	 */
	public static function update($id, $properties) {
		$class = self::getCaller();
		$table = self::getTableName($class);
		$pk = self::getPKName($table);
		// the properties element should be the same format as conditions
		$properties = self::prepareConditions($properties);
		$stmt = self::getStatement("UPDATE $table SET " . array_shift($properties) . " WHERE $pk = ?");
		array_push($properties, $id);
		return $stmt->execute($properties);
	}
	
	/**
	 * Updates all records with properties by conditions
	 *
	 * @param string $class The class of the objects to update
	 * @param string $conditions
	 * @param array $properties 
	 * @return int Number of successful updates
	 */
	public static function updateAll($conditions = null, $properties = null) {
		$class = self::getCaller();
		$table = self::getTableName($class);
		
		$properties = self::prepareConditions($properties);
		$conditions = self::prepareConditions($conditions);
		$sql = "UPDATE $table SET " . array_shift($properties);
		$stmt = self::executeSQL($sql, array_merge(array_splice($conditions, 0, 1), $properties, $conditions));
		return $stmt->rowCount();
	}
	
	/**
	 * Delete object by id
	 *
	 * @param string $class The class of the object to delete
	 * @param int $id The id of the object in the database
	 * @return boolean Whether object was deleted
	 */
	public static function delete($id) {
		$class = self::getCaller();
		$table = self::getTableName($class);
		$pk = self::getPKName($table);
		$stmt = self::getStatement("DELETE FROM $table WHERE $pk = ?");
		return $stmt->execute($id);
	}
	
	/**
	 * Deletes all records by conditions
	 *
	 * @param string $class The class of the objects to delete
	 * @param string $conditions
	 * @param string $limit
	 * @param string $deleteFrom Tables to delete records from
	 * @param string $joins Table joins needing to be added
	 * @return int Number of successful deletes
	 */
	public static function deleteAll($conditions = null, $deleteFrom = null, $joins = null) {
		$class = self::getCaller();
		$table = self::getTableName($class);
		
		if (!$deleteFrom) {
			$deleteFrom = $table;
		}
		//sql
		$sql = "DELETE $deleteFrom FROM $table";
		$stmt = self::executeSQL($sql, $conditions, null, null, $joins);
		return $stmt->rowCount();
	}
	
	/**
	 * Returns the number of records that meet the conditions
	 *
	 * @param string $class
	 * @param string $conditions
	 * @param string $joins
	 * @return int
	 */
	public static function count($conditions = null, $joins = null) {
		$class = self::getCaller();
		$table = self::getTableName($class);
		$sql = "SELECT COUNT(*) FROM $table";
		$stmt = self::executeSQL($sql, $conditions, null, null, $joins);
		// return first row, first field
		$stmt->setFetchMode(PDO::FETCH_COLUMN, 0);
		$count = $stmt->fetch();
		$stmt->closeCursor();
		return $count;
	}
	
	/**
	 * Returns the number of records returned by the sql statement
	 *
	 * @param string $class
	 * @param string $sql
	 * @param array $params
	 * @return int
	 */
	public static function countBySql($sql, $params = null) {
		$class = self::getCaller();
		$stmt = self::getStatement($sql);
		if (!$stmt->execute($params)) {
			$error = $stmt->errorInfo();
			throw new Exception($error[2]);
		}
		// return first row, first field
		$stmt->setFetchMode(PDO::FETCH_COLUMN, 0);
		$count = $stmt->fetch();
		$stmt->closeCursor();
		return $count;
	}
	
	/**
	 * Increment a property in a Model class
	 *
	 * @param string $class
	 * @param int $id
	 * @param string $counter The property of the class to be incremented
	 */
	public static function incrementCounter($id = null, $counter = null) {
		self::update($id, array("$counter = $counter + 1"));
	}
	
	/**
	 * Decrements a counter in a record
	 *
	 * @param string $class
	 * @param int $id
	 * @param string $counter The property of the class to be decremented
	 */
	public static function decrementCounter($id = null, $counter = null) {
		self::update($id, array("$counter = $counter - 1"));
	}
	
	
	
	//TODO EVERYTHING BELOW THIS
	
	
	
	// MAGIC METHODS FOR MODEL ******************************************
	
	/**
	 * Catches all methods called and forwards on certain types to their
	 * defined method. e.g. $this->doSomething(10) will call
	 * $this->_do("Something", 10) if the _do method is defined.
	 *
	 * @param string $func The name of the method
	 * @param array $args The arguments passed to it
	 * @return mixed The return value of the resolved method
	 */
	protected function __call($func, $args) {
		preg_match('/(^[^A-Z]*)([A-Z].*)/', $func, $matches);
		$catchFunc = '_' . $matches[1];
		// push the property name to the front of the args array
		array_unshift($args, lcfirst($matches[2]));
		
		if (method_exists($this, $catchFunc)) {
			return call_user_func_array(array($this, $catchFunc), $args);
		} else {
			throw new Exception("Method " . get_class($this) . "::$func() does not exist.");
		}
	}
	
	/**
	 * Catches all set actions to undefined properties and assumes they are related
	 * objects. Tries to save related object or array of objects.
	 *
	 * @param string $property Name of unset property we are setting.
	 * @return mixed The object or array of objects we are trying to set.
	 */
	protected function __set($property, $value) {
		$this->_set($property, $value);
	}
	
	/**
	 * Catches all get requests to undefined properties and assumes they are related
	 * objects. Tries to find and load related object or array of objects.
	 *
	 * @param string $property Name of unset property we are getting.
	 * @return mixed The object or array of objects we are trying to get (or null if not found).
	 */
	protected function __get($property) {
		return $this->_get($property);
	}
	
	
	/**
	 * Does automatic date conversion (to int) for database date properties and
	 * save relational objects on the fly. Calls $this->loadProperty which
	 * may be provided by subclass if custom loading is required
	 *
	 * @param string $prop
	 * @param mixed $value
	 * @param unknown_type $force
	 */
	protected function _set($prop, $value) {
		$table = self::getTableName(get_class($this));
		$fields = self::getFields($table);
		if (isset($fields[$prop]) && $fields[$prop]->type == "date" && is_string($value)) {
			$value = strtotime($value);
		}
		
		$this->$prop = $value;
		/*
		// $this->$prop could be set if _set is reached through $this->setProperty($value);
		if ((is_object($value) || is_array($value)) && !isset($this->$prop)) {
			$this->{"save" . ucfirst($prop)}();
		}*/
	}
	
	/**
	 * Loads relational objects on the fly. Calls $this->loadProperty which
	 * may be provided by subclass if custom loading is required
	 * 
	 * @param string $property Property to get
	 * @return mixed The value returned from this object
	 */
	protected function _get($property) {
		$table = self::getTableName(get_class($this));
		if (!isset($this->$prop) && self::getFields($table)) {
			$this->{"load" . ucfirst($property)}();
		}
		return $this->$property;
	}
	
	/**
	 * Adds an object to an array and saves relational objects on the fly
	 *
	 * @param string $property
	 * @param mixed $value
	 */
	protected function _add($property, $value) {
		$property = Inflector::pluralize($property);
		if (!isset($this->$property)) {
			$this->{"load" . ucfirst($property)}();
		}
		array_push($this->$property, $value);
		$this->{'save' . ucfirst($property)}();
	}
	
	/**
	 * Removes an object from an array and removes the relationship of relational objects
	 *
	 * @param string $property
	 * @param obj/int $objOrNum
	 * @return mixed Returns the object removed from the array
	 */
	protected function _remove($property, $objOrNum) {
		$property = Inflector::pluralize($property);
		if (!isset($this->$property)) {
			$this->{"load" . ucfirst($property)}();
		}
		// TODO save object after removing it (save this or that object, depending on where Fkey is)
		if (is_numeric($objOrNum)) {
			$removed = array_splice($this->$property, $objOrNum, 1);
			return $removed[0];
		}

		foreach ($this->$property as $index => $tempObj) {
			if ($tempObj === $objOrNum) {
				$removed = array_splice($this->$property, $index, 1);
				return $removed[0];
			}
		}
		return false;
	}
	
	/**
	 * Gives the size of an array property, loading relational objects on the fly
	 *
	 * @param string $property
	 * @return int
	 */
	protected function _sizeof($property) {
		if ($this->relations[$property] && !isset($this->$property))
			$this->{'load' . ucfirst($property)}();
		if (!is_array($this->$property))
			$this->$property = array();
		return sizeof($this->$property);
	}
	
	
	// Database automatic methods
	
	protected function _load($property, $conditions = null, $order = null, $limit = null) {
		if (isset($this->$property)) {
			return $this->$property;
		}
		
		if (!isset($this->id)) {
			return false;
		}
		
		// get all needed info between this class and that class //
		
		$thisClass = get_class($this);
		$thatClass = ucfirst($property);
		$plural = false;
		if (!class_exists($thatClass)) {
			$thatClass = Inflector::singularize($thatClass);
			if (!class_exists($thatClass)) {
				throw new Exception("Class not found to load the $thisClass::$property property.");
			}
			$plural = true; // This property was plural, so it is an array relationship
		}
		$thisTable = self::getTableName($thisClass);
		$thatTable = self::getTableName($thatClass);
		$thisPK = self::getPKName($thisTable);
		$thatPK = self::getPKName($thatTable);
		$thisFK = self::getFKName($thisTable);
		$thatFK = self::getFKName($thatTable);
		
		$thisFields = self::getFields($thisTable);
		$thatFields = self::getFields($thatTable);
		if (!$thisFields || !$thatFields) {
			return;
		}
		
		
		// determine the relationship //
		if (!$plural) {
			if (isset($thisFields[$thatFK])) { // Belongs-To
				$this->$property = self::findFirst($thatClass, array("$thatPK = ?", $this->$thatFK));
			} elseif (isset($thatFields[$thisFK])) { // Has-One
				$conditions = self::prepareConditions($conditions, array("$thisFK = ?", $this->$thisPK));
				$this->$property = self::findFirst($thatClass, $conditions, $order);
			}
		} else {
			if (isset($thatFields[$thisFK])) { // Has-Many
				$conditions = self::prepareConditions($conditions, array("$thisFK = ?", $this->$thisPK));
				$this->$property = self::findAll($thatClass, $conditions, $order, $limit);
			} else { // Many-To-Many/Has-And-Belongs-To
				$joinTable = self::getJoinTableName($thisTable, $thatTable);
				$joinFields = self::getFields($joinTable, $thisFK);
				if (!$joinFields) {
					$joinTable = self::getJoinTableName($thatTable, $thisTable);
					$joinFields = self::getFields($joinTable, $thisFK);
					if (!$joinFields) {
						return null;
					}
				}
				$conditions = self::prepareConditions($conditions, array("$joinTable.$thisFK = ?", $this->id));
				$joins = " INNER JOIN $joinTable ON $thatTable.$thatPK = $joinTable.$thatFK";
				$this->$property = self::findAll($thatClass, $conditions, $order, $limit, $joins);
			}
		}
		
		return $this->$property;
	}
	
	protected function _count($property, $conditions = null) {
		if (!isset($this->id)) {
			return false;
		}
		
		// get all needed info between this class and that class //
		
		$thisClass = get_class($this);
		$thatClass = ucfirst($property);
		$plural = false;
		if (!class_exists($thatClass)) {
			$thatClass = Inflector::singularize($thatClass);
			if (!class_exists($thatClass)) {
				throw new Exception("Class not found to load the $thisClass::$property property.");
			}
			$plural = true; // This property was plural, so it is an array relationship
		}
		$thisTable = self::getTableName($thisClass);
		$thatTable = self::getTableName($thatTable);
		$thisPK = self::getPKName($thisTable);
		$thatPK = self::getPKName($thatTable);
		$thisFK = self::getFKName($thisTable);
		$thatFK = self::getFKName($thatTable);
		
		$thisFields = self::getFields($thisTable);
		$thatFields = self::getFields($thatTable);
		if (!$thisFields || !$thatFields) {
			return;
		}
		
		
		// determine the relationship //
		if (!$plural) {
			if (isset($thisFields[$thatFK])) { // Belongs-To
				return self::count($thatClass, array("$thatPK = ?", $this->$thatFK));
			} elseif (isset($thatFields[$thisFK])) { // Has-One
				$conditions = self::prepareConditions($conditions, array("$thisFK = ?", $this->$thisPK));
				return self::count($thatClass, $conditions);
			}
		} else {
			if (isset($thatFields[$thisFK])) { // Has-Many
				$conditions = self::prepareConditions($conditions, array("$thisFK = ?", $this->$thisPK));
				return self::count($thatClass, $conditions);
			} else { // Many-To-Many/Has-And-Belongs-To
				$joinTable = self::getJoinTableName($thisTable, $thatTable);
				$joinFields = self::getFields($joinTable);
				if (!$joinFields) {
					$joinTable = self::getJoinTableName($thatTable, $thisTable);
					$joinFields = self::getFields($joinTable);
					if (!$joinFields) {
						return null;
					}
				}
				$conditions = self::prepareConditions($conditions, array("$joinTable.$thisFK = ?", $this->id));
				$joins = " INNER JOIN $joinTable ON $thatTable.$thatPK = $joinTable.$thatFK";
				return self::count($thatClass, $conditions, $joins);
			}
		}
	}
	
	protected function _save($property) {
		if (!isset($this->$property)) {
			return false;
		}
		
		if (!isset($this->id)) {
			return false;
		}
		
		// get all needed info between this class and that class //
		
		$thisClass = get_class($this);
		$thatClass = ucfirst($property);
		$plural = false;
		if (!class_exists($thatClass)) {
			$thatClass = Inflector::singularize($thatClass);
			if (!class_exists($thatClass)) {
				throw new Exception("Class not found to load the $thisClass::$property property.");
			}
			$plural = true; // This property was plural, so it is an array relationship
		}
		$thisTable = self::getTableName($thisClass);
		$thatTable = self::getTableName($thatTable);
		$thisPK = self::getPKName($thisTable);
		$thatPK = self::getPKName($thatTable);
		$thisFK = self::getFKName($thisTable);
		$thatFK = self::getFKName($thatTable);
		
		$thisFields = self::getFields($thisTable);
		$thatFields = self::getFields($thatTable);
		if (!$thisFields || !$thatFields) {
			return false;
		}
		
		
		// determine the relationship //
		if (!$plural) {
			if (!is_object($this->$property)) {
				throw new Exception("$thisClass::$property is not an object, cannot save it.");
			}
			if (isset($thisFields[$thatFK])) { // Belongs-To
				$this->$property->save();
				$this->$thatFK = $this->$property->id;
				$this->save();
			} elseif (isset($thatFields[$thisFK])) { // Has-One
				$this->$property->$thisFK = $this->id;
				$this->$property->save();
			}
		} else {
			if (!is_array($this->$property)) {
				throw new Exception("$thisClass::$property is not an array, cannot save it.");
			}
			if (isset($thatFields[$thisFK])) { // Has-Many
				foreach ($this->$property as $obj) {
					$obj->$thisFK = $this->id;
					$obj->save();
				}
			} else { // Many-To-Many/Has-And-Belongs-To
				$joinTable = self::getJoinTableName($thisTable, $thatTable);
				$joinFields = self::getFields($joinTable);
				if (!$joinFields) {
					$joinTable = self::getJoinTableName($thatTable, $thisTable);
					$joinFields = self::getFields($joinTable);
					if (!$joinFields) {
						return false;
					}
				}
				
				$props = $this->getProperties($joinTable);
				$delStmt = self::getStatement("DELETE FROM $joinTable WHERE $thisFK = ? AND $thatFK = ?");
				$insStmt = self::getStatement("INSERT INTO $joinTable (" .
								implode(', ', $props) .
							") VALUES (?" .
								str_repeat(", ?", count($props) - 1) .
							")");
				foreach ($this->$property as $obj) {
					$obj->save();
					if (!isset($obj->id)) {
						return false;
					}
					$props[$thatFK] = $obj->id;
					$delStmt->execute(array($this->id, $obj->id));
					$insStmt->execute(array_values($props));
				}
			}
		}
		return true;
	}
	
	protected function _delete($property, $conditions = null, $force = false) {
		if (!isset($this->$property)) {
			return false;
		}
		
		if (!isset($this->id)) {
			return false;
		}
		
		// get all needed info between this class and that class //
		
		$thisClass = get_class($this);
		$thatClass = ucfirst($property);
		$plural = false;
		if (!class_exists($thatClass)) {
			$thatClass = Inflector::singularize($thatClass);
			if (!class_exists($class)) {
				throw new Exception("Class not found to load the $thisClass::$property property.");
			}
			$plural = true; // This property was plural, so it is an array relationship
		}
		$thisTable = self::getTableName($thisClass);
		$thatTable = self::getTableName($thatTable);
		$thisPK = self::getPKName($thisTable);
		$thatPK = self::getPKName($thatTable);
		$thisFK = self::getFKName($thisTable);
		$thatFK = self::getFKName($thatTable);
		
		$thisFields = self::getFields($thisTable);
		$thatFields = self::getFields($thatTable);
		if (!$thisFields || !$thatFields) {
			return false;
		}
		
		
		// determine the relationship //
		if (!$plural) {
			if (isset($thisFields[$thatFK])) { // Belongs-To
				return self::deleteAll($thatClass, array("$thatPK = ?", $this->$thatFK));
			} elseif (isset($thatFields[$thisFK])) { // Has-One
				$conditions = self::prepareConditions($conditions, array("$thisFK = ?", $this->$thisPK));
				return self::deleteAll($thatClass, $conditions);
			}
		} else {
			if (isset($thatFields[$thisFK])) { // Has-Many
				$conditions = self::prepareConditions($conditions, array("$thisFK = ?", $this->$thisPK));
				return self::deleteAll($thatClass, $conditions);
			} else { // Many-To-Many/Has-And-Belongs-To
				$joinTable = self::getJoinTableName($thisTable, $thatTable);
				$joinFields = self::getFields($joinTable);
				if (!$joinFields) {
					$joinTable = self::getJoinTableName($thatTable, $thisTable);
					$joinFields = self::getFields($joinTable);
					if (!$joinFields) {
						return false;
					}
				}
				
				$conditions = self::prepareConditions($conditions, array("$joinTable.$thisFK = ?", $this->id));
				$joins = " INNER JOIN $joinTable ON $thatTable.$thatPK = $joinTable.$thatFK";
				return self::deleteAll($thatClass, $conditions, $force ? "$joinTable, $thatTable" : $joinTable, $joins);
			}
		}
	}
	
	
	/*****************************************************/
	/* PROTECTED STATIC UTILITY METHODS                  */
	/*****************************************************/
	
	/**
	 * Builds up a started SQL string and returns the prepared statement
	 *
	 * @param string $sql
	 * @param string $conditions
	 * @param string $order
	 * @param string $limit
	 * @param string $joins
	 * @return PDOStatement Returns a ResultSet, int, or false depending on the
	 *  query type and if there were any errors
	 */
	protected static function executeSQL($sql, $conditions = null, $limit = null, $order = null, $joins = null) {
		$params = array();
		// joins
		if ($joins)
			$sql .= " $joins";
		
		//conditions
		if ($conditions) {
			$conditions = self::prepareConditions($conditions);
			$sql .= " WHERE " . array_shift($conditions);
			$params = array_merge($params, $conditions);
		}
		
		// order
		if ($order) {
			$order = self::prepareConditions($order);
			$sql .= " ORDER BY " . array_shift($order);
			$params = array_merge($params, $order);
		}
		
		if ($limit && self::$db->getAttribute(PDO::ATTR_DRIVER_NAME) == "mysql") {
			$sql .= " LIMIT " . implode(" OFFSET ", explode(',', $limit));
		}
		
		$stmt = self::getStatement($sql, array(PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL));
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		self::executeStmt($stmt, $params);
		return $stmt;
	}
	
	protected static function executeStmt($stmt, $params) {
		if (!$stmt->execute($params)) {
			$error = $stmt->errorInfo();
			throw new Exception($error[2]);
		}
	}
	
	protected static function getStatement($sql, $options = array()) {
		if (!isset(self::$prepStmts[$sql])) {
			$stmt = self::$prepStmts[$sql] = self::$db->prepare($sql, $options);
			if (!$stmt) {
				$errorArray = self::$db->errorInfo();
				throw new Exception($errorArray[2]);
			}
		}
		return self::$prepStmts[$sql];
	}
	
	/**
	 * Generates the subclass database info and stores it statically
	 *
	 * @param string $className The name of the class
	 * @param boolean $isJoin Determines whether this is stored as join table
	 * 	info or as a normal class defenition
	 */
	protected static function getFields($table, $idField = "") {
		if (!self::$tablesLoaded) {
			self::$tables =& ActiveRecordCache::getCache();
			self::$tablesLoaded = true;
		}
		
		if (self::$tables[$table])
			return self::$tables[$table];
		
		if (!$idField)
			$idField = self::getPKName($table);
		$stmt = self::$db->query("SELECT * FROM $table WHERE $idField = -1");
		if (!$stmt) {
			return false;
		}
		
		$i = 0;
		$fields = array();
		while ($column = $stmt->getColumnMeta($i++)) {
			$props = new stdClass();
			$props->name = $column["name"];
			$props->primaryKey = in_array("not_null", $column["flags"]);
			$props->notNull = in_array("not_null", $column["flags"]);
			$props->type = $column["native_type"];
			$props->length = $column["len"];
			
			if ($props->length == 1) {
				$props->type = "boolean";
			} else {
				foreach (self::$typeconv as $regex => $type) {
					if (preg_match("/$regex/i", $column["native_type"])) {
						$props->type = $type;
						break;
					}
				}
			}
			
			$fields[$props->name] = $props;
		}
		
		self::$tables[$table] = $fields;
		return $fields;
	}
	
	/**
	 * Adds a condtion to the current condtion string or array
	 *
	 * @param string/array $conditions Condition string or array
	 * @param string/array $condition New condition to add on to the first
	 */
	protected static function prepareConditions($conditions1, $conditions2 = null) {
		if (is_string($conditions1))
			$conditions1 = array($conditions1);
		if (is_string($conditions2))
			$conditions2 = array($conditions2);
		
		if (!$conditions2)
			return $conditions1;
		if (!$conditions1)
			return $conditions2;
		$conditions1[0] .= ' AND ' . array_shift($conditions2);
		return array_merge($conditions1, $conditions2);
	}
	
	// database layout functions //
	protected static function getTableName($className) {
		$tableName = $className;
		if (self::$tableTransform == "plural")
			$tableName = Inflector::pluralize($tableName);
		if (self::$tableFormat == "under_score")
			$tableName = Inflector::underscore($tableName);
		elseif (self::$tableFormat == "camelBack")
			$tableName = lcfirst($tableName);
		return $tableName;
	}
	
	protected static function getPKName($tableName) {
		return self::substituteTableNames(self::$pk, $tableName);
	}
	
	protected static function getFKName($tableName) {
		return self::substituteTableNames(self::$fk, $tableName);
	}
	
	protected static function getJoinTableName($tableName, $tableName2) {
		return self::substituteTableNames(self::$joinTable, $tableName, $tableName2);
	}
	
	protected static function substituteTableNames($string, $tableName, $tableName2 = null) {
		$curTable = $tableName;
		while (($pos1 = strpos($string, "%")) !== false) {
			$pos2 = strpos($string, "%", $pos1 + 1);
			if ($pos2 === false)
				break;
			$length = $pos2 - $pos1 + 1;
			$replacement = substr($string, $pos1 + 1, $length - 2);
			if ($replacement == "singular_table")
				$curTable = Inflector::singularize($curTable);
			elseif ($replacement == "plural_table")
				$curTable = Inflector::pluralize($curTable);
			$string = substr_replace($string, $curTable, $pos1, $length);
			if ($tableName2)
				$curTable = $tableName2;
		}
		return $string;
	}
	
	protected function getCaller() {
		$trace = debug_backtrace();
		$i = 0;
		while ($call = $trace[$i++]) {
			if ($call["file"] != __FILE__)
				break;
		}
		$lines = file($call["file"]);
		$line = $lines[$call['line'] - 1];
		preg_match('@[a-z_][a-z0-9_]*(?=::'.$call["function"] .')@i', $line, $matches);
		return $matches[0];
	}
}



/**
 * The Inflector transforms words from singular to plural, class names to table names,
 * modularized class names to ones without, and class names to foreign keys.
 */

class Inflector {
	
	static function pluralize($word) {
		foreach (self::$pluralRules as $regex => $replace) {
			$matched = preg_match($regex, $word);
			if ($matched) {
				return preg_replace($regex, $replace, $word);
			}
		}
	   return $word;
	}
	
	static function singularize($word) {
		foreach (self::$singularRules as $regex => $replace) {
			$matched = preg_match($regex, $word);
			if ($matched) {
				return preg_replace($regex, $replace, $word);
			}
		}
	   return $word;
	}
	
	static function camelize($word) {
		return preg_replace_callback('/(^|_)(.)/',
			create_function('$matches','return strtoupper($matches[2]);'),
			$word);
	}
	
	static function underscore($word) {
		return strtolower(
					preg_replace('/([a-z])([A-Z])/', '$1_$2',
						preg_replace('/([A-Z]+)([A-Z])/', '$1_$2', $word)
					)
				);
	}
	
	static function humanize($word) {
		return preg_replace('/_/', ' ', self::underscore($word));
	}
	
	
	static private $pluralRules = array(
				'/(fish)$/i' => '$1$2',                 # fish
				'/(x|ch|ss|sh)$/i' => '$1es',            # search, switch, fix, box, process, address
				'/(series)$/i' => '$1$2',
				'/([^aeiouy]|qu)ies$/i' => '$1y',
				'/([^aeiouy]|qu)y$/i' => '$1ies',        # query, ability, agency
				'/(?:([^f])fe|([lr])f)$/i' => '$1$2ves', # half, safe, wife
				'/sis$/i' => 'ses',                       # basis, diagnosis
				'/([ti])um$/i' => '$1a',                 # datum, medium
				'/(p)erson$/i' => '$1$2eople',          # person, salesperson
				'/(m)an$/i' => '$1$2en',                # man, woman, spokesman
				'/(c)hild$/i' => '$1$2hildren',         # child
				'/s$/i' => 's',                           # no change (compatibility)
				'/$/' => 's'
	);
	
	static private $singularRules = array(
				'/(f)ish$/i' => '$1$2ish',
				'/(x|ch|ss|sh)es$/i' => '$1',
				'/(m)ovies$/i' => '$1$2ovie',
				'/(s)eries$/i' => '$1$2eries',
				'/([^aeiouy]|qu)ies$/i' => '$1y',
				'/([lr])ves$/i' => '$1f',
				'/(tive)s$/i' => '$1',
				'/([^f])ves$/i' => '$1fe',
				'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
				'/([ti])a$/i' => '$1um',
				'/(p)eople$/i' => '$1$2erson',
				'/(m)en$/i' => '$1$2an',
				'/(s)tatus$/i' => '$1$2tatus',
				'/(c)hildren$/i' => '$1$2hild',
				'/(n)ews$/i' => '$1$2ews',
				'/s$/i' => ''
	);
	
}

if (!function_exists("lcfirst")) {
	function lcfirst($string) {
		return strtolower($string{0}) . substr($string, 1);
	}
}

class ActiveRecordCache {
	private static $tables;
	
	public static function &getCache() {
		self::$tables = unserialize(file_get_contents("arcache.cache"));
		if (!is_array(self::$tables))
			self::$tables = array();
		return self::$tables;
	}
	
	public static function saveCache() {
		if (self::$tables)
			file_put_contents("arcache.cache", serialize(self::$tables));
	}
}

?>