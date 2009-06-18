<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Base session class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Kohana_Session {

	// Session instances
	protected static $instances = array();

	/**
	 * Creates a singleton session of the given type. Some session types
	 * (native, database) also support restarting a session by passing a
	 * session id as the second parameter.
	 *
	 * @param   string   type of session (native, cookie, etc)
	 * @param   string   session identifier
	 * @return  Session
	 */
	public static function instance($type = 'native', $id = NULL)
	{
		if ( ! isset(Session::$instances[$type]))
		{
			// Load the configuration for this type
			$config = Kohana::config('session')->get($type);

			// Set the session class name
			$class = 'Session_'.ucfirst($type);

			// Create a new session instance
			Session::$instances[$type] = new $class($config, $id);
		}

		return Session::$instances[$type];
	}

	// Cookie name
	protected $_name = 'session';

	// Cookie lifetime
	protected $_lifetime  = 0;

	// Encrypt session data?
	protected $_encrypted = FALSE;

	// Session data
	protected $_data = array();

	/**
	 * Overloads the name, lifetime, and encrypted session settings.
	 *
	 * @param   array   configuration
	 * @param   string  session id
	 * @return  void
	 */
	protected function __construct(array $config = NULL, $id = NULL)
	{
		if (isset($config['name']))
		{
			// Cookie name to store the session id in
			$this->_name = (string) $config['name'];
		}

		if (isset($config['lifetime']))
		{
			// Cookie lifetime
			$this->_lifetime = (int) $config['lifetime'];
		}

		if (isset($config['encrypted']))
		{
			if ($config['encrypted'] === TRUE)
			{
				// Use the default Encrypt instance
				$config['encrypted'] = 'default';
			}

			// Enable or disable encryption of data
			$this->_encrypted = $config['encrypted'];
		}

		// Load the session
		$this->read($id);
	}

	/**
	 * Session object is rendered to a serialized string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		// Serialize the data array
		$data = serialize($this->_data);

		if ($this->_encrypted)
		{
			// Encrypt the data using the default key
			$data = Encrypt::instance($this->_encrypted)->encode($data);
		}
		else
		{
			// Obfuscate the data with base64 encoding
			$data = base64_encode($data);
		}

		return $data;
	}

	/**
	 * Returns the current session array.
	 *
	 * @return  array
	 */
	public function & as_array()
	{
		return $this->_data;
	}

	/**
	 * Get a variable from the session array.
	 *
	 * @param   string   variable name
	 * @param   mixed    default value to return
	 * @return  mixed
	 */
	public function get($key, $default = NULL)
	{
		return array_key_exists($key, $this->_data) ? $this->_data[$key] : $default;
	}

	/**
	 * Set a variable in the session array.
	 *
	 * @param   string   variable name
	 * @param   mixed    value
	 * @return  Session
	 */
	public function set($key, $value)
	{
		$this->_data[$key] = $value;

		return $this;
	}

	/**
	 * Removes a variable in the session array.
	 *
	 * @param   string  variable name
	 * @return  Session
	 */
	public function delete($key)
	{
		unset($this->_data[$key]);

		return $this;
	}

	/**
	 * Loads the session data.
	 *
	 * @param   string   session id
	 * @return  void
	 */
	public function read($id = NULL)
	{
		if (is_string($data = $this->_read($id)))
		{
			try
			{
				if ($this->_encrypted)
				{
					// Decrypt the data using the default key
					$data = Encrypt::instance($this->_encrypted)->decode($data);
				}
				else
				{
					// Decode the base64 encoded data
					$data = base64_decode($data);
				}

				// Unserialize the data
				$data = unserialize($data);
			}
			catch (Exception $e)
			{
				// Ignore all reading errors
			}
		}

		if (is_array($data))
		{
			// Load the data locally
			$this->_data = $data;
		}
	}

	/**
	 * Generates a new session id and returns it.
	 *
	 * @return  string
	 */
	public function regenerate()
	{
		return $this->_regenerate();
	}

	/**
	 * Sets the last_active timestamp and saves the session.
	 *
	 * @return  boolean
	 */
	public function write()
	{
		// Set the last active timestamp
		$this->_data['last_active'] = time();

		return $this->_write();
	}

	/**
	 * Loads the raw session data string and returns it.
	 *
	 * @param   string   session id
	 * @return  string
	 */
	abstract protected function _read($id = NULL);

	/**
	 * Generate a new session id and return it.
	 *
	 * @return  string
	 */
	abstract protected function _regenerate();

	/**
	 * Writes the current session.
	 *
	 * @return  boolean
	 */
	abstract protected function _write();

} // End Session