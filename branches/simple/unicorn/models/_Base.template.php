[?php

class <?php echo $base->_name ?>Base extends Model {
	public $_name = '<?php echo $base->_name ?>';

	private $_schema = <?php arrayToPhp($base->_schema); ?>;

	private $_associations = <?php arrayToPhp($base->_associations); ?>;
}