<?php

namespace DeployCenter;

/**
 * Base module.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
abstract class BaseModule implements \DeployCenter\IModule
{

	/**
	 * @var string
	 */
	protected $name = "";


	/**
	 * @var \Nette\Templating\FileTemplate
	 */
	protected $template;


	/**
	 * @var \DeployCenter\Application
	 */
	protected $application;


	/**
	 * Get name of module.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @param \Nette\Templating\FileTemplate $template
	 */
	public function setTemplate(\Nette\Templating\FileTemplate $template)
	{
		$this->template = $template;
	}


	/**
	 * @param Application $application
	 * @param string $handle
	 * @param array $params
	 */
	public function run(Application $application, $handle, array $params)
	{
		$this->application = $application;

		if (empty($handle)) {
			$handle = "default";
		}

		$method = "handle" . ucfirst($handle);
		if (method_exists($this, $method)) {
			$this->{$method}($params);
		} else {
			$application->sendNotFound("Handle with name '$handle' not found '{$this->getName()}'.");
		}
	}


	/**
	 * @return null
	 */
	public function getApiData(Application $application)
	{
		return NULL;
	}

}
