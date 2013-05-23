<?php

namespace DeployCenter\Maintenance;

/**
 * Control for alert block on pages.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class AlertControl extends \Nette\Application\UI\Control
{

	/**
	 * Inverval when start message show before
	 */
	const ALERT_BEFORE_INTERVAL = "PT10M";


	/**
	 * @var Maintenance
	 */
	private $maintenance;


	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * @param Maintenance $maintenance
	 */
	public function setMaintenance(Maintenance $maintenance)
	{
		$this->maintenance = $maintenance;
	}


	/**
	 * Render maintenance alert block.
	 */
	public function render()
	{
		$this->template->setFile(__DIR__ . "/alertControl.latte");

		$this->template->isShow = $show = $this->isShow();
		if ($show) {
			$this->template->start = $this->maintenance->getStart();
		}

		$this->template->render();
	}


	/**
	 * Can show the message
	 * @return bool
	 */
	public function isShow()
	{
		if (!$this->maintenance) return FALSE;

		if ($this->maintenance->getStart() instanceof \DateTime) {
			$time = new \DateTime();
			$time->add(new \DateInterval(self::ALERT_BEFORE_INTERVAL));
			if ($time >= $this->maintenance->getStart()) {
				return TRUE;
			}
		}

		return FALSE;
	}

}
