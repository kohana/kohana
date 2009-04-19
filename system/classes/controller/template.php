<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Template_Core extends Controller {

	/**
	 * @var  string  page template
	 */
	public $template = 'template';

	/**
	 * Loads the template View object.
	 *
	 * @param   string   request method
	 * @return  void
	 */
	public function before($method)
	{
		$this->template = View::factory($this->template);
	}

	/**
	 * Assigns the template as the request response.
	 *
	 * @param   string   request method
	 * @return  void
	 */
	public function after($method)
	{
		// Assigns the template as the request response
		$this->request->response = $this->template;
	}

} // End Controller_Template