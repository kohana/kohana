<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cookie helper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class cookie_Core {

	/**
	 * @var  string  Magic salt to add to the cookie
	 */
	public static $salt = 'kooky';

	/**
	 * @var  integer  Number of seconds before the cookie expires
	 */
	public static $expiration = 0;

	/**
	 * @var  string  Restrict the path that the cookie is available to
	 */
	public static $path = '/';

	/**
	 * @var  string  Restrict the domain that the cookie is available to
	 */
	public static $domain = NULL;

	/**
	 * @var  boolean  Only transmit cookies over secure connections
	 */
	public static $secure = FALSE;

	/**
	 * @var  boolean  Only transmit cookies over HTTP, disabling Javascript access
	 */
	public static $httponly = FALSE;

	/**
	 * Gets the value of a signed cookie. Unsigned cookies will not be returned.
	 *
	 * @param   string  cookie name
	 * @param   mixed   default value to return
	 * @return  string
	 */
	public static function get($key, $default = NULL)
	{
		if ( ! isset($_COOKIE[$key]))
		{
			// The cookie does not exist
			return $default;
		}

		// Get the cookie value
		$cookie = $_COOKIE[$key];

		// Find the position of the split between salt and contents
		$split = strlen(cookie::salt($key, NULL));

		if (isset($cookie[$split]) AND $cookie[$split] === '~')
		{
			// Separate the salt and the value
			list ($hash, $value) = explode('~', $cookie, 2);

			if (cookie::salt($key, $value) === $hash)
			{
				// Cookie signature is valid
				return $value;
			}

			// The cookie signature is invalid, delete it
			cookie::delete($key);
		}

		return $default;
	}

	/**
	 * Sets a signed cookie.
	 *
	 * @param   string   name of cookie
	 * @param   string   contents of cookie
	 * @param   integer  lifetime in seconds
	 * @return  boolean
	 */
	public static function set($key, $value, $expiration = NULL)
	{
		if ($expiration === NULL)
		{
			// Use the default expiration
			$expiration = cookie::$expiration;
		}

		if ($expiration !== 0)
		{
			// The expiration is expected to be a UNIX timestamp
			$expiration += time();
		}

		// Add the salt to the cookie value
		$value = cookie::salt($key, $value).'~'.$value;

		return setcookie($key, $value, $expiration, cookie::$path, cookie::$domain, cookie::$secure, cookie::$httponly);
	}

	/**
	 * Deletes a cookie by emptying the contents and expiring it.
	 *
	 * @param   string   cookie name
	 * @return  boolean
	 */
	public static function delete($key)
	{
		// Remove the cookie
		unset($_COOKIE[$key]);

		// Expire the cookie
		return cookie::set($key, NULL, -3600);
	}

	/**
	 * Generates a salt string for a cookie based on the name and contents.
	 *
	 * @param   string   name of cookie
	 * @param   string   contents of cookie
	 * @return  string
	 */
	public static function salt($key, $value)
	{
		// Determine the user agent
		$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';

		return sha1($agent.$key.$value.cookie::$salt);
	}

} // End cookie