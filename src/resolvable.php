<?php
namespace hedronium\Tetra;

trait resolvable {
	public static $depends = [];

	protected $app = null;

	public function setApp(Tetra $app) 
	{
		$this->app = $app;
	}
}