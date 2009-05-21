<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Test_REST extends Controller_REST {

	public function action_get()
	{
		echo Kohana::debug(__METHOD__);
	}

	public function action_put()
	{
		echo Kohana::debug(__METHOD__);
	}

	public function action_post()
	{
		echo Kohana::debug(__METHOD__);
	}

	public function action_delete()
	{
		echo Kohana::debug(__METHOD__);
	}

} // End Pages Controller
