<?php
/**
 * Contains the most low-level helpers methods in Kohana:
 *
 * - Environment initialization
 * - Locating files within the cascading filesystem
 * - Auto-loading and transparent extension of classes
 * - Variable and path debugging
 *
 * $Id: kohana.php 3733 2008-11-27 01:12:41Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Kohana {

	// Command line instance
	public static $cli_mode = FALSE;

	// Client request method
	public static $request_method = 'GET';

	// Default charset for all requests
	public static $charset = 'UTF-8';

	// Has the environment been initialized?
	private static $init = FALSE;

	// Include paths that are used to find files
	private static $include_paths = array(APPPATH, SYSPATH);

	// Cache for resource location
	private static $file_path;

	/**
	 * Initializes the environment:
	 *
	 * - Enables the auto-loader
	 * - Enables the exception handler
	 * - Enables error-to-exception handler
	 *
	 * @return  void
	 */
	public static function init()
	{
		if (self::$init === TRUE)
			return;

		// Enable auto-loading of classes
		spl_autoload_register(array(__CLASS__, 'auto_load'));

		// Load the file path cache
		self::$file_path = Kohana::cache('kohana_file_paths');

		if (PHP_SAPI === 'cli')
		{
			// The current instance is being run via the command line
			self::$cli_mode = TRUE;
		}
		else
		{
			if (isset($_SERVER['REQUEST_METHOD']))
			{
				// Let the server determine the request method
				self::$request_method = strtoupper($_SERVER['REQUEST_METHOD']);
			}
		}

		/*
		if ($hooks = self::find_file('hooks'))
		{
			foreach ($hooks as $hook)
			{
				// Load each hook in the order they appear
				require $hook;
			}
		}
		*/


		// The system has been initialized
		self::$init = TRUE;
	}

	/**
	 * The last method before Kohana stops processing the request:
	 *
	 * - Saves the file path cache
	 *
	 * @return  void
	 */
	public function shutdown()
	{
		Kohana::cache('kohana_file_paths', self::$file_path);
	}

	/**
	 * Finds the path of a file by directory, filename, and extension.
	 * If no extension is give, the default EXT extension will be used.
	 *
	 *     // Returns an absolute path to views/template.php
	 *     echo Kohana::find_file('views', 'template');
	 *
	 *     // Returns an absolute path to media/css/style.css
	 *     echo Kohana::find_file('media', 'css/style', 'css');
	 *
	 * @param   string   directory name (views, classes, extensions, etc.)
	 * @param   string   filename with subdirectory
	 * @param   string   extension to search for
	 * @return  string   success
	 * @return  FALSE    failure
	 */
	public static function find_file($dir, $file, $ext = NULL)
	{
		// Use the defined extension by default
		$ext = ($ext === NULL) ? EXT : '.'.$ext;

		// Create a partial path of the filename
		$file = $dir.'/'.$file.$ext;

		if (isset(self::$file_path[$file]))
		{
			// The path to this file has already been found
			return self::$file_path[$file];
		}

		foreach (self::$include_paths as $path)
		{
			if (file_exists($path.$file))
			{
				// Cache and return the path to this file
				return self::$file_path[$file] = $path.$file;
			}
		}

		return FALSE;
	}

	/**
	 * Loads a file within a totally empty scope and returns the output:
	 *
	 *     $foo = Kohana::load_file('foo.php');
	 *
	 * @param   string
	 * @return  mixed
	 */
	public function load_file($file)
	{
		// Return the output of the file
		return include $file;
	}

	/**
	 * Provides auto-loading support of Kohana classes, as well as transparent
	 * extension of classes that have a _Core suffix.
	 *
	 * Class names are converted to file names by making the class name
	 * lowercase and converting underscores to slashes:
	 *
	 *     // Loads classes/my/class/name.php
	 *     Kohana::auto_load('My_Class_Name');
	 *
	 * @param   string   class name
	 * @param   string   file extensions to use
	 * @return  boolean
	 */
	public static function auto_load($class)
	{
		// Transform the class name into a path
		$file = str_replace('_', '/', strtolower($class));

		if ($path = self::find_file('classes', $file))
		{
			// Load the class file
			require $path;
		}
		else
		{
			return FALSE;
		}

		if ($path = self::find_file('extensions', $file))
		{
			// Load the extension file
			require $path;
		}
		elseif (class_exists($class.'_Core', FALSE))
		{
			// Class extension to be evaluated
			$extension = 'class '.$class.' extends '.$class.'_Core { }';

			// Use reflection to find out of the class is abstract
			$class = new ReflectionClass($class.'_Core');

			if ($class->isAbstract())
			{
				// Make the extension abstract, too
				$extension = 'abstract '.$extension;
			}

			// Transparent class extensions are possible using eval. Not very
			// clean, but it can be avoided by creating empty extension files.
			eval($extension);
		}

		return TRUE;
	}

	/**
	 * Provides simple file-based caching. All caches are serialized and
	 * stored as a hash.
	 *
	 *     // Set the "foo" cache
	 *     Kohana::cache('foo', 'hello, world');
	 *
	 *     // Get the "foo" cache
	 *     $foo = Kohana::cache('foo');
	 *
	 * @param   string   name of the cache
	 * @param   mixed    data to cache
	 * @param   integer  number of seconds the cache is valid for
	 * @return  mixed    for getting
	 * @return  boolean  for setting
	 */
	public function cache($name, $data = NULL, $lifetime = 60)
	{
		// Cache file is a hash of the name
		$file = sha1($name);

		// Cache directories are split by keys
		$dir = APPPATH.'cache/'.$file[0].'/';

		if ($data === NULL)
		{
			if (is_file($dir.$file))
			{
				if ((time() - filemtime($dir.$file)) < $lifetime)
				{
					// Return the cache
					return unserialize(file_get_contents($dir.$file));
				}
				else
				{
					// Cache has expired
					unlink($dir.$file);
				}
			}

			// Cache not found
			return NULL;
		}

		if ( ! is_dir($dir))
		{
			// Create the cache directory
			mkdir($dir, 0777);
		}

		// Serialize the data and create the cache
		return (bool) file_put_contents($dir.$file, serialize($data));
	}

	/**
	 * Returns an HTML string of debugging information about any number of
	 * variables, each wrapped in a <pre> tag:
	 *
	 *     // Displays the type and value of each variable
	 *     echo Kohana::debug($foo, $bar, $baz);
	 *
	 * @param   mixed   variable to debug
	 * @param   ...
	 * @return  string
	 */
	public static function debug()
	{
		if (func_num_args() === 0)
			return;

		// Get all passed variables
		$variables = func_get_args();

		$output = array();
		foreach ($variables as $var)
		{
			$output[] = '<pre>('.gettype($var).') '.htmlspecialchars(print_r($var, TRUE), ENT_QUOTES, self::$charset, TRUE).'</pre>';
		}

		return implode("\n", $output);
	}

	/**
	 * Removes application, system, modpath, or docroot from a filename,
	 * replacing them with the plain text equivalents. Useful for debugging
	 * when you want to display a shorter path.
	 *
	 *     // Displays SYSPATH/classes/kohana.php
	 *     echo Kohana::debug_path(Kohana::find_file('classes', 'kohana'));
	 *
	 * @param   string  path to debug
	 * @return  string
	 */
	public static function debug_path($file)
	{
		if (strpos($file, APPPATH) === 0)
		{
			$file = 'APPPATH/'.substr($file, strlen(APPPATH));
		}
		elseif (strpos($file, SYSPATH) === 0)
		{
			$file = 'SYSPATH/'.substr($file, strlen(SYSPATH));
		}
		elseif (strpos($file, MODPATH) === 0)
		{
			$file = 'MODPATH/'.substr($file, strlen(MODPATH));
		}
		elseif (strpos($file, DOCROOT) === 0)
		{
			$file = 'DOCROOT/'.substr($file, strlen(DOCROOT));
		}

		return $file;
	}

} // End Kohana
