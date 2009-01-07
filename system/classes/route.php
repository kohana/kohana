<?php
/**
 * Routes are used to determine the controller and method for a requested URI.
 * Every route generates a regular expression which is used to match a URI
 * and a route. Routes may also contain keys which can be used to set the
 * controller, method, and method arguments.
 *
 * Each :key will be translated to a regular expression using a default regular
 * expression pattern. You can override the default pattern by providing a
 * pattern for the key:
 *
 *     // This route will only match when :id is a digit
 *     Route::factory('user/edit/:id', array('id' => '\d+'));
 *
 *     // This route will match when :path is anything
 *     Route::factory(':path', array('path' => '.*'));
 *
 * It is also possible to create optional segments by using parenthesis in
 * the URI definition:
 *
 *     // This is the standard default route, and no keys are required
 *     Route::defautl('(:controller(/:method(/:id)))');
 *
 *     // This route only requires the :file key
 *     Route::factory('(:path/):file(:format)', array('path' => '.*', 'format' => '\.\w+'));
 *
 * Routes also provide a way to generate URIs (called "reverse routing"), which
 * makes them an extremely powerful and flexible way to generate internal links.
 *
 * $Id: route.php 3730 2008-11-27 00:37:57Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Route_Core {

	const REGEX_KEY     = ':[a-zA-Z0-9_]++';
	const REGEX_SEGMENT = '[^/.,;?]++';
	const REGEX_ESCAPE  = '[.\\+*?[^\\]${}=!<>|]';

	/**
	 * Returns a new Route object.
	 *
	 * @chainable
	 * @param   string  route URI
	 * @param   array   regular expressions for keys
	 * @return  Route
	 */
	public static function factory($uri, array $regex = array())
	{
		return new Route($uri, $regex);
	}

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

	// Route URI string
	protected $uri = '';

	// Regular expressions for route keys
	protected $regex = array();

	// Default values for route keys
	protected $defaults = array('method' => 'index');

	// Compiled regex cache
	protected $compiled;

	/**
	 * Creates a new route. Sets the URI and regular expressions for keys.
	 *
	 * @param   string   route URI pattern
	 * @param   array    key patterns
	 */
	public function __construct($uri, array $regex = array())
	{
		if ( ! empty($regex))
		{
			$this->regex = $regex;
		}

		// Store the routed URI
		$this->uri = $uri;

		if (($regex = Kohana::cache('kohana_route_regex_'.$uri)) === NULL)
		{
			// Compile the complete regex for this uri
			$regex = $this->compile();

			// Cache the compiled regex
			Kohana::cache('kohana_route_regex_'.$uri, $regex);
		}

		// Store the compiled regex locally
		$this->compiled = $regex;
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
	public function defaults(array $defaults)
	{
		if (empty($defaults['method']))
		{
			$defaults['method'] = 'index';
		}

		$this->defaults = $defaults;

		return $this;
	}

	/**
	 * Tests if the route matches a given URI. A successful match will return
	 * all of the routed parameters as an array. A failed match will return
	 * boolean FALSE.
	 *
	 *     // This route will only match if the :controller, :method, and :id exist
	 *     $params = Route::factory(':controller/:method/:id', array('id' => '\d+'))
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
		if (preg_match('#'.$this->compiled.'#', $uri, $matches))
		{
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

			foreach ($this->defaults as $key => $value)
			{
				if ( ! isset($params[$key]))
				{
					// Set default values for any key that was not matched
					$params[$key] = $value;
				}
			}

			return $params;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Returns the compiled regular expression for the route. This translates
	 * keys and optional groups to a proper PCRE regular expression.
	 *
	 * @return  string
	 */
	protected function compile()
	{
		// The URI should be considered literal except for keys and optional parts
		// Escape everything preg_quote would escape except for : ( )
		$this->uri = preg_replace('#'.Route::REGEX_ESCAPE.'#', '\\\\$0', $this->uri);

		if (strpos($this->uri, '(') === FALSE)
		{
			// No optional parts of the URI
			$regex = $this->uri;
		}
		else
		{
			// Make optional parts of the URI non-capturing and optional
			$regex = str_replace(array('(', ')'), array('(?:', ')?'), $this->uri);
		}

		// Insert default regex for keys
		$regex = str_replace(array('<', '>'), array('(?P<', '>'.self::REGEX_SEGMENT.')'), $regex);

		// Replace default regex patterns with user-specified patterns
		if (count($this->regex))
		{
			$replace = array();
			foreach ($this->regex as $key => $value)
			{
				$search = "<$key>".self::REGEX_SEGMENT;
				$replace[$search] = "<$key>$value";
			}
			$regex = strtr($regex, $replace);
		}

		return '^'.$regex.'$';
	}

} // End Kohana_Route
