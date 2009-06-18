<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Array and variable validation.
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Validate extends ArrayObject {

	public static $messages = array(
		'required'    => ':field is required',
		'default'     => ':field value is invalid',

		'regex'       => ':field does not match the required format',
		'length'      => ':field must be exactly :params characters long',
		'min_length'  => ':field must be at least :params characters long',
		'max_length'  => ':field must be less than :params characters long',

		'in_array'    => ':field must be of the these options: :params',
	);

	/**
	 * Creates a new Validation instance.
	 *
	 * @param   array   array to use for validation
	 * @return  object
	 */
	public static function factory(array $array)
	{
		return new Validate($array);
	}

	/**
	 * Checks if a field is empty.
	 *
	 * @return  boolean
	 */
	public static function not_empty($value)
	{
		return ! empty($value);
	}

	/**
	 * Checks a field against a regular expression.
	 *
	 * @param   string  value
	 * @param   string  regular expression to match (including delimiters)
	 * @return  boolean
	 */
	public static function regex($value, $expression)
	{
		return (bool) preg_match($expression, (string) $value);
	}

	/**
	 * Checks that a field is long enough.
	 *
	 * @param   string   value
	 * @param   integer  minimum length required
	 * @return  boolean
	 */
	public static function min_length($value, $length)
	{
		return UTF8::strlen($value) >= $length;
	}

	/**
	 * Checks that a field is short enough.
	 *
	 * @param   string   value
	 * @param   integer  maximum length required
	 * @return  boolean
	 */
	public static function max_length($value, $length)
	{
		return UTF8::strlen($value) <= $length;
	}

	/**
	 * Check an email address for correct format.
	 *
	 * @see  http://www.iamcal.com/publish/articles/php/parsing_email/
	 * @see  http://www.w3.org/Protocols/rfc822/
	 *
	 * @param   string   email address
	 * @param   boolean  strict RFC compatibility
	 * @return  boolean
	 */
	public static function email($email, $strict = FALSE)
	{
		if ($strict === TRUE)
		{
			$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
			$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
			$atom  = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
			$pair  = '\\x5c[\\x00-\\x7f]';

			$domain_literal = "\\x5b($dtext|$pair)*\\x5d";
			$quoted_string  = "\\x22($qtext|$pair)*\\x22";
			$sub_domain     = "($atom|$domain_literal)";
			$word           = "($atom|$quoted_string)";
			$domain         = "$sub_domain(\\x2e$sub_domain)*";
			$local_part     = "$word(\\x2e$word)*";

			$expression     = "/^$local_part\\x40$domain$/D";
		}
		else
		{
			$expression = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD';
		}

		return (bool) preg_match($expression, (string) $email);
	}

	/**
	 * Validate the domain of an email address by checking if the domain has a
	 * valid MX record.
	 *
	 * Note: checkdnsrr() was not added to Windows until PHP 5.3.0
	 *
	 * @param   string   email address
	 * @return  boolean
	 */
	public static function email_domain($email)
	{
		// Check if the email domain has a valid MX record
		return (bool) checkdnsrr(preg_replace('/^[^@]+@/', '', $email), 'MX');
	}

	/**
	 * Validate URL
	 *
	 * @param   string   URL
	 * @return  boolean
	 */
	public static function url($url)
	{
		return (bool) filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
	}

	/**
	 * Validate IP
	 *
	 * @param   string   IP address
	 * @param   boolean  allow private IP networks
	 * @return  boolean
	 */
	public static function ip($ip, $allow_private = TRUE)
	{
		// Do not allow reserved addresses
		$flags = FILTER_FLAG_NO_RES_RANGE;

		if ($allow_private === FALSE)
		{
			// Do not allow private or reserved addresses
			$flags = $flags | FILTER_FLAG_NO_PRIV_RANGE;
		}

		return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
	}

	/**
	 * Validates a credit card number using the Luhn (mod10) formula.
	 * @see http://en.wikipedia.org/wiki/Luhn_algorithm
	 *
	 * @param   integer       credit card number
	 * @param   string|array  card type, or an array of card types
	 * @return  boolean
	 */
	public static function credit_card($number, $type = NULL)
	{
		// Remove all non-digit characters from the number
		if (($number = preg_replace('/\D+/', '', $number)) === '')
			return FALSE;

		if ($type == NULL)
		{
			// Use the default type
			$type = 'default';
		}
		elseif (is_array($type))
		{
			foreach ($type as $t)
			{
				// Test each type for validity
				if (Validate::credit_card($number, $t))
					return TRUE;
			}

			return FALSE;
		}

		$cards = Kohana::config('credit_cards');

		// Check card type
		$type = strtolower($type);

		if ( ! isset($cards[$type]))
			return FALSE;

		// Check card number length
		$length = strlen($number);

		// Validate the card length by the card type
		if ( ! in_array($length, preg_split('/\D+/', $cards[$type]['length'])))
			return FALSE;

		// Check card number prefix
		if ( ! preg_match('/^'.$cards[$type]['prefix'].'/', $number))
			return FALSE;

		// No Luhn check required
		if ($cards[$type]['luhn'] == FALSE)
			return TRUE;

		// Checksum of the card number
		$checksum = 0;

		for ($i = $length - 1; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit, starting from the right
			$checksum += substr($number, $i, 1);
		}

		for ($i = $length - 2; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit doubled, starting from the right
			$double = substr($number, $i, 1) * 2;

			// Subtract 9 from the double where value is greater than 10
			$checksum += ($double >= 10) ? $double - 9 : $double;
		}

		// If the checksum is a multiple of 10, the number is valid
		return ($checksum % 10 === 0);
	}

	/**
	 * Checks if a phone number is valid.
	 *
	 * @param   string   phone number to check
	 * @return  boolean
	 */
	public static function phone($number, $lengths = NULL)
	{
		if ( ! is_array($lengths))
		{
			$lengths = array(7,10,11);
		}

		// Remove all non-digit characters from the number
		$number = preg_replace('/\D+/', '', $number);

		// Check if the number is within range
		return in_array(strlen($number), $lengths);
	}

	/**
	 * Tests if a string is a valid date string.
	 *
	 * @param   string   date to check
	 * @return  boolean
	 */
	public static function date($str)
	{
		return (strtotime($str) !== FALSE);
	}

	/**
	 * Checks whether a string consists of alphabetical characters only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^\pL++$/uD', (string) $str)
			: ctype_alpha((string) $str);
	}

	/**
	 * Checks whether a string consists of alphabetical characters and numbers only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_numeric($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^[\pL\pN]++$/uD', (string) $str)
			: ctype_alnum((string) $str);
	}

	/**
	 * Checks whether a string consists of alphabetical characters, numbers, underscores and dashes only.
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_dash($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^[-\pL\pN_]++$/uD', (string) $str)
			: (bool) preg_match('/^[-a-z0-9_]++$/iD', (string) $str);
	}

	/**
	 * Checks whether a string consists of digits only (no dots or dashes).
	 *
	 * @param   string   input string
	 * @param   boolean  trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function digit($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^\pN++$/uD', (string) $str)
			: ctype_digit((string) $str);
	}

	/**
	 * Checks whether a string is a valid number (negative and decimal numbers allowed).
	 *
	 * @see Uses locale conversion to allow decimal point to be locale specific.
	 * @see http://www.php.net/manual/en/function.localeconv.php
	 *
	 * @param   string   input string
	 * @return  boolean
	 */
	public static function numeric($str)
	{
		// Use localeconv to set the decimal_point value: Usually a comma or period.
		$locale = localeconv();
		return (bool) preg_match('/^-?[0-9'.$locale['decimal_point'].']++$/D', (string) $str);
	}

	/**
	 * Tests if a number is within a range.
	 *
	 * @param   integer  number to check
	 * @param   array    valid range of input
	 * @return  boolean
	 */
	public static function range($number, array $range)
	{
		// Invalid by default
		$status = FALSE;

		if (is_int($number) OR ctype_digit($number))
		{
			if (count($range) > 1)
			{
				if ($number >= $range[0] AND $number <= $range[1])
				{
					// Number is within the required range
					$status = TRUE;
				}
			}
			elseif ($number >= $range[0])
			{
				// Number is greater than the minimum
				$status = TRUE;
			}
		}

		return $status;
	}

	/**
	 * Checks if a string is a proper decimal format. The format array can be
	 * used to specify a decimal length, or a number and decimal length, eg:
	 * array(2) would force the number to have 2 decimal places, array(4,2)
	 * would force the number to have 4 digits and 2 decimal places.
	 *
	 * @param   string   input string
	 * @param   array    decimal format: y or x,y
	 * @return  boolean
	 */
	public static function decimal($str, $format = NULL)
	{
		// Create the pattern
		$pattern = '/^[0-9]%s\.[0-9]%s$/';

		if ( ! empty($format))
		{
			if (count($format) > 1)
			{
				// Use the format for number and decimal length
				$pattern = sprintf($pattern, '{'.$format[0].'}', '{'.$format[1].'}');
			}
			elseif (count($format) > 0)
			{
				// Use the format as decimal length
				$pattern = sprintf($pattern, '+', '{'.$format[0].'}');
			}
		}
		else
		{
			// No format
			$pattern = sprintf($pattern, '+', '+');
		}

		return (bool) preg_match($pattern, (string) $str);
	}

	/**
	 * Checks if a string is a proper hexadecimal HTML color value. The validation
	 * is quite flexible as it does not require an initial "#" and also allows for
	 * the short notation using only three instead of six hexadecimal characters.
	 * You may want to normalize these values with Format::color().
	 *
	 * @param   string   input string
	 * @return  boolean
	 */
	public static function color($str)
	{
		return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $str);
	}

	/**
	 * @var  array  filters
	 */
	protected $filters = array();

	/**
	 * @var  array  rules
	 */
	protected $rules = array();

	/**
	 * @var  array  callbacks
	 */
	protected $callbacks = array();

	/**
	 * @var  array  field labels
	 */
	protected $labels = array();

	/**
	 * Sets the unique "any field" key and creates an ArrayObject from the
	 * passed array.
	 *
	 * @param   array   array to validate
	 * @return  void
	 */
	public function __construct(array $array)
	{
		parent::__construct($array, ArrayObject::STD_PROP_LIST);
	}

	/**
	 * Returns the array representation of the current object.
	 *
	 * @return  array
	 */
	public function as_array()
	{
		return $this->getArrayCopy();
	}

	/**
	 * Sets or overwrites the label name for a field.
	 *
	 * @param   string  field name
	 * @param   string  label
	 * @return  $this;
	 */
	public function label($field, $label)
	{
		// Set the label for this field
		$this->labels[$field] = $label;

		return $this;
	}

	/**
	 * Overwrites or appends rules to a field. Each rule will be executed once.
	 * All rules must be string names of functions method names.
	 *
	 *     $validation->add_rule('username', 'required')
	 *                ->add_rule('username', 'length', array(4, 32));
	 *
	 * @param   string  field name
	 * @param   string  function or method name
	 * @param   array   extra parameters for the callback
	 * @return  $this
	 */
	public function add_filter($field, $filter, array $params = NULL)
	{
		if ($field !== TRUE AND ! isset($this->labels[$field]))
		{
			// Set the field label to the field name
			$this->labels[$field] = preg_replace('/[^\pL]+/u', ' ', $field);
		}

		// Store the filter and params for this rule
		$this->filters[$field][$filter] = (array) $params;

		return $this;
	}


	/**
	 * Overwrites or appends rules to a field. Each rule will be executed once.
	 * All rules must be string names of functions method names.
	 *
	 *     $validation->add_rule('username', 'required')
	 *                ->add_rule('username', 'length', array(4, 32));
	 *
	 * @param   string  field name
	 * @param   string  function or method name
	 * @param   array   extra parameters for the callback
	 * @return  $this
	 */
	public function add_rule($field, $rule, array $params = NULL)
	{
		if ($field !== TRUE AND ! isset($this->labels[$field]))
		{
			// Set the field label to the field name
			$this->labels[$field] = preg_replace('/[^\pL]+/u', ' ', $field);
		}

		// Store the rule and params for this rule
		$this->rules[$field][$rule] = (array) $params;

		return $this;
	}

	/**
	 * Adds a callback to a field. Each callback will be executed only once.
	 *
	 *     $validation->add_callback('username', array($this, 'check_username'));
	 *
	 * To add a callback to every field already set, use TRUE for the field name.
	 *
	 * @param   string  field name
	 * @param   mixed   callback to add
	 * @return  $this
	 */
	public function add_callback($field, $callback)
	{
		if ( ! isset($this->callbacks[$field]))
		{
			// Create the list for this field
			$this->callbacks[$field] = array();
		}

		if ($field !== TRUE AND ! isset($this->labels[$field]))
		{
			// Set the field label to the field name
			$this->labels[$field] = preg_replace('/[^\pL]+/u', ' ', $field);
		}

		if ( ! in_array($callback, $this->callbacks[$field], TRUE))
		{
			// Store the callback
			$this->callbacks[$field][] = $callback;
		}

		return $this;
	}

	/**
	 * Executes all validation rules.
	 *
	 * @param   array    error list
	 * @return  boolean
	 */
	public function check( & $errors)
	{
		if (Kohana::$profile === TRUE)
		{
			// Start a new benchmark
			$benchmark = Profiler::start('Validation', __FUNCTION__);
		}

		// New data set
		$data = array();

		// Assume nothing has been submitted
		$submitted = FALSE;

		// Get a list of the expected fields
		$expected = array_keys($this->labels);

		// Import the filters, rules, and callbacks locally
		$filters   = $this->filters;
		$rules     = $this->rules;
		$callbacks = $this->callbacks;

		foreach ($expected as $field)
		{
			if (isset($this[$field]))
			{
				// Some data has been submitted, continue validation
				$submitted = TRUE;

				// Use the submitted value
				$data[$field] = $this[$field];
			}
			else
			{
				// No data exists for this field
				$data[$field] = NULL;
			}

			if (isset($filters[TRUE]))
			{
				if ( ! isset($filters[$field]))
				{
					// Initialize the filters for this field
					$filters[$field] = array();
				}

				// Append the filters
				$filters[$field] += $filters[TRUE];
			}

			if (isset($rules[TRUE]))
			{
				if ( ! isset($rules[$field]))
				{
					// Initialize the rules for this field
					$rules[$field] = array();
				}

				// Append the rules
				$rules[$field] += $rules[TRUE];
			}

			if (isset($callbacks[TRUE]))
			{
				if ( ! isset($callbacks[$field]))
				{
					// Initialize the callbacks for this field
					$callbacks[$field] = array();
				}

				// Append the callbacks
				$callbacks[$field] += $callbacks[TRUE];
			}
		}

		// Overload the current array with the new one
		$this->exchangeArray($data);

		// Make sure that the errors are an array
		$errors = (array) $errors;

		if ($submitted === FALSE)
		{
			// Because no data was submitted, validation will not be forced
			return FALSE;
		}

		// Remove the filters, rules, and callbacks that apply to every field
		unset($filters[TRUE], $rules[TRUE], $callbacks[TRUE]);

		// Execute the filters

		foreach ($filters as $field => $set)
		{
			// Skip empty fields
			if ($this[$field] === NULL OR $this[$field] === '') continue;

			// Get the field value
			$value = $this[$field];

			foreach ($set as $filter => $params)
			{
				// Add the field value to the parameters
				array_unshift($params, $value);

				if (strpos($filter, '::') === FALSE)
				{
					// Use a function call
					$function = new ReflectionFunction($filter);

					// Call $function($this[$field], $param, ...) with Reflection
					$value = $function->invokeArgs($params);
				}
				else
				{
					// Split the class and method of the rule
					list($class, $method) = explode('::', $filter, 2);

					// Use a static method call
					$method = new ReflectionMethod($class, $method);

					// Call $Class::$method($this[$field], $param, ...) with Reflection
					$value = $method->invokeArgs(NULL, $params);
				}
			}

			// Set the filtered value
			$this[$field] = $value;
		}

		// Execute the rules

		foreach ($rules as $field => $set)
		{
			// Get the field value
			$value = $this[$field];

			foreach ($set as $rule => $params)
			{
				// Add the field value to the parameters
				array_unshift($params, $value);

				if (method_exists($this, $rule))
				{
					// Use a method in this object
					$method = new ReflectionMethod($this, $rule);

					// Call static::$rule($this[$field], $param, ...) with Reflection
					$passed = $method->invokeArgs(NULL, $params);
				}
				elseif (strpos($rule, '::') === FALSE)
				{
					// Use a function call
					$function = new ReflectionFunction($rule);

					// Call $function($this[$field], $param, ...) with Reflection
					$passed = $function->invokeArgs($params);
				}
				else
				{
					// Split the class and method of the rule
					list($class, $method) = explode('::', $rule, 2);

					// Use a static method call
					$method = new ReflectionMethod($class, $method);

					// Call $Class::$method($this[$field], $param, ...) with Reflection
					$passed = $method->invokeArgs(NULL, $params);
				}

				if ($passed === FALSE)
				{
					if ( ! isset(Validate::$messages[$rule]))
					{
						// Use the default message, no custom message exists
						$rule = 'default';
					}

					if (is_array($params))
					{
						// Make a text list of the parameters
						$params = implode(', ', $params);
					}

					// Translate the field name
					$field = __($this->labels[$field]);

					// Add the field error using i18n
					$errors[$field] = __(Validate::$messages[$rule], array(':field'  => $field, ':params' => $params));

					// This field has an error, stop executing rules
					break;
				}
			}
		}

		// Execute the callbacks

		foreach ($callbacks as $field => $set)
		{
			foreach ($set as $callback)
			{
				// Skip any field that already has an error
				if (isset($errors[$field])) continue;

				if (is_string($callback) AND strpos($callback, '::') !== FALSE)
				{
					// Make the static callback into an array
					$callback = explode('::', $callback, 2);
				}

				if (is_array($callback))
				{
					// Separate the object and method
					list ($object, $method) = $callback;

					// Use a method in the given object
					$method = new ReflectionMethod($object, $method);

					if ( ! is_object($object))
					{
						// The object must be NULL for static calls
						$object = NULL;
					}

					// Call $object->$method($this, $field, $errors) with Reflection
					$errors = $method->invoke($object, $this, $field, $errors);
				}
				else
				{
					// Use a function call
					$function = new ReflectionFunction($callback);

					// Call $function($this, $field, $errors) with Reflection
					$errors = $function->invoke($this, $field, $errors);
				}
			}
		}

		if (isset($benchmark))
		{
			// Stop benchmarking
			Profiler::stop($benchmark);
		}

		return empty($errors);
	}

} // End Validation
