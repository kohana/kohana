<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database query wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Database_Query_Core {

	protected $_type;
	protected $_sql;
	protected $_params;

	protected $_last_query;
	protected $_result;

	public function __construct($type, $sql)
	{
		$this->_type = $type;
		$this->_sql = $sql;
	}

	public function set($key, $value)
	{
		$this->_params[$key] = $value;
	}

	public function bind($key, & $value)
	{
		$this->_params[$key] =& $value;
	}

	public function execute($db = 'default')
	{
		if ( ! is_object($db))
		{
			// Get the database instance
			$db = Database::instance($db);
		}

		// Import the SQL locally for modification
		$sql = $this->_sql;

		if ($params = $this->_params)
		{
			foreach ($params as $key => $value)
			{
				switch (gettype($value))
				{
					case 'integer':
						// Nothing
					break;
					case 'null':
						$value = 'NULL';
					break;
					default:
						$value = "'".$db->escape((string) $value)."'";
					break;
				}

				// Escape each parameter value
				$params[$key] = $value;
			}

			// Replace the parameters in the SQL
			$sql = strtr($sql, $params);
		}

		// Set the last query
		$this->_last_query = $sql;

		// Load the result
		$result = $db->query($this->_type, $sql);

		return $result;
	}

} // End Database_Query