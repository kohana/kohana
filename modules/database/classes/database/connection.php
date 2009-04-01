<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database connection wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Database_Connection_Core {

	// Configuration array
	protected $_config;

	// Required configuration keys
	protected $_config_required = array();

	// Raw server connection
	protected $_connection;

	public function __construct(array $config)
	{
		foreach ($this->_config_required as $param)
		{
			if ( ! isset($config[$param]))
			{
				throw new Database_Exception('Required configuration parameter missing: :param',
					array(':param', $param));
			}
		}

		// Store the config locally
		$this->_config = $config;
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	abstract public function connect();

	abstract public function disconnect();

	abstract public function set_charset($charset);

	abstract public function query($type, $sql);

	abstract public function escape($value);

	public function list_tables()
	{
		throw new Database_Exception('The :method is not implemented in :class',
			array(':method' => __FUNCTION__, ':class' => get_class($this)));
	}

	public function list_columns($table)
	{
		throw new Database_Exception('The :method is not implemented in :class',
			array(':method' => __FUNCTION__, ':class' => get_class($this)));
	}

} // End Database_Connection