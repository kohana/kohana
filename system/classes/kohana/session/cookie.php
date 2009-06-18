<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cookie-based session class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_Session_Cookie extends Session {

	/**
	 * Loads the session data from the secure cookie.
	 *
	 * @return  string
	 */
	protected function _read($id = NULL)
	{
		return Cookie::get($this->_name, NULL);
	}

	/**
	 * Cookie sessions have no id.
	 *
	 * @return  void
	 */
	protected function _regenerate()
	{
		return NULL;
	}

	/**
	 * Sets a secure cookie.
	 *
	 * @return  boolean
	 */
	protected function _write()
	{
		return Cookie::set($this->_name, $this->__toString(), $this->_lifetime);
	}

} // End Session_Cookie
