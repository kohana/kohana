<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database query wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Database_Query_Core {

	protected $_type;
	protected $_sql;
	protected $_params;

	public function __construct($type, $sql)
	{
		$this->_type = $type;
		$this->_sql = $sql;
	}

	public function __toString()
	{
		// Return the SQL of this query
		return $this->_sql;
	}

	public function value($key, $value)
	{
		$this->_params[$key] = $value;

		return $this;
	}

	public function bind($key, & $value)
	{
		$this->_params[$key] =& $value;

		return $this;
	}

	public function execute($db = 'default')
	{
		if ( ! is_object($db))
		{
			// Get the database instance
			$db = Database::instance($db);
		}

		// Import the SQL locally
		$sql = $this->_sql;

		if ( ! empty($this->_params))
		{
			// Quote all of the values
			$params = array_map(array($db, 'quote'), $this->_params);

			// Replace the values in the SQL
			$sql = strtr($sql, $params);
		}

		// Load the result
		return $db->query($this->_type, $sql);
	}

} // End Database_Query