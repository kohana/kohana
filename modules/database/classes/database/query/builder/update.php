<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Update extends Database_Query_Builder_Where {

	// UPDATE ...
	protected $_table;

	// SET ...
	protected $_set = array();

	/**
	 * Set the table for a update.
	 *
	 * @param   mixed  table name or array($table, $alias) or object
	 * @return  void
	 */
	public function __construct($table)
	{
		// Set the inital table name
		$this->_table = $table;

		// Start the query with no SQL
		return parent::__construct(Database::UPDATE, '');
	}

	/**
	 * Sets the table to update.
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
	 * Set the value of a column.
	 *
	 * @param   mixed  table name or array($table, $alias) or object
	 * @param   mixed  column value
	 * @return  $this
	 */
	public function set($column, $value)
	{
		$this->_set[] = array($column, $value);

		return $this;
	}

	/**
	 * Compile the SQL query and return it.
	 *
	 * @param   object  Database instance
	 * @return  string
	 */
	public function compile(Database $db)
	{
		// Start an update query
		$query = 'UPDATE '.$db->quote_identifier($this->_table);

		$update = array();
		foreach ($this->_set as $set)
		{
			$update[] = $db->quote_identifier($set[0]).' = '.$db->quote($set[1]);
		}

		// Add the columns to update
		$query .= ' SET '.implode(', ', $update);

		if ( ! empty($this->_where))
		{
			// Add selection conditions
			$query .= ' WHERE '.Database_Query_Builder::compile_conditions($db, $this->_where);
		}

		return $query;
	}

} // End Database_Query_Builder_Update
