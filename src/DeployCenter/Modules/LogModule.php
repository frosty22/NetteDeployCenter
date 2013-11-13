<?php

namespace DeployCenter\Modules;

/**
 * Module for manage logs.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class LogModule extends \DeployCenter\BaseModule
{

	/**
	 * Log file mask
	 */
	const FILE_MASK = "*.log";


	/**
	 * @var string
	 */
	protected $name = "Log";


	/**
	 * @var string
	 */
	protected $logDir;


	/**
	 * @param string $logDir
	 */
	public function __construct($logDir)
	{
		$this->logDir = $logDir;
	}


	/**
	 * Default list exceptions
	 * @param array $params
	 */
	public function handleDefault(array $params)
	{
		$this->template->logs = $this->getListLogs();
	}


	/**
	 * Detail
	 * @param array $params
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleDetail(array $params)
	{
		if (empty($params["filename"])) {
			$this->application->sendNotFound("Exception with name {$params["filename"]} not found.");
		} else {
			$filename = $params["filename"];
		}

		if (!\Nette\Utils\Strings::match($filename, "~^{$this->getRegexpFileMask()}$~")) {
			$this->application->sendNotFound("Invalid exception $filename name.");
		}

		$filename = $this->logDir . "/" . $filename;
		if (!file_exists($filename)) {
			$this->application->sendNotFound("Exception with name $filename not found.");
		}

		$this->application->sendResponse(file_get_contents($filename), 'text/plain; charset=utf-8');
	}


	/**
	 * Remove log
	 * @param array $params
	 */
	public function handleRemove(array $params)
	{
		if (empty($params["filename"])) {
			$this->application->sendNotFound("Log name cannot be empty.");
		} else {
			$filename = $params["filename"];
		}

		$path = $this->checkFilename($filename);

		unlink($path);

		$this->application->redirect("Log:default");
	}


	/**
	 * Get list of logs.
	 * @return array
	 */
	protected function getListLogs()
	{
		$logs = array();

		$files = \Nette\Utils\Finder::findFiles(self::FILE_MASK)->in($this->logDir);
		foreach ($files as $filename => $fileinfo) {
			/** @var \SplFileInfo $fileinfo */
			$logs[] = array(
				"modified" => $fileinfo->getMTime(),
				"name" => $fileinfo->getBasename(),
				"url" => $this->application->link("Log:detail", array("filename" => $fileinfo->getBasename())),
				"remove" => $this->application->link("Log:remove", array("filename" => $fileinfo->getBasename())),
			);
		}

		return $logs;
	}


	/**
	 * Get file mask for regexp.
	 * @return string
	 */
	protected function getRegexpFileMask()
	{
		return str_replace(array(".", "*"), array("\.", ".*?"), self::FILE_MASK);
	}


	/**
	 * Get data for API.
	 * @return array
	 */
	public function getApiData(\DeployCenter\Application $application)
	{
		$this->application = $application;
		return $this->getListLogs();
	}


	/**
	 * Check if filename is correct.
	 * @param string $filename
	 * @return string
	 */
	protected function checkFilename($filename)
	{
		$mask = $this->getRegexpFileMask();
		if (!\Nette\Utils\Strings::match($filename, "~^{$mask}$~")) {
			$this->application->sendNotFound("Invalid log $filename name.");
		}

		$path = $this->logDir . "/" . $filename;
		if (!file_exists($path)) {
			$this->application->sendNotFound("Log with name $path not found.");
		}

		return $path;
	}


}
