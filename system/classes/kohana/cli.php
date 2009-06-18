<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Helper functions for working in a command-line environment.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_CLI {

	/**
	 * Returns one or more command-line options. Options are specified using
	 * standard CLI syntax:
	 *
	 *     php index.php --option "value"
	 *
	 * @param   string  option name
	 * @param   ...
	 * @return  array
	 */
	public static function options($options)
	{
		if ( ! Kohana::$is_cli)
		{
			// Not in command-line mode
			return FALSE;
		}

		// Get all of the requested options
		$options = func_get_args();

		// Found option values
		$values = array();

		for ($i = 1, $max = $_SERVER['argc']; $i < $max; $i++)
		{
			if ( ! isset($_SERVER['argv'][$i]))
			{
				// No more args left
				break;
			}

			// Get the option
			$opt = $_SERVER['argv'][$i];

			if ( ! (isset($opt[0]) AND isset($opt[1]) AND $opt[0] === '-' AND $opt[0] === $opt[1]))
			{
				// This is not an option
				continue;
			}

			// Remove the "--" prefix
			$opt = substr($opt, 2);

			if (in_array($opt, $options) AND isset($_SERVER['argv'][$i + 1]))
			{
				// Set the given value
				$values[$opt] = $_SERVER['argv'][$i + 1];
			}
		}

		return $values;
	}

} // End CLI
