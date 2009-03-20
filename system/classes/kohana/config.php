<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Wrapper for configuration arrays.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_Config_Core extends ArrayObject {

	// Cache prefix string
	const CACHE_PREFIX = 'kohana_configuration_';

	/**
	 * Loads all of the files in a configuration group and returns a merged
	 * array of the values.
	 *
	 * @param   string  group name
	 * @return  array
	 */
	protected static function load($group)
	{
		// Find all of the files in this group
		$files = Kohana::find_file('config', $group);

		// Configuration array
		$config = array();

		foreach ($files as $file)
		{
			// Merge each file to the configuration array
			$config = array_merge($config, require $file);
		}

		return $config;
	}

	// Configuration group name
	protected $_configuration_group;

	// Has the config group changed?
	protected $_configuration_modified = FALSE;

	/**
	 * Creates a new configuration object for the specified group. When caching
	 * is enabled, Kohana_Config will attempt to load the group from the cache.
	 *
	 * @param   string   group name
	 * @param   boolean  cache the group array
	 * @return  void
	 */
	public function __construct($group, $cache = TRUE)
	{
		// Set the configuration group name
		$this->_configuration_group = $group;

		if ($cache === FALSE)
		{
			// Load the configuration
			$config = Kohana_Config::load($group);
		}
		elseif (($config = Kohana::cache(self::CACHE_PREFIX.$group)) === NULL)
		{
			// Load the configuration, it has not been cached
			$config = Kohana_Config::load($group);

			// Create a cache of the configuration group
			Kohana::cache(self::CACHE_PREFIX.$group, $config);
		}

		// Load the array using the values as properties
		ArrayObject::__construct($config, ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Return the "changed" status of the configuration object.
	 *
	 * @return  boolean
	 */
	public function changed()
	{
		return $this->_configuration_modified;
	}

	/**
	 * Return the raw array that is being used for this object.
	 *
	 * @return  array
	 */
	public function as_array()
	{
		return $this->getArrayCopy();
	}

	/**
	 * Overloads ArrayObject::offsetSet() to set the "changed" status when
	 * modifying a configuration value.
	 *
	 * @param   string  array key
	 * @param   mixed   array value
	 * @return  mixed
	 */
	public function offsetSet($key, $value)
	{
		if ($this->offsetGet($key) !== $value)
		{
			// The value is about to be modified
			$this->_configuration_modified = TRUE;
		}

		return parent::offsetSet($key, $value);
	}

} // End Kohana_Config
