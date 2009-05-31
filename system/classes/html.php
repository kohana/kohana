<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * HTML helper class.
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class html_Core {

	/**
	 * @var  array  preferred order of attributes
	 */
	public static $attribute_order = array
	(
		'action',
		'method',
		'type',
		'id',
		'name',
		'value',
		'href',
		'src',
		'width',
		'height',
		'cols',
		'rows',
		'size',
		'maxlength',
		'rel',
		'media',
		'accept-charset',
		'accept',
		'tabindex',
		'accesskey',
		'alt',
		'title',
		'class',
		'style',
		'selected',
		'checked',
		'readonly',
		'disabled',
	);

	/**
	 * @var  boolean  automatically target external URLs to a new window
	 */
	public static $windowed_urls = FALSE;

	/**
	 * Create HTML link anchors.
	 *
	 * @param   string  URL or URI string
	 * @param   string  link text
	 * @param   array   HTML anchor attributes
	 * @param   string  use a specific protocol
	 * @return  string
	 */
	public static function anchor($uri, $title = NULL, array $attributes = NULL, $protocol = NULL)
	{
		if ($title === NULL)
		{
			// Use the URI as the title
			$title = htmlspecialchars($uri, ENT_NOQUOTES, Kohana::$charset, TRUE);
		}

		if ($uri === '')
		{
			// Only use the base URL
			$uri = url::base(FALSE, $protocol);
		}
		else
		{
			if (strpos($uri, '://') !== FALSE)
			{
				if (html::$windowed_urls === TRUE AND empty($attributes['target']))
				{
					// Make the link open in a new window
					$attributes['target'] = '_blank';
				}
			}
			elseif ($uri[0] !== '#')
			{
				// Make the URI absolute for non-id anchors
				$uri = url::site($uri, $protocol);
			}
		}

		// Add the sanitized link to the attributes
		$attributes['href'] = $uri;

		return '<a'.html::attributes($attributes).'>'.$title.'</a>';
	}

	/**
	 * Creates an HTML anchor to a file.
	 *
	 * @param   string  name of file to link to
	 * @param   string  link text
	 * @param   array   HTML anchor attributes
	 * @param   string  non-default protocol, eg: ftp
	 * @return  string
	 */
	public static function file_anchor($file, $title = NULL, array $attributes = NULL, $protocol = NULL)
	{
		if ($title === NULL)
		{
			// Use the file name as the title
			$title = htmlspecialchars($file, ENT_NOQUOTES, Kohana::$charset, TRUE);
		}

		// Add the file link to the attributes
		$attributes['href'] = url::base(FALSE, $protocol).$file;

		return '<a'.html::attributes($attributes).'">'.$title.'</a>';

	}

	/**
	 * Generates an obfuscated version of an email address.
	 *
	 * @param   string  email address
	 * @return  string
	 */
	public static function email($email)
	{
		$safe = '';
		foreach (str_split($email) as $letter)
		{
			switch (rand(1, 3))
			{
				// HTML entity code
				case 1: $safe .= '&#'.ord($letter).';'; break;
				// Hex character code
				case 2: $safe .= '&#x'.dechex(ord($letter)).';'; break;
				// Raw (no) encoding
				case 3: $safe .= $letter;
			}
		}

		return $safe;
	}

	/**
	 * Creates an email anchor.
	 *
	 * @param   string  email address to send to
	 * @param   string  link text
	 * @param   array   HTML anchor attributes
	 * @return  string
	 */
	public static function mailto($email, $title = NULL, array $attributes = NULL)
	{
		// Obfuscate email address
		$email = html::email($email);

		if ($title === NULL)
		{
			// Use the email address as the title
			$title = htmlspecialchars($email, ENT_NOQUOTES, Kohana::$charset, FALSE);
		}

		return '<a href="&#109;&#097;&#105;&#108;&#116;&#111;&#058;'.$email.'"'.html::attributes($attributes).'>'.$title.'</a>';
	}

	/**
	 * Creates a style sheet link.
	 *
	 * @param   string  file name
	 * @param   array   default attributes
	 * @return  string
	 */
	public static function style($file, array $attributes = NULL)
	{
		if (strpos($file, '://') === FALSE)
		{
			// Add the base URL
			$file = url::base(FALSE).$file;
		}

		// Set the stylesheet link
		$attributes['href'] = $file;

		// Set the stylesheet rel
		$attributes['rel'] = 'stylesheet';

		// Set the stylesheet type
		$attributes['type'] = 'text/css';

		return '<link'.html::attributes($attributes).' />';
	}

	/**
	 * Creates a script link.
	 *
	 * @param   string   file name
	 * @param   array    default attributes
	 * @return  string
	 */
	public static function script($file, array $attributes = NULL)
	{
		if (strpos($file, '://') === FALSE)
		{
			// Add the base URL
			$file = url::base(FALSE).$file;
		}

		// Set the script link
		$attributes['src'] = $file;

		// Set the script type
		$attributes['type'] = 'text/javascript';

		return '<script'.html::attributes($attributes).'></script>';
	}

	/**
	 * Creates a image link.
	 *
	 * @param   string   file name
	 * @param   array    default attributes
	 * @return  string
	 */
	public static function image($file, array $attributes = NULL)
	{
		if (strpos($file, '://') === FALSE)
		{
			// Add the base URL
			$file = url::base(FALSE).$file;
		}

		// Add the image link
		$attributes['src'] = $file;

		return '<img'.html::attributes($attributes).' />';
	}

	/**
	 * Compiles an array of HTML attributes into an attribute string.
	 *
	 * @param   array   attribute list
	 * @return  string
	 */
	public static function attributes(array $attributes = NULL)
	{
		if (empty($attributes))
			return '';

		$sorted = array();
		foreach (html::$attribute_order as $key)
		{
			if (isset($attributes[$key]))
			{
				// Add the attribute to the sorted list
				$sorted[$key] = $attributes[$key];
			}
		}

		// Combine the sorted attributes
		$attributes = $sorted + $attributes;

		$compiled = '';
		foreach ($attributes as $key => $value)
		{
			if ($value === NULL)
			{
				// Skip attributes that have NULL values
				continue;
			}

			// Add the attribute value
			$compiled .= ' '.$key.'="'.htmlspecialchars($value, ENT_QUOTES, Kohana::$charset, TRUE).'"';
		}

		return $compiled;
	}

} // End html
