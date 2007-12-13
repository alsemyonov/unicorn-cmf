<?php

class NodeBase extends Model {
	public $_name = 'Node';

	private $_schema = array(
		'id' => 'pk', 
		'created_at' => 'int', 
		'updated_at' => 'int',
		'title' => array('type' => 'string', 'size' => 255, 'default' => null, 'null' => null)
		'body' => 'text',
		'user_id' => 'int',
	);

	private $_associations = array(
		'belongsTo' => array(
			'Author' => array(
				'className' => 'User',
				'foreignKey' => 'user_id'
			),
		),
	);

	
}

?>