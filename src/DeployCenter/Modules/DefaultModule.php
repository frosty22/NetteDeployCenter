<?php

namespace DeployCenter\Modules;

/**
 * Default module.
 *
 * @copyright: Copyright (c) 2013 Ledvinka Vít
 * @author: Ledvinka Vít, frosty22 <ledvinka.vit@gmail.com>
 *
 */
class DefaultModule extends \DeployCenter\BaseModule
{

	/**
	 * @var string
	 */
	protected $name = "Úvod";


	/**
	 * @var array
	 */
	protected $modules = array(
		"default" => "Modul, který zřizuje tuto úvodní stránku.",
		"exceptions" => "Modul poskytující přehled všech vyhozených vyjímek, zároveň detekující jejich četnost dle error logu.",
		"log" => "Modul pro přehled všech log souborů s možností jejich zobrazení a odstranění.",
		"api" => "Modul poskytující přístup ke všem datům z ostatních modulů pomocí XML exportního souboru.",
		"maintenance" => "Module pro kontrolu stavu údržby - spouštění / ukončování a plánování vystavení"
	);


	/**
	 * Default handle.
	 * @param array $params
	 */
	public function handleDefault(array $params)
	{
		$this->template->list = $this->modules;
	}


}
