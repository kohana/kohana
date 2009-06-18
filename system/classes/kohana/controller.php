<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Controller class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Kohana_Controller {

	/**
	 * @var  object  Request that created the controller
	 */
	public $request;

	/**
	 * Creates a new controller instance. Each controller must be constructed
	 * with the request object that created it.
	 *
	 * @param   object  Request that created the controller
	 * @return  void
	 */
	public function __construct(Request $request)
	{
		// Assign the request to the controller
		$this->request = $request;
	}

	/**
	 * Automatically executed before the controller action.
	 *
	 * @return  void
	 */
	public function before()
	{
		// Nothing by default
	}

	/**
	 * Automatically executed after the controller action.
	 *
	 * @return  void
	 */
	public function after()
	{
		// Nothing by default
	}

} // End Controller
