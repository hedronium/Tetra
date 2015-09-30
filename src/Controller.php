<?php
namespace hedronium\Tetra;

abstract class Controller 
{
	private $app = null;

	public function __construct($app)
	{
		$this->app = $app;
	}
}