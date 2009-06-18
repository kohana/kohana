<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Native PHP session class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_Session_Native extends Session {

	/**
	 * Starts the session and references the $_SESSION global internally.
	 *
	 * @param   string  session id
	 * @return  void
	 */
	protected function _read($id = NULL)
	{
		// Set the cookie lifetime
		session_set_cookie_params($this->_lifetime);

		// Set the session cookie name
		session_name($this->_name);

		if ($id)
		{
			// Set the session id
			session_id($id);
		}

		// Start the session
		session_start();

		// Use the $_SESSION global for storing data
		$this->_data =& $_SESSION;

		return NULL;
	}

	/**
	 * Generate a new session id and return it.
	 *
	 * @return  string
	 */
	protected function _regenerate()
	{
		// Regenerate the session id
		session_regenerate_id();

		return session_id();
	}

	/**
	 * Writes and closes the current session. This can only be called once
	 *
	 * @return  boolean
	 */
	protected function _write()
	{
		// Write and close the session
		session_write_close();

		return TRUE;
	}

} // End Session_Native
