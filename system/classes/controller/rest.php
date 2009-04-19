<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_REST extends Controller {

	protected $_action_map = array
	(
		'GET'    => 'index',
		'PUT'    => 'create',
		'POST'   => 'update',
		'DELETE' => 'delete',
	);

	protected $_action_requested = '';

	public function before($method)
	{
		$this->_action_requested = $this->request->action;

		if ( ! isset($this->_action_map[$method]))
		{
			$this->request->status = 405;
			$this->request->action = 'invalid';

			$this->request->set_header('Allow', implode(', ', array_keys($this->_action_map)));
		}
		else
		{
			$this->request->action = strtolower($method);
		}
	}

	abstract public function action_get();

	abstract public function action_put();

	abstract public function action_post();

	abstract public function action_delete();

	public function action_invalid()
	{
		// Do nothing
	}

} // End Name