<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Insert extends Database_Query_Builder {

	// INSERT INTO ...
	protected $_table;

	// (...) VALUES (...)
	protected $_values = array();

	public function __construct($table)
	{
		// Set the inital table name
		$this->_table = $table;

		// Start the query with no SQL
		return parent::__construct(Database::INSERT, '');
	}

	/**
	 * Sets the table to insert into.
	 *
	 * @param   mixed  table name or array($table, $alias) or object
	 * @return  $this
	 */
	public function table($table)
	{
		$this->_table = $table;

		return $this;
	}

	/**
	 * Adds or overwrites column values.
	 *
	 * @param   array   list of column => value pairs
	 */
	public function values(array $values)
	{
		$this->_values = array_merge($this->_values, $values);

		return $this;
	}

	/**
	 * Sets a column to the specified value.
	 *
	 * @param   string   column name
	 * @param   mixed    value for column
	 * @return  $this
	 */
	public function set($column, $value)
	{
		$this->_values[$column] = $value;

		return $this;
	}

	/**
	 * Compile the SQL query and return it.
	 *
	 * @param   object  Database instance
	 * @return  string
	 */
	public function compile($db = 'default')
	{
		if ( ! is_object($db))
		{
			// Get the database instance
			$db = Database::instance($db);
		}

		// Start an insertion query
		$query = 'INSERT INTO '.$db->quote_identifier($this->_table);

		// Add the column names
		$query .= ' ('.implode(', ', array_map(array($db, 'quote_identifier'), array_keys($this->_values))).')';

		// Add the values
		$query .= ' VALUES ('.implode(', ', array_map(array($db, 'quote_identifier'), $this->_values)).')';

		return $query;
	}

} // End Database_Query_Builder_Insert
