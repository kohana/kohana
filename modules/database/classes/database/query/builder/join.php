<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Join extends Database_Query_Builder {

	// Type of JOIN
	protected $_type;

	// JOIN ...
	protected $_table;

	// ON ...
	protected $_on = array();

	/**
	 * Creates a new JOIN statement for a table. Optionally, the type of JOIN
	 * can be specified as the second parameter.
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   string  type of JOIN: INNER, RIGHT, LEFT, etc
	 * @return  void
	 */
	public function __construct($table, $type = NULL)
	{
		// Set the table to JOIN on
		$this->_table = $table;

		if ($type !== NULL)
		{
			// Set the JOIN type
			$this->_type = (string) $type;
		}
	}

	/**
	 * Adds a new condition for joining.
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   mixed   column name or array($column, $alias) or object
	 * @return  $this
	 */
	public function on($c1, $c2)
	{
		$this->_on[] = array($c1, $c2);

		return $this;
	}

	/**
	 * Compile the SQL partial for a JOIN statement and return it.
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

		if ($this->_type)
		{
			$sql = strtoupper($this->_type).' JOIN';
		}
		else
		{
			$sql = 'JOIN';
		}

		// Quote the table name that is being joined
		$sql .= ' '.$db->quote_identifier($this->_table).' ON ';

		$conditions = array();
		foreach ($this->_on as $on)
		{
			// Quote each of the identifiers used for the condition
			$conditions[] = $db->quote_identifier($on[0]).' = '.$db->quote_identifier($on[1]);
		}

		// Concat the conditions "... AND ..."
		$conditions = implode(' AND ', $conditions);

		if (count($this->_on) > 1)
		{
			// Wrap the conditions in a group. Some databases (Postgre) will fail
			// when singular conditions are grouped like this.
			$sql .= '('.$conditions.')';
		}
		else
		{
			$sql .= $conditions;
		}

		return $sql;
	}

} // End Database_Query_Builder_Join
