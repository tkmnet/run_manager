<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\system\Config;
use \PDO;

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
			$db = BaseManager::connectDB();
			$db->beginTransaction();
			$c = 0;
			foreach ($pendingRuns as $run) {
				BaseManager::postRunToOACIS($run["name"], $db);
				if  (++$c >= 10) {
				    break;
				}
			}
			$db->commit();
			if (count(BaseManager::getPendingRunList($params[1], 0, -1)) > 0) {
                header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[1].'/runlist/postall');
            } else {
                header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[1].'/runlist');
            }
			return;
        } elseif ($cmd === "update_score") {
		    $base = BaseManager::getBase($params[1]);
            $db = BaseManager::connectDB();
            $sth = $db->prepare("select name from run where base=:base and (state=2 or score=-1);");
            $sth->bindValue(':base', $base["id"], PDO::PARAM_INT);
            $sth->execute();
            $runNames = [];
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $runNames[] = $row["name"];
            }
            $c = 0;
            foreach ($runNames as $runName) {
                BaseManager::updateScore($runName);
                if (++$c >= 30) {
                    header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[1].'/runlist/update_score');
                    return;
                }
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
			header('Content-Disposition: attachment; filename="'.BaseManager::getBase($params[1])["alias"].'('.$params[1].').csv"');
			//header('Content-Length: ' . (strlen(bin2hex($output))/2));
			if (count($params) == 2) {
				BaseManager::printResultCsv($params[1]);
			}
			return;
		} elseif ($cmd === "addtrial") {
			BaseManager::addTrial($params[1]);
			header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[1].'');
			return;
        } elseif ($cmd === "rename") {
            BaseManager::updateAliasName($params[1], $_POST["alias"]);
            header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[1].'');
            return;
		}

		header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager');
	}
}
