<?php

namespace DeployCenter\Modules;

/**
 * Module for managing maintenance alert control and maintence page.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class MaintenanceModule extends \DeployCenter\BaseModule
{

	/**
	 * @var string
	 */
	protected $name = "Údržba";


	/**
	 * @var \DeployCenter\Maintenance\Maintenance
	 */
	protected $maintenance;


	/**
	 * @param \DeployCenter\Maintenance\Maintenance $maintenance
	 */
	public function __construct(\DeployCenter\Maintenance\Maintenance $maintenance)
	{
		$this->maintenance = $maintenance;
	}


	/**
	 * Default handle.
	 * @param array $params
	 */
	public function handleDefault(array $params)
	{
		if (isset($params["start"])) {

			if ($params["start"] == "") {
				$this->maintenance->remove();
			} else {
				try {
					$date = new \Nette\DateTime($params["start"]);
					$this->maintenance->setStart($date, isset($params["safe"]) ? TRUE : FALSE);
					if (!$this->maintenance->inSecureZone($date)) {
						$this->template->error = "Časový již není v akceptovatelné zóně - je příliš starý.";
					}
				} catch (\Exception $e) {
					$this->template->error = "Neplatný formát datum a času.";
				}
			}
		}

		$this->template->active = $this->maintenance->inProcess();
		$this->template->remove = $this->application->link("Maintenance:default", array("start" => ''));

		$this->template->safe = $this->maintenance->inSafeMode();
		$this->template->started = $this->maintenance->getStart();
		$this->template->start = $this->maintenance->getStart() ? $this->maintenance->getStart()->format("j.n.Y H:i") : NULL;
	}


}
