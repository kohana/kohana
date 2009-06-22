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

	// Query type
	protected $_type;

	// SQL statement
	protected $_sql;

	// Quoted query parameters
	protected $_parameters = array();

	/**
	 * Creates a new SQL query of the specified type.
	 *
	 * @param   integer  query type: Database::SELECT, Database::INSERT, etc
	 * @param   string   query string
	 * @return  void
	 */
	public function __construct($type, $sql)
	{
		$this->_type = $type;
		$this->_sql = $sql;
	}

	/**
	 * Return the SQL query string.
	 *
	 * @return  string
	 */
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

	/**
	 * Get the type of the query.
	 *
	 * @return  integer
	 */
	public function type()
	{
		return $this->_type;
	}

	/**
	 * Set the value of a parameter in the query.
	 *
	 * @param   string   parameter key to replace
	 * @param   mixed    value to use
	 * @return  $this
	 */
	public function param($param, $value)
	{
		// Add or overload a new parameter
		$this->_parameters[$param] = $value;

		return $this;
	}

	/**
	 * Bind a variable to a parameter in the query.
	 *
	 * @param   string  parameter key to replace
	 * @param   mixed   variable to use
	 * @return  $this
	 */
	public function bind($param, & $var)
	{
		// Bind a value to a variable
		$this->_parameters[$param] =& $var;

		return $this;
	}

	/**
	 * Add multiple parameters to the query.
	 *
	 * @param   array  list of parameters
	 * @return  $this
	 */
	public function parameters(array $params)
	{
		// Merge the new parameters in
		$this->_parameters = $params + $this->_parameters;

		return $this;
	}

	/**
	 * Compile the SQL query and return it. Replaces any parameters with their
	 * given values.
	 *
	 * @param   object  Database instance
	 * @return  string
	 */
	public function compile(Database $db)
	{
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

	/**
	 * Execute the current query on the given database.
	 *
	 * @param   mixed  Database instance or name of instance
	 * @return  object   Database_Result for SELECT queries
	 * @return  mixed    the insert id for INSERT queries
	 * @return  integer  number of affected rows for all other queries
	 */
	public function execute($db = 'default')
	{
		if ( ! is_object($db))
		{
			// Get the database instance
			$db = Database::instance($db);
		}

		if ( ! empty($this->_config['profiling']))
		{
			// Start profiling this query
			$benchmark = Profiler::start('Query ('.(string) $db.')');
		}

		// Compile the SQL for this query
		$sql = $this->compile($db);

		// Load the result
		$result = $db->query($this->_type, $sql);

		if (isset($benchmark))
		{
			// Stop profiling
			Profiler::stop($benchmark);

			// Add the SQL as the benchmark name
			Profiler::name($benchmark, $sql);
		}

		return $result;
	}

} // End Database_Query