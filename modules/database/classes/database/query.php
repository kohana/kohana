<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database query wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Database_Query {

	protected $_type;
	protected $_sql;
	protected $_parameters = array();

	public function __construct($type, $sql)
	{
		$this->_type = $type;
		$this->_sql = $sql;
	}

	final public function __toString()
	{
		try
		{
			// Return the SQL string
			return $this->compile(Database::instance());
		}
		catch (Exception $e)
		{
			// Return the exception message
			return $e->getMessage().' in '.Kohana::debug_path($e->getFile()).' [ '.$e->getLine().' ]';
		}
	}

	public function replace($key, $value)
	{
		// Replace the given value
		$this->_sql = str_replace($key, $value, $this->_sql);

		return $this;
	}

	public function parameters(array $params)
	{
		// Merge the new parameters in
		$this->_parameters = $params + $this->_parameters;

		return $this;
	}

	public function param($param, $value)
	{
		// Add or overload a new parameter
		$this->_parameters[$param] = $value;

		return $this;
	}

	public function bind($param, & $var)
	{
		// Bind a value to a variable
		$this->_parameters[$param] =& $var;

		return $this;
	}

	public function compile($db = 'default')
	{
		if ( ! is_object($db))
		{
			// Get the database instance
			$db = Database::instance($db);
		}

		// Import the SQL locally
		$sql = $this->_sql;

		if ( ! empty($this->_parameters))
		{
			// Quote all of the values
			$values = array_map(array($db, 'quote'), $this->_parameters);

			// Replace the values in the SQL
			$sql = strtr($sql, $values);
		}

		return $sql;
	}

	public function execute($db = 'default')
	{
		if ( ! is_object($db))
		{
			// Get the database instance
			$db = Database::instance($db);
		}

		// Compile the SQL for this query
		$sql = $this->compile();

		if ( ! empty($this->_config['profiling']))
		{
			// Start profiling this query
			$benchmark = Profiler::start('Query ('.(string) $db.')', $sql);
		}

		// Load the result
		$result = $db->query($this->_type, $sql);

		if (isset($benchmark))
		{
			// Stop profiling
			Profiler::stop($benchmark);
		}

		return $result;
	}

} // End Database_Query