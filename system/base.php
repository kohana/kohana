<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Kohana translation/internationalization function.
 *
 *    __('Welcome back, :user', array(':user' => $username));
 *
 * @param   string  text to translate
 * @param   array   values to replace in the translated text
 * @return  string
 */
function __($string, array $values = NULL)
{
	if (I18n::$lang !== I18n::$default_lang)
	{
		// Get the translation for this string
		$string = I18n::get($string);
	}

	return empty($values) ? $string : strtr($string, $values);
}
