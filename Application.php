<?php

namespace DeployCenter;
/*
require_once __DIR__ . "/IModule.php";
require_once __DIR__ . "/BaseModule.php";
require_once __DIR__ . "/Modules/DefaultModule.php";
require_once __DIR__ . "/Modules/ExceptionsModule.php";
require_once __DIR__ . "/Modules/ApiModule.php";
require_once __DIR__ . "/Modules/LogModule.php";
*/
/**
 * Application of deployment center.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class Application extends \Nette\Object
{

	/**
	 * Name of param for action.
	 */
	const ACTION_KEY = "action";


	/**
	 * Name of param for handle.
	 */
	const HANDLE_KEY = "handle";


	/**
	 * Path to log dir.
	 * @var string
	 */
	public $logDir;


	/**
	 * Password of application.
	 * @var string
	 */
	protected $password;


	/**
	 * Path to temp directory
	 * @var string
	 */
	protected $tempDir;


	/**
	 * Dir with template.
	 * @var string
	 */
	protected $templateDir;


	/**
	 * Modules
	 * @var IModule
	 */
	protected $modules = array();


	/**
	 * Name of run param.
	 * @var string
	 */
	protected $runParam;


	/**
	 * @var \Nette\Templating\FileTemplate
	 */
	protected $template;


	/**
	 * @param string $logDir Path to log directory
	 * @param string $tempDir Path to temp directory
	 * @param string $password Password for access
	 */
	public function __construct($logDir, $tempDir, $password)
	{
		$this->logDir = $logDir;
		$this->tempDir = $tempDir;
		$this->password = $password;
		$this->templateDir = __DIR__ . "/templates";

		$this->addModule("default", function() { return new \DeployCenter\Modules\DefaultModule(); });

		$this->addModule("exceptions", function(Application $application)
		{ return new \DeployCenter\Modules\ExceptionsModule($application->logDir); });

		$this->addModule("log", function(Application $application) { return new \DeployCenter\Modules\LogModule($application->logDir); });

		$this->addModule("api", function() { return new \DeployCenter\Modules\ApiModule(); });
	}


	/**
	 * Register application on param.
	 * @param string $param Name of GET param
	 * @return bool
	 */
	public function register($param)
	{
		$this->runParam = $param;

		if (isset($_GET[$param]) && ($_GET[$param] === $this->password)) {
			$this->run();
		}
		return FALSE;
	}


	/**
	 * Add module.
	 * @param string $action Name of action
	 * @param IModule|callback $module
	 * @return Application
	 */
	public function addModule($action, $module)
	{
		if (!$module instanceof IModule && !is_callable($module)) {
			throw new \InvalidArgumentException("Module '$action' must be callback or IModule.");
		}

		$this->modules[$action] = $module;
		return $this;
	}


	/**
	 * Remove module.
	 * @param string $action
	 * @return Application
	 */
	public function removeModule($action)
	{
		unset($this->modules[$action]);
		return $this;
	}


	/**
	 * @return IModule[]
	 */
	public function getModules()
	{
		foreach ($this->modules as $action => $module) {
			if (is_callable($module))
				$this->modules[$action] = call_user_func($module, $this);
		}
		return $this->modules;
	}


	/**
	 * Return if exist module
	 * @param string $action
	 * @return bool
	 */
	public function hasModule($action)
	{
		return isset($this->modules);
	}


	/**
	 * Get module.
	 * @param string $action
	 * @return IModule|null
	 */
	protected function getModule($action)
	{
		if (!isset($this->modules[$action])) {
			return NULL;
		}

		if (is_callable($this->modules[$action])) {
			$this->modules[$action] = call_user_func($this->modules[$action], $this);
		}

		return $this->modules[$action];
	}


	/**
	 * Run application.
	 */
	protected function run()
	{
		date_default_timezone_set("Europe/Prague"); // TODO: Better solution!

		$action = isset($_GET[self::ACTION_KEY]) ? $_GET[self::ACTION_KEY] : "default";

		$this->template = new \Nette\Templating\FileTemplate();
		$this->template->registerFilter(new \Nette\Latte\Engine());
		$this->template->registerHelperLoader("\Nette\Templating\Helpers::loader");
		$this->template->registerHelper("link", array($this, "link"));
		$this->template->modules = $this->getModules();

		if ($module = $this->getModule($action)) {

			$handle = isset($_GET[self::HANDLE_KEY]) ? $_GET[self::HANDLE_KEY] : NULL;

			$module->setTemplate($this->template);
			$module->run($this, $handle, $this->getParams());

			if (!$this->template->getFile()) {

				$defaultTemplate = $this->templateDir . "/{$action}.latte";
				if (!file_exists($defaultTemplate)) {
					$this->sendNotFound("Template for '{$module->getName()}' not found.");
				} else {
					$this->template->setFile($defaultTemplate);
				}

			}

		} else {
			$this->sendNotFound("Module '{$action}' not found.");
		}

		$this->sendResponse();
	}


	/**
	 * Link to action of module.
	 * @param string $link
	 * @param array $params
	 */
	public function link($link, array $params = array())
	{
		$link = strtolower($link);

		$dlm = strpos($link, ":");
		if ($dlm) {
			$action = substr($link, 0, $dlm);
			$handle = substr($link, $dlm + 1);
		} else {
			$action = $link;
			$handle = NULL;
		}

		$baseParams = array(
			self::ACTION_KEY => $action,
			self::HANDLE_KEY => $handle,
			$this->runParam => $this->password,
		);
		$params = array_merge($params, $baseParams);
		return $this->getUrl() . "?" . http_build_query($params);
	}


	/**
	 * Get current URL without query string
	 * @return string
	 */
	protected function getUrl()
	{
		$protocol = empty($_SERVER['HTTPS']) ? "http://" : "https://";
		return $protocol . $_SERVER['SERVER_NAME'] . strtok($_SERVER["REQUEST_URI"], '?');
	}


	/**
	 * Send not found response.
	 * @param null|string $message
	 */
	public function sendNotFound($message = NULL)
	{
		$this->template->setFile($this->getNotFound());
		if ($message) $this->template->error = $message;
		$this->sendResponse();
	}


	/**
	 * Send template.
	 */
	public function sendResponse($output = NULL)
	{
		if ($output) {
			echo $output;
		} else {
			$this->template->render();
		}

		exit; // Bad solution but why not...
	}


	/**
	 * Redirect to link
	 * @param string $link
	 * @param array $params
	 */
	public function redirect($link, array $params = array())
	{
		$url = $this->link($link, $params);
		header("Location: " . $url); // TODO: Bad solution, but why not ..
		exit;
	}

	/**
	 * Return params
	 * @return array
	 */
	protected function getParams()
	{
		// TODO: Better solution
		return $_GET;
	}


	/**
	 * Get path to not found template.
	 * @return string
	 */
	protected function getNotFound()
	{
		return $this->templateDir . "/404.latte";
	}


}
