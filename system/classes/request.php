<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Request and response wrapper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Request_Core {

	// HTTP status codes and messages
	protected static $messages = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',

		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);

	// Main request instance
	protected static $_instance;

	/**
	 * Main request singleton instance. If no URI is provided, the URI will
	 * be automatically detected using PATH_INFO, REQUEST_URI, or PHP_SELF.
	 *
	 * @param   string   URI of the request
	 * @return  Request
	 */
	public static function instance( & $uri = FALSE)
	{
		if (Request::$_instance === NULL)
		{
			// Create the initial request parameters
			$params = array('method' => 'GET', 'get' => NULL, 'post' => NULL);

			if (Kohana::$is_cli)
			{
				// Get the command line options
				$options = cli::options('uri', 'method', 'get', 'post');

				if (isset($options['uri']))
				{
					// Use the specified URI
					$uri = $options['uri'];
				}

				if (isset($options['method']))
				{
					// Request method specified
					$params['method'] = $options['method'];
				}

				if (isset($options['get']))
				{
					// GET data specified
					parse_str($options['get'], $params['get']);
				}

				if (isset($options['post']))
				{
					// POST data specified
					parse_str($options['post'], $params['post']);
				}
			}
			else
			{
				if (isset($_SERVER['REQUEST_METHOD']))
				{
					// Use the server request method
					$params['method'] = $_SERVER['REQUEST_METHOD'];
				}

				if ($params['method'] !== 'GET' AND $params['method'] !== 'POST')
				{
					// Methods besides GET and POST do not properly parse the form-encoded
					// query string into the $_POST array, so we do it manually.
					parse_str(file_get_contents('php://input'), $params['post']);
				}
			}

			if ($uri === FALSE)
			{
				if (isset($_SERVER['PATH_INFO']))
				{
					// PATH_INFO is most realiable way to handle routing, as it
					// does not include the document root or index file
					$uri = $_SERVER['PATH_INFO'];
				}
				else
				{
					// REQUEST_URI and PHP_SELF both provide the full path,
					// including the document root and index file
					if (isset($_SERVER['REQUEST_URI']))
					{
						$uri = $_SERVER['REQUEST_URI'];
					}
					elseif (isset($_SERVER['PHP_SELF']))
					{
						$uri = $_SERVER['PHP_SELF'];
					}

					if (isset($_SERVER['SCRIPT_NAME']) AND strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
					{
						// Remove the document root and index file from the URI
						$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
					}
				}
			}

			// Create the instance singleton
			Request::$_instance = new Request($uri, $params);
		}

		return Request::$_instance;
	}

	/**
	 * Creates a new request object for the given URI. Global GET and POST data
	 * can be overloaded.
	 *
	 * @chainable
	 * @param   string  URI of the request
	 * @param   array   overloaded GET data
	 * @param   array   overloaded POST data
	 * @return  Request
	 */
	public static function factory($uri, array $params = NULL)
	{
		return new Request($uri, $params);
	}

	/**
	 * @var  object  route used for this request
	 */
	public $route;

	/**
	 * @var  string  request method type (GET, POST, PUT, etc)
	 */
	public $method = 'GET';

	/**
	 * @var  decimal  HTTP version (1.0, 1.1)
	 */
	public $version = 1.1;

	/**
	 * @var  integer  HTTP response code (200, 404, 500, etc)
	 */
	public $status = 200;

	/**
	 * @var  string  response body
	 */
	public $response;

	/**
	 * @var  array  headers to send with the response body
	 */
	public $headers = array('content-type' => 'text/html; charset=utf-8');

	/**
	 * @var  string  controller directory
	 */
	public $directory = '';

	/**
	 * @var  string  controller to be executed
	 */
	public $controller;

	/**
	 * @var  string  action to be executed in the controller
	 */
	public $action;

	/**
	 * @var  string  the URI of the request
	 */
	public $uri;

	// Parameters extracted from the route
	protected $_params;

	// Request GET and POST data
	protected $_get;
	protected $_post;

	/**
	 * Creates a new request object for the given URI. Global GET and POST data
	 * can be overloaded by setting "get" and "post" in the parameters.
	 *
	 * @param   string  URI of the request
	 * @param   array   request parameters
	 * @return  void
	 * @throws  Kohana_Exception  if no route matches the URI
	 */
	public function __construct($uri, array $params = NULL)
	{
		if (isset($params['method']))
		{
			// Set the request method
			$this->method = strtoupper($params['method']);
		}

		// Load GET data
		$this->_get = isset($params['get']) ? $params['get'] : $_GET;

		// Load POST data
		$this->_post = isset($params['post']) ? $params['post'] : $_POST;

		// Remove trailing slashes from the URI
		$uri = trim($uri, '/');

		// Load routes
		$routes = Route::all();

		foreach ($routes as $name => $route)
		{
			if ($params = $route->matches($uri))
			{
				// Store the URI
				$this->uri = $uri;

				// Store the matching route
				$this->route = $route;

				if (isset($params['directory']))
				{
					// Controllers are in a sub-directory
					$this->directory = $params['directory'];
				}

				// Store the controller and action
				$this->controller = $params['controller'];
				$this->action     = $params['action'];

				// These are accessible as public vars and can be overloaded
				unset($params['controller'], $params['action'], $params['directory']);

				// Params cannot be changed once matched
				$this->_params = $params;

				return;
			}
		}

		throw new Kohana_Exception('Unable to find a route to handle :uri', array(':uri' => $uri));
	}

	/**
	 * Generates a complete URL for the current route.
	 *
	 * @param   array   additional route parameters
	 * @return  string
	 */
	public function url(array $params = NULL)
	{
		return Kohana::$base_url.$this->uri($params);
	}

	/**
	 * Generates a relative URI for the current route.
	 *
	 * @param   array   additional route parameters
	 * @return  string
	 */
	public function uri(array $params = NULL)
	{
		if ( ! isset($params['controller']))
		{
			// Add the current controller
			$params['controller'] = $this->controller;
		}

		if ( ! isset($params['action']))
		{
			// Add the current action
			$params['action'] = $this->action;
		}

		return $this->route->uri($params);
	}

	/**
	 * Retrieves a value from the route parameters.
	 *
	 * @param   string   key of the value
	 * @param   mixed    default value if the key is not set
	 * @return  mixed
	 */
	public function param($key = NULL, $default = NULL)
	{
		if ($key === NULL)
		{
			// Return the full array
			return $this->_params;
		}

		return isset($this->_params[$key]) ? $this->_params[$key] : $default;
	}

	/**
	 * Retrieves a value from GET data.
	 *
	 * @param   string   key of the value
	 * @param   mixed    default value if the key is not set
	 * @return  mixed
	 */
	public function get($key = NULL, $default = NULL)
	{
		if ($key === NULL)
		{
			// Return the full array
			return $this->_get;
		}

		return isset($this->_get[$key]) ? $this->_get[$key] : $default;
	}

	/**
	 * Retrieves a value from POST data.
	 *
	 * @param   string   key of the value
	 * @param   mixed    default value if the key is not set
	 * @return  mixed
	 */
	public function post($key = NULL, $default = NULL)
	{
		if ($key === NULL)
		{
			// Return the full array
			return $this->_post;
		}

		return isset($this->_post[$key]) ? $this->_post[$key] : $default;
	}

	/**
	 * Gets an named header.
	 *
	 * @param   string   header name
	 * @return  string   header value
	 * @return  FALSE    if no header is found
	 */
	public function get_header($name)
	{
		// The header name is always stored lowercase
		$name = strtolower($name);

		return isset($this->headers[$name]) ? $this->headers[$name] : FALSE;
	}

	/**
	 * Sets an named header.
	 *
	 * @param   string   header name
	 * @param   string   header value
	 * @return  $this
	 */
	public function set_header($name, $value)
	{
		// The header name is always stored lowercase
		$name = strtolower($name);

		// Add the header to the list
		$this->headers[$name] = $value;

		return $this;
	}

	/**
	 * Sets an unnamed header. Raw headers cannot be retrieved with get_header!
	 *
	 * @param   string   header string
	 * @return  $this
	 */
	public function set_raw_header($value)
	{
		$this->headers[] = $value;

		return $this;
	}

	/**
	 * Deletes a named header.
	 *
	 * @param   string   header name
	 * @return  $this
	 */
	public function delete_header($name)
	{
		// The header name is always stored lowercase
		$name = strtolower($name);

		// Remove the header from the list
		unset($this->_headers[$name]);

		return $this;
	}

	/**
	 * Sends the response status and all set headers.
	 *
	 * @return  $this
	 */
	public function send_headers()
	{
		// Get the status message
		$message = Request::$messages[$this->status];

		// Send the HTTP status message
		header("HTTP/{$this->version} {$this->status} {$message}", TRUE, $this->status);

		foreach ($this->headers as $name => $value)
		{
			if (is_string($name))
			{
				// Convert the header name to Title-Case, to match RFC spec
				$name = str_replace('-', ' ', $name);
				$name = str_replace(' ', '-', ucwords($name));

				// Combine the name and value to make a raw header
				$value = "{$name}: {$value}";
			}

			// Send the raw header
			header($value, TRUE);
		}

		return $this;
	}

	/**
	 * Processes the request, executing the controller. Before the routed action
	 * is run, the before() method will be called, which allows the controller
	 * to overload the action based on the request parameters. After the action
	 * is run, the after() method will be called, for post-processing.
	 *
	 * By default, the output from the controller is captured and returned, and
	 * no headers are sent.
	 *
	 * @param   boolean   capture the output and return it
	 * @return  string    for captured output
	 * @return  void      for displayed output
	 */
	public function execute($capture = TRUE)
	{
		if (empty($this->directory))
		{
			// There is no controller prefix
			$prefix = '';
		}
		else
		{
			// Make the directory name into a class prefix
			$prefix = str_replace(array('\\', '/'), '_', trim($this->directory, '/')).'_';
		}

		// Set the controller class name
		$controller = 'controller_'.$prefix.$this->controller;

		// Load the controller
		$controller = new $controller($this);

		// A new action is about to be run
		$controller->before($this->method);

		// Set the action name after running before() to allow the controller
		// to change the action based on the current parameters
		$action = 'action_'.$this->action;

		// Execute the action
		$controller->$action();

		// The action has been run
		$controller->after($this->method);

		if ($capture === TRUE)
			return $this->response;

		// Send the headers
		$this->send_headers();

		// Send the response
		echo $this->response;
	}

} // End Request
