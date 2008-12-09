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

	// Route URI string
	protected $uri = '';

	// Regular expressions for route keys
	protected $regex = array();

	// Default values for route keys
	protected $defaults = array('method' => 'index');

	// Compiled regex cache
	protected $compiled;

	// Matched URI keys
	protected $keys = array();

	/**
	 * Creates a new route. Sets the URI and regular expressions for keys.
	 *
	 * @param   string   route URI pattern
	 * @param   array    key patterns
	 */
	public function __construct($uri, array $regex = array())
	{
		$this->uri = $uri;

		if ( ! empty($regex))
		{
			$this->regex = $regex;
		}

		// Attempt to load the cached regex
		if (($this->compiled = Kohana::cache('route:'.$uri)) === NULL)
		{
			// Compile and cache the compiled regex
			Kohana::cache('route:'.$uri, $this->compiled = $this->compile());
		}
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

		if (preg_match_all('#'.Route::REGEX_KEY.'#', $regex, $keys))
		{
			// Compile every :key into its regex equivalent
			$replace = $this->compile_keys($keys[0]);

			// Replace each :key with with <key>PATTERN
			$regex = strtr($regex, $replace);
		}

		return '^'.$regex.'$';
	}

	/**
	 * Compile a segment keys into a regular expression patterns.
	 * 
	 * @param   array   array of keys
	 * @return  array
	 */
	protected function compile_keys(array $keys)
	{
		$groups = array();
		foreach ($keys as $key)
		{
			// Remove the colon from the key to get the name
			$name = substr($key, 1);

			// Create a named regex match
			$regex = '(?P<'.$name.'>';

			if (isset($this->regex[$name]))
			{
				// Use the pre-defined pattern
				$regex .= $this->regex[$name];
			}
			else
			{
				// Use the default pattern
				$regex .= Route::REGEX_SEGMENT;
			}

			// Add the regex group with its key
			$groups[$key] = $regex.')';
		}

		return $groups;
	}

} // End Kohana_Route
