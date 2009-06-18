<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Internationalization (i18n) class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_i18n {

	// The default language of all messages
	public static $default_lang = 'en-US';

	// The current language
	public static $lang = 'en-US';

	// Cache of loaded languages
	protected static $_cache = array();

	/**
	 * Returns translation of a string. If no translation exists, the original
	 * string will be returned.
	 *
	 * @param   string   text to translate
	 * @return  string
	 */
	public static function get($string)
	{
		// Load the translation table
		$table = i18n::load(i18n::$lang);

		// Return the translated string if it exists
		return isset($table[$string]) ? $table[$string] : $string;
	}

	/**
	 * Loads the translation table for a given language.
	 * 
	 * @param   string   language to load
	 * @return  array
	 */
	protected static function load($lang)
	{
		if ( ! isset(i18n::$_cache[$lang]))
		{
			// Separate the language and locale
			list ($language, $locale) = explode('-', strtolower($lang), 2);

			// Start a new translation table
			$table = array();

			if ($files = Kohana::find_file('i18n', $language))
			{
				foreach ($files as $file)
				{
					// Load the strings that are in this file
					$strings = Kohana::load($file);

					// Merge the language strings into the translation table
					$table = array_merge($table, $strings);
				}
			}

			if ($files = Kohana::find_file('i18n', $language.'/'.$locale))
			{
				foreach ($files as $file)
				{
					// Load the strings that are in this file
					$strings = Kohana::load($file);

					// Merge the locale strings into the translation table
					$table = array_merge($table, $strings);
				}
			}

			// Cache the translation table locally
			i18n::$_cache[$lang] = $table;
		}

		return i18n::$_cache[$lang];
	}

	final private function __construct()
	{
		// This is a static class
	}

} // End i18n
