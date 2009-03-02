<?php defined('SYSPATH') OR die('No direct access allowed.');

class arr_core {

	public static function is_assoc(array $array)
	{
		// Keys of the array
		$keys = array_keys($array);

		// If the array keys of the keys match the keys, then the array must
		// be associative.
		return array_keys($keys) !== $keys;
	}

	public static function get(array $array, $key, $default = NULL)
	{
		return array_key_exists($key, $array) ? $array[$key] : $default;
	}

	public static function extract(array $array, $key)
	{
		$keys  = func_get_args();
		$array = array_shift($keys);

		$found = array();
		foreach ($keys as $key)
		{
			$found[$key] = isset($array[$key]) ? $array[$key] : NULL;
		}

		return $found;
	}

	public static function merge(array $a1, array $a2)
	{
		
	}

} // End arr
