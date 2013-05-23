<?php

namespace DeployCenter\Maintenance;

/**
 * Support for maintenance information.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class Maintenance extends \Nette\Object
{

	/**
	 * Filename for storage file
	 */
	const FILENAME = "deploy-center-maintenance";


	/**
	 * Max oldest time for acceptable
	 */
	const SECURE_ZONE_INTERVAL = "P1D";


	/**
	 * @var string
	 */
	protected $tempDir;


	/**
	 * @var \Nette\DateTime
	 */
	protected $start = NULL;


	/**
	 * @var bool
	 */
	protected $safe = FALSE;


	/**
	 * @param string $tempDir
	 */
	public function __construct($tempDir)
	{
		$this->tempDir = $tempDir;

		if (file_exists($this->tempDir . "/" . self::FILENAME)) {
			try {
				$content = unserialize(file_get_contents($tempDir . "/" . self::FILENAME));
				if (isset($content["start"]) && isset($content["safe"])) {
					if ($content["start"] instanceof \DateTime) {
						$this->start = $content["start"];
					}
					$this->safe = $content["safe"];
				}
			} catch (\Exception $e) {
			}
		}
	}


	/**
	 * Remove maintenance.
	 * @return Maintenance
	 */
	public function remove()
	{
		@unlink($this->tempDir . "/" . self::FILENAME);
		$this->start = NULL;
		return $this;
	}


	/**
	 * Return if deployment is in process.
	 * @return bool
	 */
	public function inProcess()
	{
		if ($this->start instanceof \DateTime) {
			if ($this->start <= new \DateTime())
				return $this->inSecureZone($this->start) ? TRUE : FALSE;
		}
		return FALSE;
	}


	/**
	 * Return start time of deployment.
	 * @return \DateTime|NULL
	 */
	public function getStart()
	{
		return $this->inSecureZone($this->start) ? $this->start : NULL;
	}


	/**
	 * Return if planning deployment is in safe mode.
	 * @return bool
	 */
	public function inSafeMode()
	{
		return $this->safe;
	}


	/**
	 * Set start time of deployment.
	 * @param \DateTime $start
	 * @param boolean $safe In safe mode
	 * @return Maintenance
	 */
	public function setStart(\DateTime $start, $safe = FALSE)
	{
		$data = array("start" => $start, "safe" => $safe);
		file_put_contents($this->tempDir . "/" . self::FILENAME, serialize($data));
		$this->start = $start;
		$this->safe = $safe;
		return $this;
	}


	/**
	 * Check if time is too old
	 * @param \DateTime $date
	 * @return bool
	 */
	public function inSecureZone(\DateTime $date = NULL)
	{
		if ($date === NULL) return FALSE;

		$now = new \DateTime();
		$now->sub(new \DateInterval(self::SECURE_ZONE_INTERVAL));
		if ($now < $date) return TRUE;
		return FALSE;
	}



}
