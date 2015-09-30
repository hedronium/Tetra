<?php
namespace hedronium\Tetra;

use Slim\Slim;

class Tetra extends Slim
{
	private function resolveAction($action) 
	{
		if (is_string($action)) {
			list($class, $method) = explode('@', $action);

			if (empty($class)) {
				throw new \Exception("No Class Specified.");
			}

			if (empty($method)) {
				throw new \Exception("No Method Specified.");
			}

			$depends = $class::$depends;
			$depends = array_flip($depends);

			foreach ($depends as $key => &$value) {
				$value = $this->container->get($key);
			}

			$obj = new $class($depends);

			$obj->setApp($this);

			return function() use ($obj, $method) {
				call_user_func_array([
					$obj,
					$method
				], func_get_args());
			};
		}

		return $action;
	}

	public function get($route, $action) 
	{
		$action = $this->resolveAction($action);
		parent::get($route, $action);
	}

	public function post($route, $action) 
	{
		$action = $this->resolveAction($action);
		parent::post($route, $action);
	}

	public function render($template, $data = [])
	{
		$template.= '.twig';
		parent::render($template, $data);
	}

	public function listen($event, $handler)
	{
		$this->hook(
			'user.'.$event,
			$this->resolveAction($handler)
		);
	}

	public function trigger($event)
	{
		$this->applyHook(
			'user.'.$event,
			$this->resolveAction($handler)
		);
	}

	public static function boot($config) {
		$config = require $config;

		$config['view'] = new \Slim\Views\Twig();


		$app = new static($config);


		$view = $app->view();
		$view->parserOptions = array(
			'debug' => $config['debug'],
			'cache' => $config['templates.cache_path']
		);

		require $config['routes_file'];

		App::setApp($app);
	}
}