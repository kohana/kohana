<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * The Encrypt library provides two-way encryption of text and binary strings
 * using the MCrypt extension.
 * @see http://php.net/mcrypt
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_Encrypt {

	/**
	 * @var  array  Encrypt class instances
	 */
	public static $instances = array();

	// OS-dependant RAND type to use
	protected static $_rand;

	/**
	 * Returns a singleton instance of Encrypt. An encryption key must be provided,
	 * but default configuration settings will be applied:
	 *
	 * "nofb" mode, produces short output with high entropy and supports IV
	 * "rijndael-128" (128-bit AES) cipher, the industry standard
	 *
	 * @param   string  configuration group name
	 * @return  object
	 */
	public static function instance($name = 'default')
	{
		if ( ! isset(Encrypt::$instances[$name]))
		{
			// Load the configuration data
			$config = Kohana::config('encrypt')->{$name};

			if ( ! isset($config['mode']))
			{
				// Add the default mode
				$config['mode'] = MCRYPT_MODE_NOFB;
			}

			if ( ! isset($config['cipher']))
			{
				// Add the default cipher
				$config['cipher'] = MCRYPT_RIJNDAEL_128;
			}

			// Create a new instance
			Encrypt::$instances[$name] = new Encrypt($config['key'], $config['mode'], $config['cipher']);
		}

		return Encrypt::$instances[$name];
	}

	/**
	 * Creates a new mcrypt wrapper.
	 * @see  http://php.net/mcrypt
	 *
	 * @param   string   encryption key
	 * @param   string   mcrypt mode
	 * @param   string   mcrypt cipher
	 * @throws  Kohana_Exception
	 */
	public function __construct($key, $mode, $cipher)
	{
		// Find the max length of the key, based on cipher and mode
		$size = mcrypt_get_key_size($cipher, $mode);

		if (isset($key[$size]))
		{
			// Shorten the key to the maximum size
			$key = substr($key, 0, $size);
		}

		// Store the key, mode, and cipher
		$this->_key    = $key;
		$this->_mode   = $mode;
		$this->_cipher = $cipher;

		// Store the IV size
		$this->_iv_size = mcrypt_get_iv_size($this->_cipher, $this->_mode);
	}

	/**
	 * Encrypts a string and returns an encrypted string that can be decoded.
	 *
	 * @param   string  data to be encrypted
	 * @return  string  encrypted data
	 */
	public function encode($data)
	{
		// Set the rand type if it has not already been set
		if (Encrypt::$_rand === NULL)
		{
			if (Kohana::$is_windows)
			{
				// Windows only supports the system random number generator
				Encrypt::$_rand = MCRYPT_RAND;
			}
			else
			{
				if (defined('MCRYPT_DEV_URANDOM'))
				{
					// Use /dev/urandom
					Encrypt::$_rand = MCRYPT_DEV_URANDOM;
				}
				elseif (defined('MCRYPT_DEV_RANDOM'))
				{
					// Use /dev/random
					Encrypt::$_rand = MCRYPT_DEV_RANDOM;
				}
				else
				{
					// Use the system random number generator
					Encrypt::$_rand = MCRYPT_RAND;
				}
			}
		}

		if (Encrypt::$_rand === MCRYPT_RAND)
		{
			// The system random number generator must always be seeded each
			// time it is used, or it will not produce true random results
			mt_srand();
		}

		// Create a random initialization vector of the proper size for the current cipher
		$iv = mcrypt_create_iv($this->_iv_size, Encrypt::$_rand);

		// Encrypt the data using the configured options and generated iv
		$data = mcrypt_encrypt($this->_cipher, $this->_key, $data, $this->_mode, $iv);

		// Use base64 encoding to convert to a string
		return base64_encode($iv.$data);
	}

	/**
	 * Decrypts an encoded string back to its original value.
	 *
	 * @param   string  encoded string to be decrypted
	 * @return  string  decrypted data
	 */
	public function decode($data)
	{
		// Convert the data back to binary
		$data = base64_decode($data);

		// Extract the initialization vector from the data
		$iv = substr($data, 0, $this->_iv_size);

		// Remove the iv from the data
		$data = substr($data, $this->_iv_size);

		// Return the decrypted data, trimming the \0 padding bytes from the end of the data
		return rtrim(mcrypt_decrypt($this->_cipher, $this->_key, $data, $this->_mode, $iv), "\0");
	}

} // End Encrypt
