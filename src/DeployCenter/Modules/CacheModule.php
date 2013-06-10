<?php

namespace DeployCenter\Modules;

/**
 * Module for cache cleaning
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class CacheModule extends \DeployCenter\BaseModule
{

	/**
	 * @var string
	 */
	protected $name = "Cache";


	/**
	 * @var string
	 */
	protected $tempDir;


	/**
	 * @param string $tempDir
	 */
	public function __construct($tempDir)
	{
		$this->tempDir = $tempDir;
	}


	/**
	 * List of cache folder
	 * @param array $params
	 */
	public function handleDefault(array $params)
	{
		$this->template->folders = $this->getFolderTable();

		if (isset($params["cleared"])) {
			$this->template->cleared = $params["cleared"];
		}
	}


	/**
	 * Clear cache folder
	 * @param array $params
	 */
	public function handleClear(array $params)
	{
		if (empty($params["dir"])) {
			$this->application->sendNotFound("Dir name cannot be empty.");
		} else {
			$filename = $params["dir"];
		}

		$path = $this->tempDir . "/" . $filename;
		if (!is_dir($path)) {
			$this->application->sendNotFound("Invalid path to directory - $path.");
		}

		$i = 0;
		foreach (\Nette\Utils\Finder::findFiles("*")->from($path) as $filename => $file) {
			$i++;
			chmod($file, 0777);
			unlink($file);
		}

		$this->application->redirect("Cache:default", array("cleared" => $i));
	}


	/**
	 * Get data for API.
	 * @return array
	 */
	public function getApiData(\DeployCenter\Application $application)
	{
		$this->application = $application;
		return $this->getFolderTable();
	}


	/**
	 * @return array
	 */
	protected function getFolderTable()
	{
		$output = array();
		foreach ($this->getFolders() as $dir) {
			/** @var \SplFileInfo $dir */
			$output[] = array("name" => $dir->getBasename(),
						      "clear" => $this->application->link("Cache:clear", array("dir" => $dir->getBasename())));
		}
		return $output;
	}


	/**
	 * @return \Nette\Utils\Finder
	 */
	protected function getFolders()
	{
		return \Nette\Utils\Finder::findDirectories("*")->in($this->tempDir);
	}

}