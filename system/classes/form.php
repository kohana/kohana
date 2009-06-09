<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Form helper class.
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class form_Core {

	/**
	 * Generates an opening HTML form tag.
	 *
	 * @param   string  form action
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function open($action = NULL, array $attributes = NULL)
	{
		if ($action === NULL)
		{
			// Use the current URI
			$action = Request::instance()->uri;
		}

		if ($action === '')
		{
			// Use only the base URI
			$action = Kohana::$base_url;
		}
		elseif (strpos($action, '://') === FALSE)
		{
			// Make the URI absolute
			$action = url::site($action);
		}

		// Add the form action to the attributes
		$attributes['action'] = $action;

		// Only accept the default character set
		$attributes['accept-charset'] = Kohana::$charset;

		if ( ! isset($attributes['method']))
		{
			// Use POST method
			$attributes['method'] = 'post';
		}

		return '<form'.html::attributes($attributes).'>';
	}

	/**
	 * Creates the closing form tag.
	 *
	 * @return  string
	 */
	public static function close()
	{
		return '</form>';
	}

	/**
	 * Creates a form input. If no type is specified, a "text" type input will
	 * be returned.
	 *
	 * @param   string  input name
	 * @param   string  input value
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function input($name, $value = '', array $attributes = NULL)
	{
		// Set the input name
		$attributes['name'] = $name;

		// Set the input value
		$attributes['value'] = $value;

		if ( ! isset($attributes['type']))
		{
			// Default type is text
			$attributes['type'] = 'text';
		}

		return '<input'.html::attributes($attributes).' />';
	}

	/**
	 * Creates a hidden form input.
	 *
	 * @param   string  input name
	 * @param   string  input value
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function hidden($name, $value = '', array $attributes = NULL)
	{
		$attributes['type'] = 'hidden';

		return form::input($name, $value, $attributes);
	}

	/**
	 * Creates a password form input.
	 *
	 * @param   string  input name
	 * @param   string  input value
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function password($name, $value = '', array $attributes = NULL)
	{
		$attributes['type'] = 'password';

		return form::input($name, $value, $attributes);
	}

	/**
	 * Creates a file upload form input.
	 *
	 * @param   string  input name
	 * @param   string  input value
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function file($name, array $attributes = NULL)
	{
		$attributes['type'] = 'file';

		return form::input($name, NULL, $attributes);
	}

	/**
	 * Creates a checkbox form input.
	 *
	 * @param   string   input name
	 * @param   string   input value
	 * @param   boolean  checked status
	 * @param   array    html attributes
	 * @return  string
	 */
	public static function checkbox($name, $value = '', $checked = FALSE, array $attributes = NULL)
	{
		$attributes['type'] = 'checkbox';

		if ($checked === TRUE)
		{
			// Make the checkbox active
			$attributes['checked'] = 'checked';
		}

		return form::input($name, $value, $attributes);
	}

	/**
	 * Creates a radio form input.
	 *
	 * @param   string   input name
	 * @param   string   input value
	 * @param   boolean  checked status
	 * @param   array    html attributes
	 * @return  string
	 */
	public static function radio($name, $value = '', $checked = FALSE, array $attributes = NULL)
	{
		$attributes['type'] = 'radio';

		if ($checked === TRUE)
		{
			// Make the radio active
			$attributes['checked'] = 'checked';
		}

		return form::input($name, $value, $attributes);
	}

	/**
	 * Creates a textarea form input.
	 *
	 * @param   string   textarea name
	 * @param   string   textarea body
	 * @param   array    html attributes
	 * @param   boolean  encode existing HTML characters
	 * @return  string
	 */
	public static function textarea($name, $body = '', array $attributes = NULL, $double_encode = TRUE)
	{
		// Set the input name
		$attributes['name'] = $name;

		// Make the textarea body HTML-safe
		$body = htmlspecialchars($title, ENT_NOQUOTES, Kohana::$charset, $double_encode);

		return '<textarea'.html::attributes($attributes).'>'.$body.'</textarea>';
	}

	/**
	 * Creates a select form input.
	 *
	 * @param   string   input name
	 * @param   array    available options
	 * @param   string   selected option
	 * @param   array    html attributes
	 * @return  string
	 */
	public static function select($name, array $options = NULL, $selected = NULL, array $attributes = NULL)
	{
		// Set the input name
		$attributes['name'] = $name;

		if (empty($options))
		{
			// There are no options
			$options = '';
		}
		else
		{
			foreach ($options as $value => $name)
			{
				if (is_array($name))
				{
					// Create a new optgroup
					$group = array('label' => $value);

					// Create a new list of options
					$_options = array();

					foreach ($name as $_value => $_name)
					{
						// Create a new attribute set for this option
						$option = array('value' => $_value);

						if ($_value === $selected)
						{
							// This option is selected
							$option['selected'] = 'selected';
						}

						// Sanitize the option title
						$title = htmlspecialchars($_name, ENT_NOQUOTES, Kohana::$charset, FALSE);

						// Change the option to the HTML string
						$_options[] = '<option'.html::attributes($option).'>'.$title.'</option>';
					}

					// Compile the options into a string
					$_options = "\n".implode("\n", $_options)."\n";

					$options[$value] = '<optgroup'.html::attributes($group).'>'.$_options.'</optgroup>';
				}
				else
				{
					// Create a new attribute set for this option
					$option = array('value' => $value);

					if ($value === $selected)
					{
						// This option is selected
						$option['selected'] = 'selected';
					}

					// Sanitize the option title
					$title = htmlspecialchars($name, ENT_NOQUOTES, Kohana::$charset, FALSE);

					// Change the option to the HTML string
					$options[$value] = '<option'.html::attributes($option).'>'.$title.'</option>';
				}
			}

			// Compile the options into a single string
			$options = "\n".implode("\n", $options)."\n";
		}

		return '<select'.html::attributes($attributes).'>'.$options.'</select>';
	}

	/**
	 * Creates a submit form input.
	 *
	 * @param   string  input name
	 * @param   string  input value
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function submit($name, $value, array $attributes = NULL)
	{
		$attributes['type'] = 'submit';

		return form::input($name, $value, $attributes);
	}

	/**
	 * Creates a button form input. Note that the body of a button is NOT escaped,
	 * to allow images and other HTML to be used.
	 *
	 * @param   string  input name
	 * @param   string  input value
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function button($name, $body, array $attributes = NULL)
	{
		// Set the input name
		$attributes['name'] = $name;

		return '<button'.html::attributes($attributes).'>'.$body.'</button>';
	}

	/**
	 * Creates a form label.
	 * 
	 * @param   string  target input
	 * @param   string  label text
	 * @param   array   html attributes
	 * @return  string
	 */
	public static function label($input, $text = NULL, array $attributes = NULL)
	{
		if ($text === NULL)
		{
			// Use the input name as the text
			$text = $input;
		}

		// Set the label target
		$attributes['for'] = $input;

		return '<label'.html::attributes($attributes).'>'.$text.'</label>';
	}

} // End form
