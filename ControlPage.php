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

		if ($cmd === "post") {
			$run = BaseManager::getRun($params[1]);
			if ($run != null) {
				BaseManager::postRunToOACIS($params[1]);

				header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$run["baseName"].'/runlist');
				return;
			}
		} elseif ($cmd === "postall") {
			$pendingRuns = BaseManager::getPendingRunList($params[1], 0, -1);
			foreach ($pendingRuns as $run) {
				BaseManager::postRunToOACIS($run["name"]);
			}
			header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[1].'/runlist');
			return;
		} elseif ($cmd === "duplicate_base") {
			$newName = BaseManager::duplicateBase($params[1]);
			header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$newName);
			return;
		} elseif ($cmd === "getcsv") {
			error_reporting(0);
			//header('Content-Type: application/zip; name="' . $zipFileName . '"');
			header('Content-Type: text/csv;');
			//header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
			//header('Content-Length: ' . (strlen(bin2hex($output))/2));
			if (count($params) == 2) {
				echo BaseManager::getResultCsv($params[1]);
			}
			return;
		}

		header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager');
	}
}
