<?php
/**
 * Provides remote server communications options using [curl][ref-curl].
 *
 * [ref-curl]: http://php.net/curl
 *
 * @package    Core
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
	 * Returns the output of a remote URL. Output will be returned as an
	 * array with a boolean "status" and a string "response":
	 * 
	 *     $page = remote::get('http://www.google.com');
	 *     
	 *     echo $page['status'] ? $page['response'] : 'Error: '.$page['response'];
	 * 
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

		if (($response = curl_exec($remote)) === FALSE)
		{
			// An error has occurred
			$status = FALSE;

			// Return the error message instead of the response
			$response = curl_error($remote);
		}
		else
		{
			// The response is valid
			$status = TRUE;
		}

		// Close the connection
		curl_close($remote);

		return array('status' => $status, 'response' => $response);
	}

} // End remote
