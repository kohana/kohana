<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Test_Cookie extends Controller_Test {

	public function action_set()
	{
		$value = array(
			'time' => $time = microtime(TRUE),
			'md5'  => md5($time),
			'uid'  => uniqid());

		$this->_cases['kohana'] = $value;

		foreach ($this->_cases as $name => $value)
		{
			// Set the cookie
			cookie::set($name, serialize($value));
		}
	}

	public function action_get()
	{
		$this->_cases['kohana'] = unserialize(cookie::get('kohana'));
	}

} // End Welcome Controller
