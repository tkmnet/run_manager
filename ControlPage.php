<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\system\Config;

class ControlPage extends AbstractPage
{
	public function controller($params)
	{
		$param_count = count($params);

		if ($param_count <= 0) {
			header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager');
			return;
		}

		$cmd = $params[0];

		if ($cmd === "reserve") {
			$run = BaseManager::getRun($params[1]);
			if ($run != null) {
				BaseManager::postRunToOACIS($params[1]);

				header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$run["baseName"].'/runlist');
				return;
			}
		}

		header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager');
	}
}
