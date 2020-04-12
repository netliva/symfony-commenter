<?php

namespace Netliva\XxxBundle\Services;


class ExampleServices extends \Twig_Extension
{
	protected $em;
	public function __construct($em){
		$this->em = $em;
	}

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('example', [$this, 'example']),
		);
	}


	public function example($key)
	{
		return $key;
	}


}
