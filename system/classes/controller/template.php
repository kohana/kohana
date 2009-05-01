<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Template_Core extends Controller {

	/**
	 * @var  string  page template
	 */
	public $template = 'template';

	/**
	 * Loads the template View object.
	 *
	 * @return  void
	 */
	public function before()
	{
		$this->template = View::factory($this->template);
	}

	/**
	 * Assigns the template as the request response.
	 *
	 * @param   string   request method
	 * @return  void
	 */
	public function after()
	{
		// Assigns the template as the request response
		$this->request->response = $this->template;
	}

} // End Controller_Template