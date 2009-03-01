<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Contains the most low-level helpers methods in Kohana:
 *
 * - Environment initialization
 * - Locating files within the cascading filesystem
 * - Auto-loading and transparent extension of classes
 * - Variable and path debugging
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Kohana {

	const VERSION   = '3.0';
	const CODENAME  = 'renaissance';

	// Security check that is added to all generated PHP files
	const PHP_HEADER = "<?php defined('SYSPATH') or die('No direct script access.');\n\n";

	/**
	 * @var  boolean  command line environment?
	 */
	public static $is_cli = FALSE;

	/**
	 * @var  boolean   Windows environment?
	 */
	public static $is_windows = FALSE;

	/**
	 * @var  boolean  magic quotes enabled?
	 */
	public static $magic_quotes = FALSE;

	/**
	 * @var  boolean  cache the location of files across requests?
	 */
	public static $cache_paths = FALSE;

	/**
	 * @var  boolean  display errors and exceptions in output?
	 */
	public static $display_errors = TRUE;

	/**
	 * @var  boolean  log errors and exceptions?
	 */
	public static $log_errors = TRUE;

	/**
	 * @var  string  character set of input and output
	 */
	public static $charset = 'utf-8';

	// Currently active modules
	private static $_modules = array();

	// Include paths that are used to find files
	private static $_paths = array(APPPATH, SYSPATH);

	/**
	 * Initializes the environment:
	 *
	 * - Disables register_globals and magic_quotes_gpc
	 * - Determines the current environment
	 * - Set global settings
	 * - Sanitizes GET, POST, and COOKIE variables
	 * - Converts GET, POST, and COOKIE variables to the global character set
	 *
	 * Any of the global settings can be set here:
	 *
	 * > boolean "display_errors" : display errors and exceptions
	 * > boolean "log_errors"     : log errors and exceptions
	 * > boolean "cache_paths"    : cache the location of files between requests
	 * > string  "charset"        : character set used for all input and output
	 *
	 * @param   array   global settings
	 * @return  void
	 */
	public static function init(array $settings = NULL)
	{
		static $_init;

		// This function can only be run once
		if ($_init === TRUE) return;

		// The system will now be initialized
		$_init = TRUE;

		if (version_compare(PHP_VERSION, '6.0', '<='))
		{
			// Disable magic quotes at runtime
			set_magic_quotes_runtime(0);
		}

		if (ini_get('register_globals'))
		{
			if (isset($_REQUEST['GLOBALS']))
			{
				// Prevent malicious GLOBALS overload attack
				echo "Global variable overload attack detected! Request aborted.\n";

				// Exit with an error status
				exit(1);
			}

			// Get the variable names of all globals
			$global_variables = array_keys($GLOBALS);

			// Remove the standard global variables from the list
			$global_variables = array_diff($global_vars,
				array('GLOBALS', '_REQUEST', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER', '_ENV', '_SESSION'));

			foreach ($global_variables as $name)
			{
				// Retrieve the global variable and make it null
				global $$name;
				$$name = NULL;

				// Unset the global variable, effectively disabling register_globals
				unset($GLOBALS[$name], $$name);
			}
		}

		// Determine if we are running in a command line environment
		self::$is_cli = (PHP_SAPI === 'cli');

		// Determine if we are running in a Windows environment
		self::$is_windows = (DIRECTORY_SEPARATOR === '\\');

		// Determine if this server supports UTF-8 natively
		utf8::$server_utf8 = extension_loaded('mbstring');

		if (isset($settings['display_errors']))
		{
			// Enable or disable the display of errors
			self::$display_errors = (bool) $settings['display_errors'];
		}

		if (isset($settings['cache_paths']))
		{
			// Enable or disable the caching of paths
			self::$cache_paths = (bool) $settings['cache_paths'];
		}

		if (isset($settings['charset']))
		{
			// Set the system character set
			self::$charset = strtolower($settings['charset']);
		}

		// Determine if the extremely evil magic quotes are enabled
		self::$magic_quotes = (bool) get_magic_quotes_gpc();

		// Sanitize all request variables
		$_GET    = self::sanitize($_GET);
		$_POST   = self::sanitize($_POST);
		$_COOKIE = self::sanitize($_COOKIE);

		// Normalize all request variables to the current charset
		$_GET    = utf8::clean($_GET, self::$charset);
		$_POST   = utf8::clean($_POST, self::$charset);
		$_COOKIE = utf8::clean($_COOKIE, self::$charset);
	}

	/**
	 * Recursively sanitizes an input variable:
	 *
	 * - Removes slashes if magic quotes are enabled
	 * - Normalizes all newlines to LF
	 *
	 * @param   mixed  any variable
	 * @return  mixed  sanitized variable
	 */
	public static function sanitize($value)
	{
		if (is_array($value) OR is_object($value))
		{
			foreach ($value as $key => $val)
			{
				// Recursively clean each value
				$value[$key] = self::sanitize($val);
			}
		}
		elseif (is_string($value))
		{
			if (self::$magic_quotes === TRUE)
			{
				// Remove slashes added by magic quotes
				$value = stripslashes($value);
			}

			if (strpos($value, "\r") !== FALSE)
			{
				// Standardize newlines
				$value = str_replace(array("\r\n", "\r"), "\n", $value);
			}
		}

		return $value;
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
			if (($extension = Kohana::cache('kohana_auto_extension '.$class)) === NULL)
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

				// Cache the extension string to that Reflection will be avoided
				Kohana::cache('kohana_auto_extension '.$class, $extension);
			}

			// Transparent class extensions are possible using eval. Not very
			// clean, but it can be avoided by creating empty extension files.
			eval($extension);
		}

		return TRUE;
	}

	/**
	 * PHP error handler, converts all errors into ErrorExceptions. This handler
	 * respects error_reporting settings.
	 *
	 * @throws   ErrorException
	 * @return   TRUE
	 */
	public static function error_handler($code, $error, $file = NULL, $line = NULL)
	{
		if ((error_reporting() & $code) !== 0)
		{
			// This error is not suppressed by current error reporting settings
			throw new ErrorException($error, $code, 0, $file, $line);
		}

		// Do not execute the PHP error handler
		return TRUE;
	}

	/**
	 * Changes the currently enabled modules. Module paths may be relative
	 * or absolute, but must point to a directory:
	 * 
	 *     Kohana::modules(array('modules/foo', MODPATH.'bar'));
	 * 
	 * @param   array   module paths
	 * @return  void
	 */
	public static function modules(array $modules = NULL)
	{
		if ($modules === NULL)
			return self::$_modules;

		// Start a new list of include paths, APPPATH first
		$paths = array(APPPATH);

		foreach ($modules as $name => $path)
		{
			if (is_dir($path))
			{
				// Add the module to include paths
				$paths[] = realpath($path).DIRECTORY_SEPARATOR;
			}
			else
			{
				// This module is invalid, remove it
				unset($modules[$name]);
			}
		}

		// Finish the include paths by adding SYSPATH
		$paths[] = SYSPATH;

		// Set the new include paths
		self::$_paths = $paths;

		// Set the current module list
		return self::$_modules = $modules;
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
		$path = $dir.'/'.$file.$ext;

		if ($dir === 'config' OR $dir === 'i18n')
		{
			// Include paths must be searched in reverse
			$paths = array_reverse(self::$_paths);

			// Array of files that have been found
			$found = array();

			foreach ($paths as $dir)
			{
				if (is_file($dir.$path))
				{
					// This path has a file, add it to the list
					$found[] = $dir.$path;
				}
			}
		}
		else
		{
			// The file has not been found yet
			$found = FALSE;

			foreach (self::$_paths as $dir)
			{
				if (is_file($dir.$path))
				{
					// A path has been found
					$found = $dir.$path;

					// Stop searching
					break;
				}
			}
		}

		return $found;
	}

	/**
	 * Loads a file within a totally empty scope and returns the output:
	 *
	 *     $foo = Kohana::load('foo.php');
	 *
	 * @param   string
	 * @return  mixed
	 */
	public function load($file)
	{
		return include $file;
	}

	/**
	 * Provides simple file-based caching for strings and arrays: 
	 *
	 *     // Set the "foo" cache
	 *     Kohana::cache('foo', 'hello, world');
	 *
	 *     // Get the "foo" cache
	 *     $foo = Kohana::cache('foo');
	 *
	 * All caches are stored as PHP code, generated with [var_export][ref-var].
	 * Caching objects may not work as expected. Storing references or an
	 * object or array that has recursion will cause an E_FATAL.
	 * 
	 * [ref-var]: http://php.net/var_export
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
		$file = sha1($name).EXT;

		// Lets keep this short and sweet
		$ds = DIRECTORY_SEPARATOR;

		// Cache directories are split by keys to prevent filesystem overload
		$dir = APPPATH.'cache'.$ds.substr($file, 0, 2).$ds.substr($file, 2, 2).$ds;

		if ($data === NULL)
		{
			if (is_file($dir.$file))
			{
				if ((time() - filemtime($dir.$file)) < $lifetime)
				{
					// Return the cache
					return include $dir.$file;
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
			mkdir($dir, 0777, TRUE);
		}

		// Serialize the data and create the cache
		return (bool) file_put_contents($dir.$file, self::PHP_HEADER.'return '.var_export($data, TRUE).';');
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
			$type = gettype($var);

			switch ($type)
			{
				case 'boolean':
					$var = $var ? 'TRUE' : 'FALSE';
				break;
				default:
					$var = htmlspecialchars(print_r($var, TRUE), NULL, self::$charset, TRUE);
				break;
			}

			$output[] = '<pre>('.$type.') '.$var.'</pre>';
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

	/**
	 * Returns an HTML string, highlighting a specific line of a file, with some
	 * number of lines padded above and below.
	 *
	 *     // Highlights the current line of the current file
	 *     echo Kohana::debug_source(__FILE__, __LINE__);
	 *
	 * @param   string   file to open
	 * @param   integer  line number to highlight
	 * @param   integer  number of padding lines
	 * @return  string
	 */
	public static function debug_source($file, $line_number, $padding = 3)
	{
		// Open the file and set the line position
		$file = fopen($file, 'r');
		$line = 0;

		// Set the reading range
		$range = array('start' => $line_number - $padding, 'end' => $line_number + $padding);

		$source = array();
		while (($row = fgets($file)) !== FALSE)
		{
			// Increment the line number
			if (++$line > $range['end'])
				break;

			if ($line >= $range['start'])
			{
				// Trim whitespace and sanitize the row
				$row = htmlspecialchars(rtrim($row));

				if ($line === $line_number)
				{
					// Apply highlighting to the row
					$row = '<span style="background:#f2df92">'.$row.'</span>';
				}

				// Add to the captured source
				$source[] = $row;
			}
		}

		// Close the file
		fclose($file);

		return implode("\n", $source);
	}

	/**
	 * Returns an array of HTML strings that represent each step in the backtace.
	 *
	 *     // Displays the entire current backtrace
	 *     echo implode('<br/>', Kohana::trace());
	 *
	 * @param   string  path to debug
	 * @return  string
	 */
	public static function trace(array $trace = NULL)
	{
		if ($trace === NULL)
		{
			// Start a new trace
			$trace = debug_backtrace();
		}

		// Non-standard function calls
		$statements = array('include', 'include_once', 'require', 'require_once');

		$output = array();
		foreach ($trace as $line)
		{
			if ( ! isset($line['function']) OR ! isset($line['file']))
			{
				// Ignore this line, it has unusable data
				continue;
			}

			// Start a new trace step
			$step = array('file' => self::debug_path($line['file']), 'line' => '', 'function' => '');

			if (isset($line['line']))
			{
				// Add the file line
				$step['line'] = $line['line'];
			}

			if (in_array($line['function'], $statements))
			{
				if ( ! isset($line['args']))
				{
					// Really bizzare, ignore this line completely
					continue;
				}

				// Sanitize all paths
				$step['args'] = array_map(array(__CLASS__, 'debug_path'), $line['args']);

				// function args
				$step['function'] = $line['function'].' '.implode(', ', $step['args']);
			}
			else
			{
				if (isset($line['args']))
				{
					// Sanitize all arguments
					$step['args'] = implode(', ', array_map(array(__CLASS__, 'debug_var'), $line['args']));
				}
				else
				{
					// No arguments
					$step['args'] = '';
				}

				if (isset($line['class']) AND isset($line['type']))
				{
					// class::function(args) or class->function(args)
					$step['function'] = $line['class'].$line['type'].$line['function'].'('.$step['args'].')';
				}
				else
				{
					// function(args)
					$step['function'] = $line['function'].'('.$step['args'].')';
				}
			}

			// Add this step to the trace output
			$output[] = "<strong>{$step['file']} [ {$step['line']} ]</strong>\n".
				"\t{$step['function']}";
		}

		return $output;
	}

	public static function debug_var($var)
	{
		switch (gettype($var))
		{
			case 'null':
				return 'NULL';
			break;
			case 'boolean':
				return $var ? 'TRUE' : 'FALSE';
			break;
			case 'string':
				return "'{$var}'";
			break;
			case 'object':
				return 'object '.get_class($var);
			break;
			case 'array':
				if (arr::is_assoc($var))
					return str_replace("\n", ' ', var_export($var, TRUE));

				return 'array('.implode(', ', array_map(array(__CLASS__, __FUNCTION__), $var)).')';
			break;
			default:
				return var_export($var, TRUE);
			break;
		}
	}

	final private function __construct()
	{
		// This is a static class
	}

} // End Kohana
