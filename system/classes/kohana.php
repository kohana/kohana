<?php
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
 * @copyright  (c) 2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Kohana {

	// Has the environment been initialized?
	private static $init = FALSE;

	// Save the cache on shutdown?
	public static $save_cache = FALSE;

	// Command line instance
	public static $cli_mode = FALSE;

	// Client request method
	public static $request_method = 'GET';

	// Default charset for all requests
	public static $charset;

	// Current locale and timezone
	public static $locale;
	public static $timezone;

	// Include paths that are used to find files
	private static $include_paths = array(APPPATH, SYSPATH);

	// Cache for resource location
	private static $file_path;
	private static $file_path_changed = FALSE;

	/**
	 * Initializes the environment:
	 *
	 * - Enables the auto-loader
	 * - Enables the exception handler
	 * - Enables error-to-exception handler
	 * - Determines if the application was started from the command line
	 * - Determines the HTTP request method, if possible
	 * - Sets the environment locale and timezone
	 * - Enables modules
	 * - Loads hooks
	 *
	 * @return  void
	 */
	public static function init()
	{
		if (self::$init === TRUE)
			return;

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

		// Enable auto-loading of classes
		spl_autoload_register(array(__CLASS__, 'auto_load'));

		// Enable the exception handler
		// set_exception_handler(array(__CLASS__, 'exception_handler'));

		// Enable the error-to-exception handler
		set_error_handler(array(__CLASS__, 'error_handler'));

		// Load main configuration
		$config = Kohana::load_file(APPPATH.'config/kohana'.EXT);

		// Toggle cache saving
		self::$save_cache = $config['save_cache'];

		// Localize the application
		self::locale($config['locale']);

		// Localize the timezone
		self::timezone($config['timezone']);

		// Load module paths
		self::modules($config['modules']);

		// Load the file path cache
		self::$file_path = Kohana::cache('kohana_file_paths');

		if ($hooks = self::list_files('hooks', TRUE))
		{
			foreach ($hooks as $hook)
			{
				// Load each hook in the order they appear
				require $hook;
			}
		}

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
		if (self::$save_cache === TRUE)
		{
			if (self::$file_path_changed === TRUE)
			{
				Kohana::cache('kohana_file_paths', self::$file_path);
			}
		}
	}

	/**
	 * Sets the environment locale. The first locale must always be a valid
	 * `xx_XX` locale name to be used for i18n:
	 * 
	 *     Kohana::locale(array('de_DE@euro.UTF-8', 'de_DE.UTF-8', 'german'));
	 * 
	 * When using this method, it is a good idea to provide many variations, as
	 * locale availability on different systems is very unpredictable.
	 * 
	 * @param   array   locale choices
	 * @return  void
	 */
	public static function locale(array $locales)
	{
		if (setlocale(LC_ALL, $locales) !== FALSE)
		{
			// Set the system locale
			self::$locale = substr($locales[0], 0, 5);
		}
	}

	/**
	 * Sets the environment timezone. Any timezone supported by PHP cane be
	 * used here:
	 * 
	 *     Kohana::timezone('Arctic/Longyearbyen');
	 * 
	 * @param   string   timezone name
	 * @return  string
	 */
	public static function timezone($timezone)
	{
		if ($timezone === NULL)
		{
			// Disable notices when using date_default_timezone_get
			$ER = error_reporting(~E_NOTICE);

			$timezone = date_default_timezone_get();

			// Restore error reporting
			error_reporting($ER);
		}

		if (date_default_timezone_set($timezone) === TRUE)
		{
			// Set the system timezone
			self::$timezone = $timezone;
		}
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
	public static function modules(array $modules)
	{
		// Start a new set of include paths, APPPATH first
		$paths = array(APPPATH);

		foreach ($modules as $module)
		{
			if ($module = realpath($module) AND is_dir($module))
			{
				if (KOHANA_IS_WIN)
				{
					// Remove backslashes
					$module = str_replace('\\', '/', $module);
				}

				// Add the module to include paths
				$paths[] = $module.'/';
			}
		}

		// Finish the include paths by adding SYSPATH
		$paths[] = SYSPATH;

		// Set the new include paths
		self::$include_paths = $paths;
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

		if (isset(self::$file_path[$path]))
		{
			// The path to this file has already been found
			return self::$file_path[$path];
		}

		foreach (self::$include_paths as $dir)
		{
			if (file_exists($dir.$path))
			{
				// Cache is about to change
				self::$file_path_changed = TRUE;

				// Cache and return the path to this file
				return self::$file_path[$path] = $dir.$path;
			}
		}

		return FALSE;
	}

	/**
	 * Find all of the files in a directory:
	 * 
	 *     $configs = Kohana::list_files('config');
	 * 
	 * @param   string   directory name
	 * @param   boolean  list files recursively
	 * @return  array
	 */
	public function list_files($directory, $recursive = FALSE)
	{
		if (isset(self::$file_path[$directory.'/*']))
		{
			// The files in this path have already been found
			return self::$file_path[$directory.'/*'];
		}

		// Reverse the paths so that lower entries are overwritten
		$paths = array_reverse(self::$include_paths);

		// Start the list of files
		$files = array();

		foreach ($paths as $path)
		{
			if (is_dir($path.$directory))
			{
				$dir = new DirectoryIterator($path.$directory);

				foreach ($dir as $file)
				{
					$filename = $file->getFilename();

					if ($filename[0] === '.')
						continue;

					if ($file->isDir())
					{
						if ($recursive === TRUE)
						{
							// Recursively add files
							$files = array_merge($files, self::list_files($directory.'/'.$filename, TRUE));
						}
					}
					else
					{
						// Add the file to the files
						$files[$directory.'/'.$filename] = realpath($file->getPathname());
					}
				}
			}
		}

		// Cache is about to change
		self::$file_path_changed = TRUE;

		// Cache and return the files
		return self::$file_path[$directory.'/*'] = $files;
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

} // End Kohana
