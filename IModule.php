<?php

namespace DeployCenter;

/**
 * Interface for all modules.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
interface IModule
{


	/**
	 * Set template.
	 * @param \Nette\Templating\FileTemplate $template
	 * @return void
	 */
	public function setTemplate(\Nette\Templating\FileTemplate $template);


	/**
	 * Get name of module.
	 * @return string
	 */
	public function getName();


	/**
	 * Run module.
	 * @param Application $application
	 * @param string $handle
	 * @param array $params
	 * @return void
	 */
	public function run(Application $application, $handle, array $params);


	/**
	 * Get data for API.
	 * @return null|array
	 */
	public function getApiData(Application $application);

}
