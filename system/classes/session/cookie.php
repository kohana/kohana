<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cookie-based session class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Session_Cookie_Core extends Session {

	/**
	 * Loads the session data from the the session cookie.
	 *
	 * @return  string
	 */
	public function read()
	{
		return cookie::get($this->_name, NULL);
	}

	/**
	 * Cookie sessions have no id.
	 *
	 * @return  void
	 */
	public function regenerate()
	{
		return NULL;
	}

	/**
	 * Sets the
	 */
	public function write()
	{
		// Get the session data as a string
		$data = $this->__toString();

		return cookie::set($this->_name, $data);
	}

} // End Session_Cookie