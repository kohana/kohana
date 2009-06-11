<?php defined('SYSPATH') or die('No direct script access.');

class Validation extends ArrayObject {

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
		return new Validation($array);
	}

	// Field rules
	protected $_rules = array();

	// Field labels
	protected $_labels = array();

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
	 * Sets or overwrites the label name for a field.
	 *
	 * @param   string  field name
	 * @param   string  label
	 * @return  $this;
	 */
	public function label($field, $label)
	{
		// Set the label for this field
		$this->_labels[$field] = $label;

		return $this;
	}

	/**
	 * Overwrites or appends rules to a field.
	 *
	 * @param   string  field name
	 * @param   array   rules to append or overwrite
	 * @return  $this
	 */
	public function rules($field, array $rules = NULL)
	{
		if ( ! isset($this->_labels[$field]))
		{
			// Set the field label to the field name
			$this->_labels[$field] = preg_replace('/[^\pL]+/', ' ', $field);
		}

		foreach ($rules as $rule => $params)
		{
			// Note the foreach() must be used here so that duplicate rules
			// will overwrite each other without changing the order the rules
			// are validated.

			// Store the rule and params for this rule
			$this->_rules[$field][$rule] = $params;
		}

		return $this;
	}

	/**
	 * Executes all validation rules.
	 *
	 * @param   array    error list
	 * @return  boolean
	 */
	public function validate( & $errors)
	{
		// Data to validate
		$data = array();

		// Assume nothing has been submitted
		$submitted = FALSE;

		// Get the expected fields
		$expected = array_keys($this->_rules);

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

		foreach ($this->_rules as $field => $rules)
		{
			if (isset($errors[$field]))
			{
				// This field already has validation errors, do not overwrite
				continue;
			}

			foreach ($rules as $rule => $params)
			{
				if ($this[$field] === NULL AND ! ($rule === 'required' OR $rule === 'matches'))
				{
					// Skip this rule for empty fields
					continue;
				}

				if (method_exists($this, $rule))
				{
					// Use this object as the callback
					$callback = array($this, $rule);
				}
				elseif (method_exists('valid', $rule))
				{
					// Use the valid helper as the callback
					$callback = array('valid', $rule);
				}
				else
				{
					if (strpos($rule, '::') === FALSE)
					{
						// Make a function call
						$callback = $rule;
					}
					else
					{
						// Make a static class call
						$callback = explode('::', $rule);
					}
				}

				if ( ! is_array($callback))
				{
					if ($params === NULL)
					{
						// Call the function
						$passed = $callback($this[$field]);
					}
					else
					{
						// Call the function with parameters
						$passed = $callback($this[$field], $params);
					}
				}
				else
				{
					// Call the class method with parameters
					$passed = call_user_func($callback, $this[$field], $params);
				}

				if ($passed === FALSE)
				{
					if (is_array($params))
					{
						// Make a text list of the parameters
						$params = implode(', ', $params);
					}

					if ( ! isset(Validation::$messages[$rule]))
					{
						// Use the default message, no custom message exists
						$rule = 'default';
					}

					// Add the field error using i18n
					$errors[$field] = __(Validation::$messages[$rule], array(
						':field'  => __($this->_labels[$field]),
						':params' => $params,
					));
				}
			}
		}

		return empty($errors);
	}

} // End Validation
