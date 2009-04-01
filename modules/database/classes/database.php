<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database access.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Database_Core {

	const SELECT =  1;
	const INSERT =  2;
	const UPDATE =  3;
	const DELETE =  4;
	const CREATE = -2;
	const ALTER  = -3;
	const DROP   = -4;

	public static function instance($name, $cached = TRUE)
	{
		static $instances;

		if ( ! isset($instances[$name]))
		{
			$config = Kohana::config('database', $cached)->$name;

			$instances[$name] = new Database($config);
		}

		return $instances[$name];
	}

	protected $_driver;

	public function __construct(array $config)
	{
		if ( ! isset($config['type']))
			throw new Kohana_Exception('Database type not defined in configuration');

		// Set the driver class name
		$driver = 'Database_'.ucfirst($config['type']).'_Connection';

		// Load the driver
		$this->_driver = new $driver($config);
	}

	public function query($type, $sql)
	{
		return $this->_driver->query($type, $sql);
	}

	public function escape($value)
	{
		return $this->_driver->escape($value);
	}

	public function select($sql)
	{
		return $this->_driver->query(Database::SELECT, $sql);
	}

	public function insert($sql)
	{
		return $this->_driver->query(Database::INSERT, $sql);
	}

	public function update($sql)
	{
		return $this->_driver->query(Database::UPDATE, $sql);
	}

	public function delete($sql)
	{
		return $this->_driver->query(Database::DELETE, $sql);
	}

} // End Database