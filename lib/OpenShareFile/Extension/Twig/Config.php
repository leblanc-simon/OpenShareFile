<?php

namespace OpenShareFile\Extension\Twig;

use OpenShareFile\Core\Config as CoreConfig;

class Config extends \Twig_Extension
{
	public function getFunctions()
	{
		return array(
			'get_config' => new \Twig_Function_Method($this, 'getConfigFunction'),
		);
	}

	public function getName()
	{
		return __CLASS__;
	}


	public function getConfigFunction($v)
	{
		return CoreConfig::get($v);
	}
}