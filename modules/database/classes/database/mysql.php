<?php defined('SYSPATH') or die('No direct script access.');
/**
 * MySQL database connection.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Database_MySQL_Core extends Database {

	protected $_config_required = array('hostname', 'username', 'database');

	// Database name
	protected $_database;

	public function connect()
	{
		if ($this->_connection)
			return;

		extract($this->_config);

		// Clear the configuration for security
		$this->_config = array();

		// Set the connection type
		$connect = (isset($persistent) AND $persistent === TRUE) ? 'mysql_pconnect' : 'mysql_connect';

		try
		{
			// Connect to the database
			$this->_connection = $connect($hostname, $username, $password);
		}
		catch (ErrorException $e)
		{
			// No connection exists
			$this->_connection = NULL;

			// Unable to connect to the database
			throw new Database_Exception(mysql_errno(), mysql_error());
		}

		if ( ! mysql_select_db($database, $this->_connection))
		{
			// Unable to select database
			throw new Database_Exception(':error',
				array(':error' => mysql_error($this->_connection)),
				mysql_errno($this->_connection));
		}

		// Store the database name for use by meta functions
		$this->_database = $database;

		if (isset($charset))
		{
			// Set the character set
			$this->set_charset($charset);
		}
	}

	public function disconnect()
	{
		try
		{
			// Database is assumed disconnected
			$status = TRUE;

			if (is_resource($this->_connection))
			{
				$status = mysql_close($this->_connection);
			}
		}
		catch (Exception $e)
		{
			// Database is probably not disconnected
			$status = is_resource($this->_connection);
		}

		return $status;
	}

	public function set_charset($charset)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if ( ! mysql_set_charset($charset, $this->_connection))
		{
			// Unable to set charset
			throw new Database_Exception(':error',
				array(':error' => mysql_error($this->_connection)),
				mysql_errno($this->_connection));
		}
	}

	public function query($type, $sql)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		// Execute the query
		if (($result = mysql_query($sql, $this->_connection)) === FALSE)
		{
			// Query failed
			throw new Database_Exception(':error [ :query ]',
				array(':error' => mysql_error($this->_connection), ':query' => $sql),
				mysql_errno($this->_connection));
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === Database::SELECT)
		{
			// Return an iterator of results
			return new Database_MySQL_Result($result, $sql);
		}
		elseif ($type === Database::INSERT)
		{
			// Return the insert id of the row
			return mysql_insert_id($this->_connection);
		}
		else
		{
			// Return the number of rows affected
			return mysql_affected_rows($this->_connection);
		}
	}

	public function list_tables($like = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if (is_string($like))
		{
			// Search for table names
			$result = $this->query(Database::SELECT, 'SHOW TABLES LIKE '.$this->quote($like))->as_array();
		}
		else
		{
			// Find all table names
			$result = $this->query(Database::SELECT, 'SHOW TABLES')->as_array();
		}

		$tables = array();
		foreach ($result as $row)
		{
			// Get the table name from the results
			$tables[] = current($row);
		}

		return $tables;
	}

	public function list_columns($table)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		// Find all table names
		$result = $this->query(Database::SELECT, 'SHOW COLUMNS FROM '.$table)->as_array();

		$columns = array();
		foreach ($result as $row)
		{
			// Get the column name from the results
			$columns[] = $row['Field'];
		}

		return $columns;
	}

	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		if (($value = mysql_real_escape_string($value, $this->_connection)) === FALSE)
		{
			throw new Database_Exception(':error',
				array(':error' => mysql_errno($this->_connection)),
				mysql_error($this->_connection));
		}

		return $value;
	}

} // End Database_Connection_MySQL