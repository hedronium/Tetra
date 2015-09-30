<?php
namespace hedronium\Tetra;

class App {
	private static $app = null;

	public static function setApp($app)
	{
		if (static::$app === null) {
			static::$app = $app;
		} 
	}

	public static function getInstance()
	{
		return static::$app;
	}

	public static function __callStatic($name, $arguments)
	{
		call_user_func_array([
			static::$app,
			$name
		], $arguments);
	}
}