<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Template_Core extends Controller {

	/**
	 * @var  string  page template
	 */
	public $template = 'template';
	
	/**
	 * @var bool auto render template
	 **/
	public $auto_render = TRUE;

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
		if ($this->auto_render === TRUE)
		{
			// Assign the template as the request response and render it
			$this->request->response = $this->template->render();
		}
	}

} // End Controller_Template