<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Insert extends Database_Query_Builder {

	// INSERT INTO ...
	protected $_table;

	// (...)
	protected $_columns;

	// VALUES (...)
	protected $_values = array();

	/**
	 * Set the table and columns for an insert.
	 * 
	 * @param   mixed  table name or array($table, $alias) or object
	 * @param   array  column names
	 * @return  void
	 */
	public function __construct($table, array $columns = NULL)
	{
		// Set the inital table name
		$this->_table = $table;

		if ( ! empty($columns))
		{
			// Set the column names
			$this->_columns = $columns;
		}

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
	 * Set the columns that will be inserted.
	 * 
	 * @param   array  column names
	 * @return  $this
	 */
	public function columns(array $columns)
	{
		$this->_columns = $columns;

		return $this;
	}

	/**
	 * Adds or overwrites values. Multiple value sets can be added.
	 *
	 * @param   array   values list
	 * @param   ...
	 * @return  $this
	 */
	public function values(array $values)
	{
		// Get all of the passed values
		$values = func_get_args();

		$this->_values = array_merge($this->_values, $values);

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

		// Callback for quoting values
		$quote = array($db, 'quote');

		// Start an insertion query
		$query = 'INSERT INTO '.$db->quote_identifier($this->_table);

		// Add the column names
		$query .= ' ('.implode(', ', array_map(array($db, 'quote_identifier'), $this->_columns)).') ';

		$groups = array();
		foreach ($this->_values as $group)
		{
			$groups[] = '('.implode(', ', array_map($quote, $group)).')';
		}

		// Add the values
		$query .= 'VALUES '.implode(', ', $groups);

		return $query;
	}

} // End Database_Query_Builder_Insert
