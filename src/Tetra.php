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

			return function() use ($class, $method) {
				$obj = new $class($this);

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

	public static function getDefaultSettings()
	{
		$original = parent::getDefaultSettings();

		return array_merge($original, [
			'container' => new Container
		]);
	}

	public function __construct(array $userSettings = array())
	{
		// Setup IoC container
		$settings = array_merge(static::getDefaultSettings(), $userSettings);
		$this->container = new $settings['container'];
		$this->container['settings'] = $settings;

		// Default environment
		$this->container->singleton('environment', function ($c) {
			return \Slim\Environment::getInstance();
		});

		// Default request
		$this->container->singleton('request', function ($c) {
			return new \Slim\Http\Request($c['environment']);
		});

		// Default response
		$this->container->singleton('response', function ($c) {
			return new \Slim\Http\Response();
		});

		// Default router
		$this->container->singleton('router', function ($c) {
			return new \Slim\Router();
		});

		// Default view
		$this->container->singleton('view', function ($c) {
			$viewClass = $c['settings']['view'];
			$templatesPath = $c['settings']['templates.path'];

			$view = ($viewClass instanceOf \Slim\View) ? $viewClass : new $viewClass;
			$view->setTemplatesDirectory($templatesPath);
			return $view;
		});

		// Default log writer
		$this->container->singleton('logWriter', function ($c) {
			$logWriter = $c['settings']['log.writer'];

			return is_object($logWriter) ? $logWriter : new \Slim\LogWriter($c['environment']['slim.errors']);
		});

		// Default log
		$this->container->singleton('log', function ($c) {
			$log = new \Slim\Log($c['logWriter']);
			$log->setEnabled($c['settings']['log.enabled']);
			$log->setLevel($c['settings']['log.level']);
			$env = $c['environment'];
			$env['slim.log'] = $log;

			return $log;
		});

		// Default mode
		$this->container['mode'] = function ($c) {
			$mode = $c['settings']['mode'];

			if (isset($_ENV['SLIM_MODE'])) {
				$mode = $_ENV['SLIM_MODE'];
			} else {
				$envMode = getenv('SLIM_MODE');
				if ($envMode !== false) {
					$mode = $envMode;
				}
			}

			return $mode;
		};

		// Define default middleware stack
		$this->middleware = array($this);
		$this->add(new \Slim\Middleware\Flash());
		$this->add(new \Slim\Middleware\MethodOverride());

		// Make default if first instance
		if (is_null(static::getInstance())) {
			$this->setName('default');
		}
	}
}