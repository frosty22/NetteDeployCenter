<?php

namespace DeployCenter\Modules;

/**
 * List exceptions and read error.log
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class ExceptionsModule extends \DeployCenter\BaseModule
{


	/**
	 * Mask of files with exceptions
	 */
	const FILE_MASK = "exception-*.html";


	/**
	 * Name of error log file
	 */
	const ERROR_LOG = "error.log";


	/**
	 * Date format in error log.
	 */
	const DATETIME_FORMAT = "Y-m-d H-i-s";


	/**
	 * Max read lines from errorLog
	 */
	const MAX_READLINE = 5000;


	/**
	 * @var string
	 */
	protected $name = "Vyjímky";


	/**
	 * @var string
	 */
	protected $logDir;


	/**
	 * @var bool
	 */
	protected $parseErrorLog;


	/**
	 * @var bool
	 */
	protected $parsedCompleteErrorLog = FALSE;


	/**
	 * @param string $logDir
	 * @param bool $parseErrorLog
	 */
	public function __construct($logDir, $parseErrorLog = TRUE)
	{
		$this->logDir = $logDir;
		$this->parseErrorLog = $parseErrorLog;
	}


	/**
	 * Default list exceptions
	 * @param array $params
	 */
	public function handleDefault(array $params)
	{
		$this->template->exceptions = $this->getExceptions();
		$this->template->parsedErrorLog = $this->parseErrorLog;
		$this->template->parsedCompleteErrorLog = $this->parsedCompleteErrorLog;
	}


	/**
	 * Detail
	 * @param array $params
	 */
	public function handleDetail(array $params)
	{
		if (empty($params["filename"])) {
			$this->application->sendNotFound("Exception with name {$params["filename"]} not found.");
		} else {
			$filename = $params["filename"];
		}

		$path = $this->checkFilename($filename);
		$this->application->sendResponse(file_get_contents($path));
	}


	/**
	 * Mark as resolved
	 * @param array $params
	 */
	public function handleResolved(array $params)
	{
		if (empty($params["filename"])) {
			$this->application->sendNotFound("Exception name cannot be empty.");
		} else {
			$filename = $params["filename"];
		}

		$path = $this->checkFilename($filename);

		$newname = date('Y-m-d-H-i-s') . "-" . md5($filename) . ".resolved";
		rename($path, $this->logDir . "/" . $newname);

		$this->application->redirect("Exceptions:default");
	}


	/**
	 * Get data for API.
	 * @return array
	 */
	public function getApiData(\DeployCenter\Application $application)
	{
		$this->application = $application;
		return $this->getExceptions();
	}


	/**
	 * Get list of exceptions.
	 * @return array
	 */
	protected function getExceptions()
	{
		$exceptions = array();

		if ($this->parseErrorLog) {
			$parsed = $this->parseErrorLog();
		}

		$files = \Nette\Utils\Finder::findFiles(self::FILE_MASK)->in($this->logDir);
		foreach ($files as $filename => $fileinfo) {
			/** @var \SplFileInfo $fileinfo */
			$exceptions[] = array(
				"created" => $fileinfo->getCTime(),
				"name" => $fileinfo->getBasename(),
				"url" => $this->application->link("Exceptions:detail", array("filename" => $fileinfo->getBasename())),
				"resolved" => $this->application->link("Exceptions:resolved", array("filename" => $fileinfo->getBasename())),
				"info" => isset($parsed[$fileinfo->getBaseName()]) ? $parsed[$fileinfo->getBasename()]["info"] : NULL,
				"count" => isset($parsed[$fileinfo->getBasename()]) ? $parsed[$fileinfo->getBasename()]["count"] : 0,
				"first" => isset($parsed[$fileinfo->getBasename()]) ?
								\DateTime::createFromFormat(self::DATETIME_FORMAT, $parsed[$fileinfo->getBasename()]["first"]) : NULL,
				"last" => isset($parsed[$fileinfo->getBasename()]) ?
								\DateTime::createFromFormat(self::DATETIME_FORMAT, $parsed[$fileinfo->getBasename()]["last"]) : NULL,
			);
		}

		return $exceptions;
	}


	/**
	 * Parse error log.
	 * @return array
	 */
	protected function parseErrorLog()
	{
		$filename = $this->logDir . "/" . self::ERROR_LOG;
		if (!file_exists($filename)) {
			$this->parseErrorLog = FALSE;
			return array();
		}

		$mask = $this->getRegexpFileMask();
		$fp = fopen($filename, "r");
		$output = array();
		$breaked = FALSE;
		$i = 0;
		while (!feof($fp)) {
			$line = fgets($fp, 1024);
			if (preg_match("~^\[(.*?)\](.*?)\@\@  ({$mask})$~", $line, $matches)) {
				if (isset($output[$matches[3]])) {
					$output[$matches[3]]["count"] = $output[$matches[3]]["count"] + 1;
					$output[$matches[3]]["last"] = $matches[1];
				} else {
					$output[$matches[3]] = array(
						"count" => 1,
						"first" => $matches[1],
						"last" => $matches[1],
						"info" => $matches[2]
					);
				}
			}

			$i++;
			if ($i === self::MAX_READLINE) {
				$breaked = TRUE;
				break;
			}
		}

		fclose($fp);
		if (!$breaked) $this->parsedCompleteErrorLog = TRUE;

		return $output;
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
	 * Check if filename is correct.
	 * @param string $filename
	 * @return string
	 */
	protected function checkFilename($filename)
	{
		$mask = $this->getRegexpFileMask();
		if (!\Nette\Utils\Strings::match($filename, "~^{$mask}$~")) {
			$this->application->sendNotFound("Invalid exception $filename name.");
		}

		$path = $this->logDir . "/" . $filename;
		if (!file_exists($path)) {
			$this->application->sendNotFound("Exception with name $path not found.");
		}

		return $path;
	}
}
