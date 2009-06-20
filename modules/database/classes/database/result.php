<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database result wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Database_Result implements Countable, Iterator, SeekableIterator, ArrayAccess {

	// Executed SQL for this result
	protected $_query;

	// Raw result resource
	protected $_result;

	// Total number of rows and current row
	protected $_total_rows  = 0;
	protected $_current_row = 0;

	/**
	 * Sets the total number of rows and stores the result locally.
	 *
	 * @param   mixed   query result
	 * @param   string  SQL query
	 * @return  void
	 */
	public function __construct($result, $sql)
	{
		// Store the result locally
		$this->_result = $result;

		// Store the SQL locally
		$this->_query = $sql;
	}

	/**
	 * Result destruction cleans up all open result sets.
	 */
	abstract public function __destruct();

	/**
	 * Return all of the rows in the result as an array.
	 *
	 * @return  array
	 */
	abstract public function as_array();

	/**
	 * Return the named column from the current row.
	 *
	 * @param   string  column to get
	 * @param   mixed   default value if the column does not exist
	 * @return  mixed
	 */
	public function get($name, $default = NULL)
	{
		// Get the current row
		$row = $this->current();

		return array_key_exists($name, $row) ? $row[$name] : $default;
	}

	/**
	 * Countable: count
	 */
	public function count()
	{
		return $this->_total_rows;
	}

	/**
	 * ArrayAccess: offsetExists
	 */
	public function offsetExists($offset)
	{
		if ($this->_total_rows > 0)
		{
			$min = 0;
			$max = $this->_total_rows - 1;

			return ! ($offset < $min OR $offset > $max);
		}

		return FALSE;
	}

	/**
	 * ArrayAccess: offsetSet
	 *
	 * @throws  Kohana_Database_Exception
	 */
	final public function offsetSet($offset, $value)
	{
		throw new Kohana_Exception('Database results are read-only');
	}

	/**
	 * ArrayAccess: offsetUnset
	 *
	 * @throws  Kohana_Database_Exception
	 */
	final public function offsetUnset($offset)
	{
		throw new Kohana_Exception('Database results are read-only');
	}

	/**
	 * Iterator: current
	 */
	public function current()
	{
		return $this->offsetGet($this->_current_row);
	}

	/**
	 * Iterator: key
	 */
	public function key()
	{
		return $this->_current_row;
	}

	/**
	 * Iterator: next
	 */
	public function next()
	{
		++$this->_current_row;
		return $this;
	}

	/**
	 * Iterator: prev
	 */
	public function prev()
	{
		--$this->_current_row;
		return $this;
	}

	/**
	 * Iterator: rewind
	 */
	public function rewind()
	{
		$this->_current_row = 0;
		return $this;
	}

	/**
	 * Iterator: valid
	 */
	public function valid()
	{
		return $this->offsetExists($this->_current_row);
	}

} // End Database_Result