<?php

namespace OpenShareFile\Extension\Twig;

use OpenShareFile\Core\Config;

class Asset extends \Twig_Extension
{
	public function getFunctions()
	{
		return array(
			'asset' => new \Twig_Function_Method($this, 'assetFunction'),
			'img' => new \Twig_Function_Method($this, 'imageFunction'),
			'css' => new \Twig_Function_Method($this, 'stylesheetFunction'),
			'js' => new \Twig_Function_Method($this, 'javascriptFunction'),
		);
	}

	public function getName()
	{
		return __CLASS__;
	}


	public function assetFunction($v)
	{
		return $this->getBaseUrl().'/'.$v;
	}

	public function imageFunction($v)
	{
		if (preg_match('#^https?::/#', $v)) {
			return $v;
		}

		return $this->assetFunction('img/'.$v);
	}

	public function stylesheetFunction($v)
	{
		if (preg_match('#^https?::/#', $v)) {
			return $v;
		}

		return $this->assetFunction('css/'.$v);	
	}

	public function javascriptFunction($v)
	{
		if (preg_match('#^https?::/#', $v)) {
			return $v;
		}

		return $this->assetFunction('js/'.$v);
	}

	private function getBaseUrl()
	{
		$root = $_SERVER['DOCUMENT_ROOT'];
		$current = dirname($_SERVER['SCRIPT_FILENAME']);

		return str_replace(array($root, DIRECTORY_SEPARATOR), array('', '/'), $current).'/themes/'.Config::get('theme');
	}
}