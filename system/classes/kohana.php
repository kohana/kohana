<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Contains the most low-level helpers methods in Kohana:
 *
 * - Environment initialization
 * - Locating files within the cascading filesystem
 * - Auto-loading and transparent extension of classes
 * - Variable and path debugging
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Kohana {

	// Release version and codename
	const VERSION  = '3.0';
	const CODENAME = 'renaissance';

	// Log message types
	const ERROR = 'ERROR';
	const DEBUG = 'DEBUG';
	const INFO  = 'INFO';

	// Security check that is added to all generated PHP files
	const FILE_SECURITY = '<?php defined(\'SYSPATH\') or die(\'No direct script access.\');';

	// Format of cache files: header, cache name, and data
	const FILE_CACHE = ":header \n\n// :name\n\n:data\n";

	/**
	 * @var  string  current environment name
	 */
	public static $environment = 'development';

	/**
	 * @var  boolean  command line environment?
	 */
	public static $is_cli = FALSE;

	/**
	 * @var  boolean  Windows environment?
	 */
	public static $is_windows = FALSE;

	/**
	 * @var  boolean  magic quotes enabled?
	 */
	public static $magic_quotes = FALSE;

	/**
	 * @var  boolean  log errors and exceptions?
	 */
	public static $log_errors = FALSE;

	/**
	 * @var  string  character set of input and output
	 */
	public static $charset = 'utf-8';

	/**
	 * @var  string  base URL to the application
	 */
	public static $base_url = '/';

	/**
	 * @var  string  application index file
	 */
	public static $index_file = 'index.php';

	/**
	 * @var  boolean  enabling internal caching?
	 */
	public static $caching = FALSE;

	/**
	 * @var  boolean  enable core profiling?
	 */
	public static $profiling = TRUE;

	/**
	 * @var  object  logging object
	 */
	public static $log;

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
	 * > boolean "caching"        : cache the location of files between requests
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

		if (isset($settings['profile']))
		{
			// Enable profiling
			self::$profiling = (bool) $settings['profile'];
		}

		if (self::$profiling === TRUE)
		{
			// Start a new benchmark
			$benchmark = Profiler::start(__CLASS__, __FUNCTION__);
		}

		// The system will now be initialized
		$_init = TRUE;

		// Start an output buffer
		ob_start();

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
			$global_variables = array_diff($global_variables,
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

		if (isset($settings['caching']))
		{
			// Enable or disable internal caching
			self::$caching = (bool) $settings['caching'];
		}

		if (isset($settings['charset']))
		{
			// Set the system character set
			self::$charset = strtolower($settings['charset']);
		}

		if (isset($settings['base_url']))
		{
			// Set the base URL
			self::$base_url = rtrim($settings['base_url'], '/').'/';
		}

		if (isset($settings['index_file']))
		{
			// Set the index file
			self::$index_file = trim($settings['index_file'], '/');
		}

		// Determine if the extremely evil magic quotes are enabled
		self::$magic_quotes = (bool) get_magic_quotes_gpc();

		// Sanitize all request variables
		$_GET    = self::sanitize($_GET);
		$_POST   = self::sanitize($_POST);
		$_COOKIE = self::sanitize($_COOKIE);

		// Load the logger
		self::$log = Kohana_Log::instance();

		if (isset($benchmark))
		{
			// Stop benchmarking
			Profiler::stop($benchmark);
		}
	}

	/**
	 * Recursively sanitizes an input variable:
	 *
	 * - Strips slashes if magic quotes are enabled
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

			// Class has been found
			return TRUE;
		}

		// Class is not in the filesystem
		return FALSE;
	}

	/**
	 * Changes the currently enabled modules. Module paths may be relative
	 * or absolute, but must point to a directory:
	 *
	 *     Kohana::modules(array('modules/foo', MODPATH.'bar'));
	 *
	 * @param   array  list of module paths
	 * @return  array  enabled modules
	 */
	public static function modules(array $modules = NULL)
	{
		if ($modules === NULL)
			return self::$_modules;

		if (self::$profiling === TRUE)
		{
			// Start a new benchmark
			$benchmark = Profiler::start(__CLASS__, __FUNCTION__);
		}

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

		if (isset($benchmark))
		{
			// Stop the benchmark
			Profiler::stop($benchmark);
		}

		// Set the new include paths
		self::$_paths = $paths;

		// Set the current module list
		return self::$_modules = $modules;
	}

	/**
	 * Finds the path of a file by directory, filename, and extension.
	 * If no extension is given, the default EXT extension will be used.
	 *
	 * When searching the "config" or "i18n" directory, an array of files
	 * will be returned. These files will return arrays which must be
	 * merged together.
	 *
	 *     // Returns an absolute path to views/template.php
	 *     Kohana::find_file('views', 'template');
	 *
	 *     // Returns an absolute path to media/css/style.css
	 *     Kohana::find_file('media', 'css/style', 'css');
	 *
	 *     // Returns an array of all the "mimes" configuration file
	 *     Kohana::find_file('config', 'mimes');
	 *
	 * @param   string   directory name (views, i18n, classes, extensions, etc.)
	 * @param   string   filename with subdirectory
	 * @param   string   extension to search for
	 * @return  array    file list from the "config" or "i18n" directories
	 * @return  string   single file path
	 */
	public static function find_file($dir, $file, $ext = NULL)
	{
		if (self::$profiling === TRUE AND class_exists('Profiler', FALSE))
		{
			// Start a new benchmark
			$benchmark = Profiler::start(__CLASS__, __FUNCTION__);
		}

		// Use the defined extension by default
		$ext = ($ext === NULL) ? EXT : '.'.$ext;

		// Create a partial path of the filename
		$path = $dir.'/'.$file.$ext;

		if (self::$caching === TRUE)
		{
			// Set the cache key for this path
			$cache_key = 'Kohana::find_file("'.$path.'")';

			if (($found = self::cache($cache_key)) !== NULL)
			{
				// Return the cached path
				return $found;
			}
		}

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

		if (isset($cache_key))
		{
			// Save the path cache
			Kohana::cache($cache_key, $found);
		}

		if (isset($benchmark))
		{
			// Stop the benchmark
			Profiler::stop($benchmark);
		}

		return $found;
	}

	/**
	 * Recursively finds all of the files in the specified directory.
	 *
	 *     $views = Kohana::list_files('views');
	 *
	 * @param   string  directory name
	 * @return  array
	 */
	public static function list_files($directory = NULL)
	{
		if ($directory !== NULL)
		{
			// Add the directory separator
			$directory .= DIRECTORY_SEPARATOR;
		}

		// Create an array for the files
		$found = array();

		foreach (self::$_paths as $path)
		{
			if (is_dir($path.$directory))
			{
				// Create a new directory iterator
				$dir = new DirectoryIterator($path.$directory);

				foreach ($dir as $file)
				{
					// Get the file name
					$filename = $file->getFilename();

					if ($filename[0] === '.')
					{
						// Skip all hidden files
						continue;
					}

					// Relative filename is the array key
					$key = $directory.$filename;

					if ($file->isDir())
					{
						if ($sub_dir = self::list_files($key))
						{
							if (isset($found[$key]))
							{
								// Append the sub-directory list
								$found[$key] += $sub_dir;
							}
							else
							{
								// Create a new sub-directory list
								$found[$key] = $sub_dir;
							}
						}
					}
					else
					{
						if ( ! isset($found[$key]))
						{
							// Add new files to the list
							$found[$key] = realpath($file->getPathName());
						}
					}
				}
			}
		}

		// Sort the results alphabetically
		ksort($found);

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
	public static function load($file)
	{
		return include $file;
	}

	/**
	 * Creates a new configuration object for the requested group.
	 *
	 * @param   string   group name
	 * @param   boolean  enable caching
	 */
	public static function config($group, $cache = TRUE)
	{
		return new Kohana_Config($group, $cache);
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
	public static function cache($name, $data = NULL, $lifetime = 60)
	{
		// Cache file is a hash of the name
		$file = sha1($name).EXT;

		// Cache directories are split by keys to prevent filesystem overload
		$dir = APPPATH."cache/{$file[0]}{$file[1]}/";

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
			try
			{
				// Create the cache directory
				mkdir($dir, 0777, TRUE);

				// Set permissions (must be manually set to fix umask issues)
				chmod($dir, 0777);
			}
			catch (Exception $e)
			{
				throw new Kohana_Exception('Directory :dir must be writable',
					array(':dir' => Kohana::debug_path(APPPATH.'cache')));
			}
		}

		if ( ! is_file($dir.$file))
		{
			// Create the file
			touch($dir.$file);

			// Make the file world writable
			chmod($dir.$file, 0666);
		}

		// Write the cache
		return (bool) file_put_contents($dir.$file, strtr(self::FILE_CACHE, array
		(
			':header' => self::FILE_SECURITY,
			':name'   => $name,
			':data'   => 'return '.var_export($data, TRUE).';',
		)));
	}

	/**
	 * PHP error handler, converts all errors into ErrorExceptions. This handler
	 * respects error_reporting settings.
	 *
	 * @throws  ErrorException
	 * @return  TRUE
	 */
	public static function error_handler($code, $error, $file = NULL, $line = NULL)
	{
		if ((error_reporting() & $code) !== 0)
		{
			// This error is not suppressed by current error reporting settings
			throw new Kohana_Error($error, $code, 0, $file, $line);
		}

		// Do not execute the PHP error handler
		return TRUE;
	}

	/**
	 * Inline exception handler, displays the error message, source of the
	 * exception, and the stack trace of the error.
	 *
	 * @param   object   exception object
	 * @return  boolean
	 */
	public static function exception_handler(Exception $e)
	{
		try
		{
			// Get the exception information
			$type    = get_class($e);
			$code    = $e->getCode();
			$message = $e->getMessage();
			$file    = $e->getFile();
			$line    = $e->getLine();

			// Set the text version of the exception
			$text = "{$type} [ {$code} ]: {$message} ".self::debug_path($file)." [ {$line} ]";

			// Add this exception to the log
			self::$log->add(Kohana::ERROR, $text);

			if (Kohana::$is_cli)
			{
				// Just display the text of the exception
				echo "\n{$text}\n";

				return TRUE;
			}

			// Get the exception backtrace
			$trace = $e->getTrace();

			if ($e instanceof ErrorException AND version_compare(PHP_VERSION, '5.3', '<'))
			{
				// Workaround for a bug in ErrorException::getTrace() that exists in
				// all PHP 5.2 versions. @see http://bugs.php.net/bug.php?id=45895
				for ($i = count($trace) - 1; $i > 0; --$i)
				{
					if (isset($trace[$i - 1]['args']))
					{
						// Re-position the args
						$trace[$i]['args'] = $trace[$i - 1]['args'];

						// Remove the args
						unset($trace[$i - 1]['args']);
					}
				}
			}

			// Get the source of the error
			$source = self::debug_source($file, $line);

			// Generate a new error id
			$error_id = uniqid();

			// Start an output buffer
			ob_start();

			// Include the exception HTML
			include self::find_file('views', 'kohana/error');

			// Display the contents of the output buffer
			echo ob_get_clean();

			return TRUE;
		}
		catch (Exception $e)
		{
			// Clean the output buffer if one exists
			ob_get_level() and ob_clean();

			// This can happen when the kohana error view has a PHP error
			echo $e->getMessage(), ' [ ', Kohana::debug_path($e->getFile()), ', ', $e->getLine(), ' ]';

			// Exit with an error status
			exit(1);
		}
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
				case 'null':
					$var = 'NULL';
				break;
				case 'boolean':
					$var = $var ? 'TRUE' : 'FALSE';
				break;
				default:
					$var = htmlspecialchars(print_r($var, TRUE), NULL, self::$charset);
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
	 * Returns a single line representation of a variable. Internally, this is
	 * used only for showing function arguments in stack traces.
	 *
	 *     echo Kohana::debug_var($my_var);
	 *
	 * @param   mixed  variable to debug
	 * @return  string
	 */
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
				return var_export($var, TRUE);
			break;
			case 'object':
				return 'object '.get_class($var);
			break;
			case 'array':
				if (Arr::is_assoc($var))
					return print_r($var, TRUE);

				return 'array('.implode(', ', array_map(array(__CLASS__, __FUNCTION__), $var)).')';
			break;
			default:
				return var_export($var, TRUE);
			break;
		}
	}

	/**
	 * Returns an array of HTML strings that represent each step in the backtrace.
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
		foreach ($trace as $step)
		{
			if ( ! (isset($step['function']) AND isset($step['file'])))
			{
				// Ignore this line, it has unusable data
				continue;
			}

			// Set the function name
			$function = $step['function'];

			// Set the file and line
			$file = self::debug_path($step['file']);
			$line = $step['line'];

			if (isset($step['class']))
			{
				// Change the function to a method
				$function = $step['class'].$step['type'].$function;
			}

			if (isset($step['args']))
			{
				if (in_array($function, $statements))
				{
					// Sanitize the path name
					$function .= ' '.self::debug_path($step['args'][0]);
				}
				else
				{
					// Sanitize the function arguments
					$function .= '('.implode(', ', $args = array_map(array('Kohana', 'debug_var'), $step['args'])).')';
				}
			}

			$output[] = array(
				'function' => $function,
				'file'     => self::debug_path($step['file']),
				'line'     => $step['line']);
		}

		return $output;
	}

	private function __construct()
	{
		// This is a static class
	}

} // End Kohana
