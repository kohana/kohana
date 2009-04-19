<?php
/**
 * Provides remote server communications options using [curl][ref-curl].
 *
 * [ref-curl]: http://php.net/curl
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class remote_Core {

	// Default curl options
	public static $default_options = array
	(
		CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Kohana v3.0 +http://kohanaphp.com/)',
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT        => 5,
	);

	/**
	 * Returns the output of a remote URL.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   remote URL
	 * @param   array    curl options
	 * @return  array
	 */
	public static function get($url, array $options = NULL)
	{
		// Add default options
		$options = array_merge((array) $options, remote::$default_options);

		// The transfer must always be returned
		$options[CURLOPT_RETURNTRANSFER] = TRUE;

		// Open a new remote connection
		$remote = curl_init($url);

		// Set connection options
		curl_setopt_array($remote, $options);

		// Get the response
		$response = curl_exec($remote);

		// Get the response information
		$code = curl_getinfo($remote, CURLINFO_HTTP_CODE);

		if ($response === FALSE OR $code !== 200)
		{
			throw new Kohana_Exception('Error fetching remote :url [ status :code ] :error',
				array(':url' => $url, ':code' => $code, ':error' => curl_error($remote)));
		}

		// Close the connection
		curl_close($remote);

		return $response;
	}

} // End remote
