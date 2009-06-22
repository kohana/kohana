<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Select extends Database_Query_Builder_Where {

	// SELECT ...
	protected $_select = array('*');

	// FROM ...
	protected $_from = array();

	// JOIN ...
	protected $_join = array();

	// GROUP BY ...
	protected $_group_by = array();

	// HAVING ...
	protected $_having = array();

	// ORDER BY ...
	protected $_order_by = array();

	// LIMIT ...
	protected $_limit = NULL;

	// OFFSET ...
	protected $_offset = NULL;

	// The last JOIN statement created
	protected $_last_join;

	/**
	 * Sets the initial columns to select from.
	 *
	 * @param   array  column list
	 * @return  void
	 */
	public function __construct(array $columns = NULL)
	{
		if ( ! empty($columns))
		{
			// Set the initial columns
			$this->_select = $columns;
		}

		// Start the query with no actual SQL statement
		parent::__construct(Database::SELECT, '');
	}

	/**
	 * Choose the columns to select from.
	 *
	 * @param   mixed  column name or array($column, $alias) or object
	 * @param   ...
	 * @return  $this
	 */
	public function select($columns = NULL)
	{
		$columns = func_get_args();

		$this->_columns = array_merge($this->_columns, $columns);

		return $this;
	}

	/**
	 * Choose the tables to select "FROM ..."
	 *
	 * @param   mixed  table name or array($table, $alias) or object
	 * @param   ...
	 * @return  $this
	 */
	public function from($tables)
	{
		$tables = func_get_args();

		$this->_from = array_merge($this->_from, $tables);

		return $this;
	}

	/**
	 * Adds addition tables to "JOIN ...".
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   string  join type (LEFT, RIGHT, INNER, etc)
	 * @return  $this
	 */
	public function join($table, $type = NULL)
	{
		$this->_join[] = $this->_last_join = new Database_Query_Builder_Join($table, $type);

		return $this;
	}

	/**
	 * Adds "ON ..." conditions for the last created JOIN statement.
	 * 
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   mixed   column name or array($column, $alias) or object
	 * @return  $this
	 */
	public function on($c1, $c2)
	{
		$this->_last_join->on($c1, $c2);

		return $this;
	}

	/**
	 * Creates a "GROUP BY ..." filter.
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   ...
	 * @return  $this
	 */
	public function group_by($columns)
	{
		$columns = func_get_args();

		$this->_group_by = array_merge($this->_group_by, $columns);

		return $this;
	}

	/**
	 * Alias of and_having()
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   string  logic operator
	 * @param   mixed   column value
	 * @return  $this
	 */
	public function having($column, $op, $value = NULL)
	{
		return $this->and_having($column, $op, $value);
	}

	/**
	 * Creates a new "AND HAVING" condition for the query.
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   string  logic operator
	 * @param   mixed   column value
	 * @return  $this
	 */
	public function and_having($column, $op, $value = NULL)
	{
		$this->_having[] = array('AND' => array($column, $op, $value));

		return $this;
	}

	/**
	 * Creates a new "OR HAVING" condition for the query.
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   string  logic operator
	 * @param   mixed   column value
	 * @return  $this
	 */
	public function or_having($column, $op, $value = NULL)
	{
		$this->_having[] = array('OR' => array($column, $op, $value));

		return $this;
	}

	/**
	 * Alias of and_having_open()
	 *
	 * @return  $this
	 */
	public function having_open()
	{
		return $this->and_having_open();
	}

	/**
	 * Opens a new "AND HAVING (...)" grouping.
	 *
	 * @return  $this
	 */
	public function and_having_open()
	{
		$this->_having[] = array('AND' => '(');

		return $this;
	}

	/**
	 * Opens a new "OR HAVING (...)" grouping.
	 *
	 * @return  $this
	 */
	public function or_having_open()
	{
		$this->_having[] = array('OR' => '(');

		return $this;
	}

	/**
	 * Closes an open "AND HAVING (...)" grouping.
	 *
	 * @return  $this
	 */
	public function having_close()
	{
		return $this->and_having_close();
	}

	/**
	 * Closes an open "AND HAVING (...)" grouping.
	 *
	 * @return  $this
	 */
	public function and_having_close()
	{
		$this->_having[] = array('AND' => ')');

		return $this;
	}

	/**
	 * Closes an open "OR HAVING (...)" grouping.
	 *
	 * @return  $this
	 */
	public function or_having_close()
	{
		$this->_having[] = array('OR' => ')');

		return $this;
	}

	/**
	 * Applies sorting with "ORDER BY ..."
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   string  direction of sorting
	 * @return  $this
	 */
	public function order_by($column, $direction = NULL)
	{
		$columns = func_get_args();

		$this->_order_by[] = array($column, $direction);

		return $this;
	}

	/**
	 * Return up to "LIMIT ..." results
	 * 
	 * @param   integer  maximum results to return
	 * @return  $this
	 */
	public function limit($number)
	{
		$this->_limit = (int) $number;

		return $this;
	}

	/**
	 * Start returning results after "OFFSET ..."
	 * 
	 * @param   integer   starting result number
	 * @return  $this
	 */
	public function offset($number)
	{
		$this->_offset = (int) $number;

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
		// Callback to quote identifiers
		$quote_ident = array($db, 'quote_identifier');

		// Start a selection query
		$query = 'SELECT '.implode(', ', array_map($quote_ident, $this->_select));

		if ( ! empty($this->_from))
		{
			// Set tables to select from
			$query .= ' FROM '.implode(', ', array_map($quote_ident, $this->_from));
		}

		if ( ! empty($this->_join))
		{
			// Add tables to join
			$query .= ' '.Database_Query_Builder::compile_join($db, $this->_join);
		}

		if ( ! empty($this->_where))
		{
			// Add selection conditions
			$query .= ' WHERE '.Database_Query_Builder::compile_conditions($db, $this->_where);
		}

		if ( ! empty($this->_group_by))
		{
			// Add sorting
			$query .= ' GROUP BY '.implode(', ', array_map($quote_ident, $this->_group_by));
		}

		if ( ! empty($this->_having))
		{
			// Add filtering conditions
			$query .= ' HAVING '.Database_Query_Builder::compile_conditions($db, $this->_having);
		}

		if ( ! empty($this->_order_by))
		{
			// Add sorting
			$query .= ' '.Database_Query_Builder::compile_order_by($db, $this->_order_by);
		}

		if ($this->_limit !== NULL)
		{
			// Add limiting
			$query .= ' LIMIT '.$this->_limit;
		}

		if ($this->_offset !== NULL)
		{
			// Add offsets
			$query .= ' OFFSET '.$this->_offset;
		}

		return $query;
	}

} // End Database_Query_Select
