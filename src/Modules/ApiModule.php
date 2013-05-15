<?php

namespace DeployCenter\Modules;

/**
 * Module for API support.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class ApiModule extends \DeployCenter\BaseModule
{

	/**
	 * @var string
	 */
	protected $name = "API";


	/**
	 * API
	 * @param array $params
	 */
	public function run(\DeployCenter\Application $application, $handle, array $params)
	{
		header("Content-Type: application/xml"); // TODO: Bad but simple..

		$output = array();
		foreach ($application->getModules() as $module) {
			$output[$this->getModuleName($module)] = $module->getApiData($application);
		}

		$this->template->data = $output;
	}


	/**
	 * Get name of module.
	 * @param \DeployCenter\IModule $module
	 * @return string
	 */
	protected function getModuleName(\DeployCenter\IModule $module)
	{
		$className = get_class($module);
		$pos = strrpos($className, "\\");
		if ($pos) {
			return substr($className, $pos + 1);
		}
		return $className;
	}

}
