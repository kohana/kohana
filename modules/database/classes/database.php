<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database connection wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Database {

	// Query types
	const SELECT =  1;
	const INSERT =  2;
	const UPDATE =  3;
	const DELETE =  4;

	/**
	 * @var  array  Database instances
	 */
	public static $instances = array();

	/**
	 * Get a singleton Database instance. If configuration is not specified,
	 * it will be loaded from the database configuration file using the same
	 * group as the name.
	 *
	 * @param   string   instance name
	 * @param   array    configuration parameters
	 * @return  Database
	 */
	public static function instance($name = 'default', array $config = NULL)
	{
		if ( ! isset(Database::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this database
				$config = Kohana::config('database')->$name;
			}

			if ( ! isset($config['type']))
			{
				throw new Kohana_Exception('Database type not defined in :name configuration',
					array(':name' => $name));
			}

			// Set the driver class name
			$driver = 'Database_'.ucfirst($config['type']);

			// Create the database connection instance
			new $driver($name, $config);
		}

		return Database::$instances[$name];
	}

	/**
	 * @var  string  the last query executed
	 */
	public $last_query;

	// Character that is used to quote identifiers
	protected $_identifier = '"';

	// Instance name
	protected $_instance;

	// Raw server connection
	protected $_connection;

	// Configuration array
	protected $_config;

	/**
	 * Stores the database configuration locally and name the instance.
	 *
	 * @return  void
	 */
	final protected function __construct($name, array $config)
	{
		// Set the instance name
		$this->_instance = $name;

		// Store the config locally
		$this->_config = $config;

		// Store the database instance
		Database::$instances[$name] = $this;
	}

	/**
	 * Disconnect from the database when the object is destroyed.
	 *
	 * @return  void
	 */
	final public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * Returns the database instance name.
	 *
	 * @return  string
	 */
	final public function __toString()
	{
		return $this->_instance;
	}

	/**
	 * Connect to the database.
	 *
	 * @throws  Database_Exception
	 * @return  void
	 */
	abstract public function connect();

	/**
	 * Disconnect from the database
	 *
	 * @return  boolean
	 */
	abstract public function disconnect();

	/**
	 * Set the connection character set.
	 *
	 * @throws  Database_Exception
	 * @param   string   character set name
	 * @return  void
	 */
	abstract public function set_charset($charset);

	/**
	 * Perform an SQL query of the given type.
	 *
	 * @param   integer  Database::SELECT, Database::INSERT, etc
	 * @param   string   SQL query
	 * @return  object   Database_Result for SELECT queries
	 * @return  mixed    the insert id for INSERT queries
	 * @return  integer  number of affected rows for all other queries
	 */
	abstract public function query($type, $sql);

	/**
	 * List all of the tables in the database. Optionally, a LIKE string can
	 * be used to search for specific tables.
	 *
	 * @param   string   table to search for
	 * @return  array
	 */
	abstract public function list_tables($like = NULL);

	/**
	 * Lists all of the columns in a table. Optionally, a LIKE string can be
	 * used to search for specific fields.
	 *
	 * @param   string  table to get columns from
	 * @param   string  column to search for
	 * @return  array
	 */
	abstract public function list_columns($table, $like = NULL);

	/**
	 * Quote a value for an SQL query.
	 *
	 * @param   mixed   any value to quote
	 * @return  string
	 */
	public function quote($value)
	{
		if ($value === NULL)
		{
			return 'NULL';
		}
		elseif ($value === TRUE OR $value === FALSE)
		{
			return $value ? 'TRUE' : 'FALSE';
		}
		elseif (is_object($value))
		{
			if ($value instanceof Database_Query)
			{
				return '('.$value->compile($this).')';
			}
			else
			{
				return (string) $value;
			}
		}
		elseif (is_array($value))
		{
			return implode(', ', array_map(array($this, __FUNCTION__), $value));
		}
		elseif (is_int($value) OR (is_string($value) AND ctype_digit($value)))
		{
			return (int) $value;
		}

		// SQL standard is to use single-quotes for all values
		return '\''.$this->escape($value).'\'';
	}

	/**
	 * Quote a database identifier, such as a table or column name.
	 *
	 * @param   mixed   any identifier
	 * @return  string
	 */
	public function quote_identifier($value)
	{
		if ($value === '*')
		{
			return $value;
		}
		elseif (is_object($value))
		{
			if ($value instanceof Database_Query)
			{
				// Compile the sub-query using the current database
				return '('.$value->compile($this).')';
			}
			else
			{
				return (string) $value;
			}
		}
		elseif (is_array($value))
		{
			// Separate the column and alias
			list ($value, $alias) = $value;

			return $this->quote_identifier($value).' AS '.$this->quote_identifier($alias);
		}

		if (strpos($value, '.') !== FALSE)
		{
			// Dots are used to separate schema, table, and column names
			$value = str_replace('.', "{$this->_identifier}.{$this->_identifier}", $value);
		}

		return $this->_identifier.$value.$this->_identifier;
	}

	/**
	 * Sanitize a string by escaping characters that could cause an SQL
	 * injection attack.
	 *
	 * @param   string   value to quote
	 * @return  string
	 */
	abstract public function escape($value);

} // End Database_Connection