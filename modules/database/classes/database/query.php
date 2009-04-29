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

	// Enable profiling?
	public $profile = TRUE;

	protected $_type;
	protected $_sql;
	protected $_values = array();

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

	public function replace($key, $value)
	{
		// Replace the given value
		$this->_sql = str_replace($key, $value, $this->_sql);

		return $this;
	}

	public function values(array $values)
	{
		// Merge the new values in
		$this->_values = $values + $this->_values;

		return $this;
	}

	public function value($key, $value)
	{
		// Add or overload a new value
		$this->_values[$key] = $value;

		return $this;
	}

	public function bind($key, & $var)
	{
		// Bind a value to a variable
		$this->_values[$key] =& $var;

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

		if ( ! empty($this->_values))
		{
			// Quote all of the values
			$values = array_map(array($db, 'quote'), $this->_values);

			// Replace the values in the SQL
			$sql = strtr($sql, $values);
		}

		if ($this->profile === TRUE)
		{
			// Start profiling this query
			$token = Profiler::start($sql, 'database ('.(string) $db.')');
		}

		// Load the result
		$result = $db->query($this->_type, $sql);

		if (isset($token))
		{
			Profiler::stop($token);
		}
	}

} // End Database_Query