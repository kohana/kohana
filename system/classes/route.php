<?php
/**
 * Routes are used to determine the controller and method for a requested URI.
 * Every route generates a regular expression which is used to match a URI
 * and a route. Routes may also contain keys which can be used to set the
 * controller, method, and method arguments.
 *
 * Each <key> will be translated to a regular expression using a default
 * regular expression pattern. You can override the default pattern by providing
 * a pattern for the key:
 *
 *     // This route will only match when <id> is a digit
 *     Route::factory('user/edit/<id>', array('id' => '\d+'));
 *
 *     // This route will match when <path> is anything
 *     Route::factory('<path>', array('path' => '.*'));
 *
 * It is also possible to create optional segments by using parenthesis in
 * the URI definition:
 *
 *     // This is the standard default route, and no keys are required
 *     Route::default('(<controller>(/<method>(/<id>)))');
 *
 *     // This route only requires the :file key
 *     Route::factory('(<path>/)<file>(<format>)', array('path' => '.*', 'format' => '\.\w+'));
 *
 * Routes also provide a way to generate URIs (called "reverse routing"), which
 * makes them an extremely powerful and flexible way to generate internal links.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Route_Core {

	const REGEX_KEY     = '<([a-zA-Z0-9_]++)>';
	const REGEX_SEGMENT = '[^/.,;?]++';
	const REGEX_ESCAPE  = '[.\\+*?[^\\]${}=!|]';

	protected static $_routes = array();

	/**
	 * Called when the object is re-constructed from the cache.
	 *
	 * @param   array   cached values
	 * @return  Route
	 */
	public static function __set_state(array $values)
	{
		// Reconstruct the route
		$route = new Route($values['uri'], $values['regex']);

		// Set defaults
		$route->defaults = $values['defaults'];

		return $route;
	}

	/**
	 * Stores a named route and returns it.
	 *
	 * @param   string   route name
	 * @param   string   URI pattern
	 * @param   array    regex patterns for route keys
	 * @return  Route
	 */
	public static function add($name, $uri, array $regex = NULL)
	{
		return Route::$_routes[$name] = new Route($uri, $regex);
	}

	/**
	 * Retrieves a named route.
	 *
	 * @param   string  route name
	 * @return  Route
	 * @return  FALSE   when no route is found
	 */
	public static function get($name)
	{
		return isset(Route::$_routes[$name]) ? Route::$_routes[$name] : FALSE;
	}

	/**
	 * Retrieves all named routes, with the default route last.
	 *
	 * @return  array  named routes
	 */
	public static function all()
	{
		return Route::$_routes;
	}

	// Route URI string
	protected $_uri = '';

	// Controller directory
	protected $_directory;

	// Regular expressions for route keys
	protected $_regex = array();

	// Default values for route keys
	protected $_defaults = array('method' => 'index');

	// Compiled regex cache
	protected $_route_regex;

	/**
	 * Creates a new route. Sets the URI and regular expressions for keys.
	 *
	 * @param   string   route URI pattern
	 * @param   array    key patterns
	 */
	public function __construct($uri, array $regex = NULL)
	{
		if ( ! empty($regex))
			$this->_regex = $regex;

		// Store the URI that this route will match
		$this->_uri = $uri;

		if (($regex = Kohana::cache('kohana_route:'.$uri)) === NULL)
		{
			// Compile the complete regex for this uri
			$regex = $this->_compile();

			// Cache the compiled regex
			Kohana::cache('kohana_route:'.$uri, $regex);
		}

		// Store the compiled regex locally
		$this->_route_regex = $regex;
	}

	/**
	 * Sets the prefix directory for all controllers matched by this route.
	 *
	 * @param   string  directory path
	 * @return  Route
	 */
	public function directory($directory = NULL)
	{
		$this->_directory = strtolower($directory);

		return $this;
	}

	/**
	 * Provides default values for keys when they are not present. The default
	 * method will always be "index" unless it is overloaded with this method.
	 *
	 *     $route->defaults(array('controller' => 'welcome', 'method' => 'index'));
	 *
	 * @chainable
	 * @param   array  key values
	 * @return  Route
	 */
	public function defaults(array $defaults = NULL)
	{
		if (empty($defaults['action']))
		{
			$defaults['action'] = 'index';
		}

		$this->_defaults = $defaults;

		return $this;
	}

	/**
	 * Tests if the route matches a given URI. A successful match will return
	 * all of the routed parameters as an array. A failed match will return
	 * boolean FALSE.
	 *
	 *     // This route will only match if the <controller>, <method>, and <id> exist
	 *     $params = Route::factory('<controller>/<method>/<id>', array('id' => '\d+'))
	 *         ->match('users/edit/10');
	 *     // The parameters are now:
	 *     // controller = users
	 *     // method = edit
	 *     // id = 10
	 *
	 * This method should almost always be used within an if/else block:
	 *
	 *     if ($params = $route->match($uri))
	 *     {
	 *         // Parse the parameters
	 *     }
	 *
	 * @param   string  URI to match
	 * @return  array   on success
	 * @return  FALSE   on failure
	 */
	public function matches($uri)
	{
		if ( ! preg_match($this->_route_regex, $uri, $matches))
			return FALSE;

		$params = array();
		foreach ($matches as $key => $value)
		{
			if (is_int($key))
			{
				// Skip all unnamed keys
				continue;
			}

			// Set the value for all matched keys
			$params[$key] = $value;
		}

		foreach ($this->_defaults as $key => $value)
		{
			if ( ! isset($params[$key]))
			{
				// Set default values for any key that was not matched
				$params[$key] = $value;
			}
		}

		if ( ! empty($this->_directory))
		{
			// Create the class prefix
			$prefix = str_replace(array('\\', '/'), '_', $this->_directory);

			// Add the prefix to the controller
			$params['controller'] = $prefix.'_'.$params['controller'];
		}

		return $params;
	}

	/**
	 * Generates a URI for the current route based on the parameters given.
	 *
	 * @param   array   URI parameters
	 * @return  string
	 * @throws  Kohana_Exception  when the URI will not match the current route
	 */
	public function uri(array $params = NULL)
	{
		if ($params === NULL)
			$params = $this->_defaults;

		// Start with the routed URI
		$uri = $this->_uri;

		if (strpos($uri, '<') === FALSE AND strpos('(', $this->uri) === FALSE)
		{
			// This is a static route, no need to replace anything
			return $uri;
		}

		if (preg_match_all('#'.Route::REGEX_KEY.'#', $uri, $keys))
		{
			foreach ($keys[1] as $key)
			{
				$search[]  = "<$key>";
				$replace[] = isset($params[$key]) ? $params[$key] : '';
			}

			// Replace all the variable keys in the URI
			$uri = str_replace($search, $replace, $uri);
		}

		if (strpos($uri, '(') !== FALSE)
		{
			// Remove all groupings from the URI
			$uri = str_replace(array('(', ')'), '', $uri);
		}

		// Trim off extra slashes
		$uri = rtrim($uri, '/');

		if ( ! preg_match($this->_route_regex, $uri))
		{
			// This will generally happen with the user supplies invalid parameters
			throw new Exception('The generated URI "'.$uri.'" will not be matched by "'.$this->_uri.'"');
		}

		return $uri;
	}

	/**
	 * Returns the compiled regular expression for the route. This translates
	 * keys and optional groups to a proper PCRE regular expression.
	 *
	 * @return  string
	 */
	protected function _compile()
	{
		// The URI should be considered literal except for keys and optional parts
		// Escape everything preg_quote would escape except for : ( ) < >
		$regex = preg_replace('#'.Route::REGEX_ESCAPE.'#', '\\\\$0', $this->_uri);

		if (strpos($regex, '(') !== FALSE)
		{
			// Make optional parts of the URI non-capturing and optional
			$regex = str_replace(array('(', ')'), array('(?:', ')?'), $regex);
		}

		// Insert default regex for keys
		$regex = str_replace(array('<', '>'), array('(?P<', '>'.Route::REGEX_SEGMENT.')'), $regex);

		if ( ! empty($this->_regex))
		{
			$search = $replace = array();
			foreach ($this->_regex as $key => $value)
			{
				$search[]  = "<$key>".Route::REGEX_SEGMENT;
				$replace[] = "<$key>$value";
			}

			// Replace the default regex with the user-specified regex
			$regex = str_replace($search, $replace, $regex);
		}

		return '#^'.$regex.'$#';
	}

} // End Route
