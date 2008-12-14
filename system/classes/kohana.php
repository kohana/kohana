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

	const VERSION   = '3.0';
	const CODENAME  = 'renaissance';

	// Save the cache on shutdown?
	public static $save_cache = FALSE;

	// Current server is Windows?
	public static $is_windows = FALSE;

	// Current request is command line?
	public static $is_cli = FALSE;

	// Client request method
	public static $request_method = 'GET';

	// Default character set of input and output
	public static $charset = 'UTF-8';

	// Default locale of your application
	public static $default_locale = 'en_US';

	// Current configuration
	public static $config;

	// Current locale
	public static $locale;

	// Current timezone
	public static $timezone;

	// Environment has been initialized?
	private static $init = FALSE;

	// Current modules
	private static $modules = array();

	// Include paths that are used to find files
	private static $include_paths = array(APPPATH, SYSPATH);

	// Cache for resource location
	private static $file_path;
	private static $file_path_changed = FALSE;

	// Cache of current language messages
	private static $language;

	/**
	 * Initializes the environment:
	 *
	 * - Loads hooks
	 * - Converts all input variables to the configured character set
	 *
	 * @return  void
	 */
	public static function init()
	{
		if (self::$init === TRUE)
			return;

		// Test if the current environment is command-line
		self::$is_cli = (PHP_SAPI === 'cli');

		// Test if the current evironment is Windows
		self::$is_windows = (DIRECTORY_SEPARATOR === '\\');

		// Determine if the server supports UTF-8 natively
		utf8::$server_utf8 = extension_loaded('mbstring');

		// Load the file path cache
		self::$file_path = Kohana::cache('kohana_file_paths');

		// Load the configuration loader
		self::$config = new Kohana_Config_Loader;

		// Import the main configuration locally
		$config = self::$config->kohana;

		// Set the default locale
		self::$default_locale = $config->default_locale;
		self::$save_cache     = $config->save_cache;
		self::$charset        = $config->charset;

		// Localize the environment
		self::locale($config->locale);

		// Set the enviroment time
		self::timezone($config->timezone);

		// Enable modules
		self::modules($config->modules);

		if ($hooks = self::list_files('hooks', TRUE))
		{
			foreach ($hooks as $hook)
			{
				// Load each hook in the order they appear
				require $hook;
			}
		}

		// Convert global variables to current charset.
		$_GET    = utf8::clean($_GET, self::$charset);
		$_POST   = utf8::clean($_POST, self::$charset);
		$_SERVER = utf8::clean($_SERVER, self::$charset);

		// The system has been initialized
		self::$init = TRUE;
	}

	public static function instance()
	{
		echo Kohana::debug(__METHOD__.' reporting for duty!');
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
			// Save the configuration
			self::$config->save();

			if (self::$file_path_changed === TRUE)
			{
				// Save the file found file paths
				Kohana::cache('kohana_file_paths', self::$file_path);
			}
		}
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
	 * Retrieves a language string, optionally with arguments.
	 * 
	 * @param   string  message to translate
	 * @param   array   replacements for placeholders
	 * @return  string
	 */
	public function i18n($string, array $args = NULL)
	{
		if (self::$locale !== self::$default_locale)
		{
			if ( ! isset(self::$language[$string]))
			{
				// Let the user know that this message needs to be translated
				throw new Exception('The requested string ['.$string.'] has not been translated to '.self::$locale);
			}

			// Get the message translation
			$string = self::$language[$string];
		}

		if ($args === NULL)
			return $string;

		return strtr($string, $args);
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

			if (($messages = Kohana::cache('kohana_i18n_'.self::$locale)) === NULL)
			{
				// Find all this languages translation files
				$files = self::find_file('i18n', self::$locale);

				$messages = array();
				foreach ($files as $file)
				{
					// Load the messages in this file
					$messages = array_merge($messages, include $file);
				}

				// Cache the combined messages
				Kohana::cache('kohana_i18n_'.self::$locale, $messages);
			}

			// Load the language internally
			self::$language = $messages;
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
	public static function modules(array $modules = NULL)
	{
		if ($modules === NULL)
			return $modules;

		// Start a new set of include paths, APPPATH first
		$include_paths = array(APPPATH);

		foreach ($modules as $name => $path)
		{
			if (is_dir($path))
			{
				// Get the absolute path to the module
				$path = realpath($path);

				if (Kohana::$is_windows === TRUE)
				{
					// Remove backslashes
					$path = str_replace('\\', '/', $path);
				}

				// Add the module to include paths
				$include_paths[] = $path.'/';
			}
			else
			{
				unset($modules[$name]);
			}
		}

		// Set the current module list
		self::$modules = $modules;

		// Finish the include paths by adding SYSPATH
		$include_paths[] = SYSPATH;

		// Set the new include paths
		self::$include_paths = $include_paths;
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

		if ($dir === 'i18n' OR $dir === 'config')
		{
			// Include paths must be searched in reverse
			$paths = array_reverse(self::$include_paths);

			// Array of files that have been found
			$found = array();

			foreach ($paths as $dir)
			{
				if (file_exists($dir.$path))
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

			foreach (self::$include_paths as $dir)
			{
				if (file_exists($dir.$path))
				{
					// A path has been found
					$found = $dir.$path;

					// Stop searching
					break;
				}
			}
		}

		if ( ! empty($found))
		{
			// Cache is about to change
			self::$file_path_changed = TRUE;

			// Cache path to this file
			self::$file_path[$path] = $found;
		}

		return $found;
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
		// Cache key, double wildcard for recursive
		$key = $directory.'/*'.($recursive === TRUE ? '*' : '');

		if (isset(self::$file_path[$key]))
		{
			// The files in this path have already been found
			return self::$file_path[$key];
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
					elseif ($directory === 'i18n')
					{
						// Files in i18n/ do not get overwritten, as all of them must be loaded
						$files[] = realpath($file->getPathname());
					}
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
		return self::$file_path[$key] = $files;
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

		// Cache directories are split by keys
		$dir = APPPATH.'cache/'.$file[0].'/';

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
			mkdir($dir, 0777);
		}

		// Serialize the data and create the cache
		return (bool) file_put_contents($dir.$file, '<?php return '.var_export($data, TRUE).';');
	}

	public function array_get($key, array $array, $default = NULL)
	{
		if (empty($array))
			return $default;

		if (strpos($key, '.') === FALSE)
		{
			// This is a quick shortcut that optimizes single-level keys
			return isset($array[$key]) ? $array[$key] : $default;
		}

		// Split the key
		$keys = explode('.', $key);

		do
		{
			// Get the next key
			$key = array_shift($keys);

			if (isset($array[$key]))
			{
				if (is_array($array[$key]) AND ! empty($keys))
				{
					// Dig down to prepare the next loop
					$array = $array[$key];
				}
				else
				{
					// Requested key was found
					return $array[$key];
				}
			}
			else
			{
				// Requested key is not set
				break;
			}
		}
		while ( ! empty($keys));

		return $default;
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
