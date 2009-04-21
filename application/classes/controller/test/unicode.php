<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Test_Unicode extends Controller_Test {

	public function action_strings()
	{
		$this->_cases = array(
			'strlen'     => '¿se√en?',
			'ord'        => '€',
			'strtolower' => 'ÇÂPÎTÅL ÉÑD',
			'ucwords'    => 'åñø†hêr ñamê',
			'strrev'     => '¡üñiçø∂é õ√érl•a∂!');
	}

	public function action_translate()
	{
		$this->_cases = array(
			'en_US' => 'English',
			'fr_FR' => 'French',
			'es_ES' => 'Spanish');
	}

} // End Test_Unicode