<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Reqs extends Controller {

	public function action_main()
	{
		echo '<p>Is this request the main request?</p>', Kohana::debug($this->request === Request::$instance);

		echo '<p>What is the URI of the main request?</p>', Kohana::debug(Request::$instance->uri);

		echo '<p>What is the URI of this request?</p>', Kohana::debug($this->request->uri);
	}

	public function action_globals()
	{
		echo '<p>What is the request method?</p>', Kohana::debug(Request::$method);

		echo '<p>What is the request referrer?</p>', Kohana::debug(Request::$referrer);
		
		echo '<p>Is this an AJAX request?</p>', Kohana::debug(Request::$is_ajax);
	}

}
