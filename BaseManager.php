<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\manager\AgentManager;
use rrsoacis\manager\ClusterManager;
use rrsoacis\manager\DatabaseManager;
use rrsoacis\manager\MapManager;
use \MongoClient;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use \PDO;
use rrsoacis\system\Config;
use rrsoacis\system\Agent;
use rrsoacis\exception\AgentNotFoundException;

class BaseManager
{
	public static function getBases()
	{
		$db = self::connectDB();
		$sth = $db->query("select name from base;");
		$bases = [];
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$bases[] = self::getBase($row["name"]);
		}
		return $bases;
	}

	public static function getBase($name)
	{
		$db = self::connectDB();
		$sth = $db->prepare("select * from base where name=:name;");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->execute();
		$base = null;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$base = $row;

			$base["parameters"] = [];
			$sth2 = $db->prepare("select * from parameter where base=:baseId;");
			$sth2->bindValue(':baseId', $base["id"], PDO::PARAM_INT);
			$sth2->execute();
			while ($row2 = $sth2->fetch(PDO::FETCH_ASSOC)) {
				$base["parameters"][] = $row2;
			}

			$base["replaceSets"] = [];
			$sth2 = $db->prepare("select * from replaceSets where base=:baseId;");
			$sth2->bindValue(':baseId', $base["id"], PDO::PARAM_INT);
			$sth2->execute();
			while ($row2 = $sth2->fetch(PDO::FETCH_ASSOC)) {
				$replaceSets = $row2;
				$sth3 = $db->prepare("select * from replaceSetsParameter where replaceSets=:setsId;");
				$sth3->bindValue(':setsId', $replaceSets["id"], PDO::PARAM_INT);
				$sth3->execute();
				$parameters = [];
				while ($row3 = $sth3->fetch(PDO::FETCH_ASSOC)) {
					foreach ($base["parameters"] as $parameter) {
						if ($parameter["id"] == $row3["parameter"]) {
							$parameters[] = $parameter;
							break;
						}
					}
				}
				$replaceSets["parameters"] = $parameters;

				$sth3 = $db->prepare("select * from replaceSet where replaceSets=:setsId;");
				$sth3->bindValue(':setsId', $replaceSets["id"], PDO::PARAM_INT);
				$sth3->execute();
				$replaceSetArray = [];
				while ($row3 = $sth3->fetch(PDO::FETCH_ASSOC)) {
					$replaceSet = $row3;
					$sth4 = $db->prepare("select * from replace where replaceSet=:setId;");
					$sth4->bindValue(':setId', $replaceSet["id"], PDO::PARAM_INT);
					$sth4->execute();
					$replaces = [];
					while ($row4 = $sth4->fetch(PDO::FETCH_ASSOC)) {
						$replaces[] = $row4;
					}
					$replaceSet["replace"] = $replaces;
					$replaceSetArray[] = $replaceSet;
				}
				$replaceSets["replaceSet"] = $replaceSetArray;

				$base["replaceSets"][] = $replaceSets;
			}
		}
		return $base;
	}

	public static function addBase($alias, $note, $map, $agent_a, $agent_f, $agent_p, $is_mod, $is_dev, $mods, $devs)
	{
		$tmpFileOut = '/tmp/rrsoacis-out-' . uniqid();
		$tmpFileIn = '/tmp/rrsoacis-in-' . uniqid();
		system("sudo -i -u oacis " . Config::$OACISCLI_PATH . " simulator_template -o " . $tmpFileOut . " 2>&1");
		$simulator = json_decode(file_get_contents($tmpFileOut), true);
		system("rm -f " . $tmpFileOut);
		$simulator['name'] = "RO_tkmnetRM_" . uniqid();
		$simulator['command'] = "/home/oacis/rrs-oacis/rrsenv/script/rrscluster";
		$simulator['executable_on_ids'][] = ClusterManager::getMainHostGroup();
		$simulator['support_input_json'] = true;

		$simulator['parameter_definitions'] = [];

		$parameter1 = [];
		$parameter1['key'] = 'MAP';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'AGENT_A';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'AGENT_F';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'AGENT_P';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'IS_MOD';
		$parameter1['type'] = 'Boolean';
		$parameter1['default'] = false;
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'IS_DEV';
		$parameter1['type'] = 'Boolean';
		$parameter1['default'] = false;
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		foreach ($mods as $mod) {
			$parameter1 = [];
			$parameter1['key'] = 'MOD_'.$mod[0];
			$parameter1['type'] = 'String';
			$parameter1['default'] = '';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;
		}

		foreach ($devs as $dev) {
			$parameter1 = [];
			$parameter1['key'] = 'DEV_'.$dev[0];
			$parameter1['type'] = 'String';
			$parameter1['default'] = '';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;
		}

		file_put_contents($tmpFileIn, json_encode($simulator));
		system("sudo -i -u oacis " . Config::$OACISCLI_PATH . " create_simulator -i " . $tmpFileIn . " -o " . $tmpFileOut);
		system("rm -f " . $tmpFileIn);
		$simulatorId = json_decode(file_get_contents($tmpFileOut), true)['simulator_id'];
		system("rm -f " . $tmpFileOut);

		$db = self::connectDB();
		$sth = $db->prepare("insert into base(name, alias, note) values(:name, :alias, :note);");
		$sth->bindValue(':name', $simulatorId, PDO::PARAM_STR);
		$sth->bindValue(':alias', $alias, PDO::PARAM_STR);
		$sth->bindValue(':note', $note, PDO::PARAM_STR);
		$sth->execute();

		$sth = $db->prepare("select id from base where name=:name;");
		$sth->bindValue(':name', $simulatorId, PDO::PARAM_STR);
		$sth->execute();
		$baseId = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$baseId = $row["id"];
		}

		self::addParameterToDB($db, $baseId, "MAP", $map);
		self::addParameterToDB($db, $baseId, "AGENT_A", $agent_a);
		self::addParameterToDB($db, $baseId, "AGENT_F", $agent_f);
		self::addParameterToDB($db, $baseId, "AGENT_P", $agent_p);
		self::addParameterToDB($db, $baseId, "IS_MOD", $is_mod);
		self::addParameterToDB($db, $baseId, "IS_DEV", $is_dev);

		foreach ($mods as $mod) {
			self::addParameterToDB($db, $baseId, "MOD_".$mod[0], $mod[1]);
		}

		foreach ($devs as $dev) {
			self::addParameterToDB($db, $baseId, "DEV_".$dev[0], $dev[1]);
		}
	}

	private static function addParameterToDB($db, $baseId, $name, $def)
	{
		$sth = $db->prepare("insert into parameter(base, name, def) values(:base, :name, :def);");
		$sth->bindValue(':base', $baseId, PDO::PARAM_INT);
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':def', $def, PDO::PARAM_STR);
		$sth->execute();
	}

	public static function getReplaceableParameter($name)
	{
		$db = self::connectDB();
		$sth = $db->prepare("select parameter.* from parameter,base"
			." where base.name=:name and parameter.base=base.id and parameter.def='' and parameter.id"
			." not in (select parameter from replaceSets,replaceSetsParameter"
			." where replaceSets.id=replaceSetsParameter.replaceSets and replaceSets.base=base.id);");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->execute();
		$parameters = [];
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$parameters[] = $row;
		}
		return $parameters;
	}

	public static function addReplaceSets($name, $parameters)
	{
		$db = self::connectDB();

		$sth = $db->prepare("select id from base where name=:name;");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->execute();
		$baseId = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$baseId = $row["id"];
		}

		$db->query("insert into replaceSets(base) values(-1);");
		$sth = $db->prepare("select id from replaceSets where base=-1;");
		$sth->execute();
		$setsId = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$setsId = $row["id"];
		}
		$db->query("update replaceSets set base=".$baseId." where id=".$setsId.";");

		$paramCount = 0;
		foreach ($parameters as $parameter) {
			$sth = $db->prepare("select id from parameter where name=:name and base=:baseId;");
			$sth->bindValue(':name', $parameter, PDO::PARAM_STR);
			$sth->bindValue(':baseId', $baseId, PDO::PARAM_INT);
			$sth->execute();
			while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
				$sth2 = $db->prepare("insert into replaceSetsParameter(replaceSets, parameter) values(:setsId, :paramId);");
				$sth2->bindValue(':setsId', $setsId, PDO::PARAM_INT);
				$sth2->bindValue(':paramId', $row["id"], PDO::PARAM_STR);
				$sth2->execute();
				$paramCount++;
			}
		}

		if ($paramCount <= 0) {
			$db->query("delete from replaceSets where id=".$setsId.";");
		}
	}

	public static function addReplaceSet($name, $replaceSets, $values)
	{
		$db = self::connectDB();
		$db->query("insert into replaceSet(replaceSets) values(-1);");
		$sth = $db->query("select id from replaceSet where replaceSets=-1;");
		$replaceSet = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$replaceSet = $row["id"];
		}
		$sth = $db->prepare("update replaceSet set replaceSets=:replaceSets where id=:id;");
		$sth->bindValue(':replaceSets', $replaceSets, PDO::PARAM_INT);
		$sth->bindValue(':id', $replaceSet, PDO::PARAM_INT);
		$sth->execute();

		$sth = $db->prepare("select parameter.* from parameter,replaceSetsParameter,replaceSets,base"
			." where parameter.id=replaceSetsParameter.parameter and replaceSetsParameter.replaceSets=replaceSets.id"
			." and replaceSets.base=base.id and base.name=:name and replaceSets.id=:replaceSets;");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':replaceSets', $replaceSets, PDO::PARAM_INT);
		$sth->execute();
		$insertedCount = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$value = (isset($values[$row["name"]]) ? $values[$row["name"]] : "");
			$sth2 = $db->prepare("insert into replace(replaceSet, parameter, value) values(:replaceSet, :parameter, :value);");
			$sth2->bindValue(':replaceSet', $replaceSet, PDO::PARAM_INT);
			$sth2->bindValue(':parameter', $row["id"], PDO::PARAM_INT);
			$sth2->bindValue(':value', $value, PDO::PARAM_STR);
			$sth2->execute();
			$insertedCount++;
		}

		if ($insertedCount <= 0) {
			$sth = $db->prepare("delete from replaceSet where id=:id;");
			$sth->bindValue(':id', $replaceSet, PDO::PARAM_INT);
			$sth->execute();
		} else {
			self::generateRuns($name, $replaceSet);
		}
	}

	public static function generateRuns($name, $replaceSet)
	{
		if (count(self::getReplaceableParameter($name)) > 0) { return; }
		$select = "select * from ";
		$db = self::connectDB();
		$sth = $db->prepare("select replaceSets from replaceSet where id=:id;");
		$sth->bindValue(':id', $replaceSet, PDO::PARAM_INT);
		$sth->execute();
		$replaceSets = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$replaceSets = $row["replaceSets"];
		}

		$sth = $db->prepare("select replaceSets.id from replaceSets,base where replaceSets.base=base.id and base.name=:base;");
		$sth->bindValue(':base', $name, PDO::PARAM_STR);
		$sth->execute();
		$from = "";
		$count = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			if ($from !== "") {
				$from .= ",";
			}
			if ($row["id"] == $replaceSets) {
				$from .= "(select id as p".$count." from replaceSet where replaceSets=".$row["id"]." and id=".$replaceSet.")";
			} else {
				$from .= "(select id as p".$count." from replaceSet where replaceSets=".$row["id"].")";
			}
			$count++;
		}
		$select .= $from.";";

		$sth = $db->query($select);
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$params = "";
			foreach ($row as $r) {
				if ($params !== "") {
					$params .= ",";
				}
				$params .= $r;
			}
			$sth2 = $db->prepare("insert into run(name, replaceSetArray) values(:name, :params);");
			$sth2->bindValue(':name', uniqid(), PDO::PARAM_STR);
			$sth2->bindValue(':params', $params, PDO::PARAM_STR);
			$sth2->execute();
		}
	}

	/**
	 *
	 * */
	public static function addMap($sessionName, $mapName)
	{
		$session = Self::getSession($sessionName);
		if (count(MapManager::getMap($mapName)) <= 0) {
			return false;
		}
		if (count($session) <= 0) {
			return false;
		}

		$db = self::connectDB();
		$sth = $db->prepare("insert into linkedMap(session, map) values(:session, :map);");
		$sth->bindValue(':session', $sessionName, PDO::PARAM_STR);
		$sth->bindValue(':map', $mapName, PDO::PARAM_STR);
		$sth->execute();

		foreach ($session['agents'] as $agent) {
			if (!isset($agent['name']) || $agent['name'] == '') {
				continue;
			}

			$scriptId = uniqid();
			$sth = $db->prepare("insert into run(name, session, map, agent) values(:name, :session, :map, :agent);");
			$sth->bindValue(':name', $scriptId, PDO::PARAM_STR);
			$sth->bindValue(':session', $sessionName, PDO::PARAM_STR);
			$sth->bindValue(':map', $mapName, PDO::PARAM_STR);
			$sth->bindValue(':agent', $agent['name'], PDO::PARAM_STR);
			$sth->execute();

			$script = "#!/bin/bash\n\n";
			$script .= Config::$OACISCLI_PATH . " create_parameter_sets";
			$script .= ' -s ' . $sessionName;
			$script .= ' -i \'{"MAP":"' . $mapName . '","F":"' . $agent['name'] . '","P":"' . $agent['name'] . '","A":"' . $agent['name'] . '"}\'';
			$script .= ' -r \'{"num_runs":1,"mpi_procs":0,"omp_threads":0,"priority":1,"submitted_to":"' . ClusterManager::getMainHostGroup() . '","host_parameters":null}\'';
			$script .= ' -o /tmp/out_' . $scriptId . '.json';
			$script .= "\n";
			$script .= 'php ' . realpath(dirname(__FILE__)) . '/update_runid.php \'' . $scriptId . '\' /tmp/out_' . $scriptId . '.json';
			file_put_contents('/home/oacis/rrs-oacis/oacis-queue/scripts/' . $scriptId, $script);
			exec('nohup /home/oacis/rrs-oacis/oacis-queue/main.pl ' . $scriptId . ' > /dev/null &');
		}

		return true;
	}

	/**
	 *
	 * */
	public static function removeMap($sessionName, $mapName)
	{
		$session = Self::getSession($sessionName);
		if (count(MapManager::getMap($mapName)) <= 0) {
			return false;
		}
		if (count($session) <= 0) {
			return false;
		}

		$db = self::connectDB();
		$sth = $db->prepare("select runId from run where session=:session and map=:map;");
		$sth->bindValue(':session', $sessionName, PDO::PARAM_STR);
		$sth->bindValue(':map', $mapName, PDO::PARAM_STR);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$scriptId = uniqid();

			$script = "#!/bin/bash\n\n";
			$script .= Config::$OACISCLI_PATH . " destroy_runs_by_ids";
			$script .= ' ' . $row['runId'];

			file_put_contents('/home/oacis/rrs-oacis/oacis-queue/scripts/' . $scriptId, $script);
			exec('nohup /home/oacis/rrs-oacis/oacis-queue/main.pl ' . $scriptId . ' > /dev/null &');
		}
		$db->query("delete from run where session='" . $sessionName . "' and map='" . $mapName . "';");

		$sth = $db->prepare("delete from linkedMap where session=:session and map=:map;");
		$sth->bindValue(':session', $sessionName, PDO::PARAM_STR);
		$sth->bindValue(':map', $mapName, PDO::PARAM_STR);
		$sth->execute();

		return true;
	}

	/**
	 *
	 * */
	public static function repost($sessionName, $mapName, $agentName)
	{
		$session = Self::getSession($sessionName);
		if (count(MapManager::getMap($mapName)) <= 0) {
			return false;
		}
		if (count($session) <= 0) {
			return false;
		}

		$db = self::connectDB();

		$scriptId = uniqid();
		$sth = $db->prepare("insert into run(name, session, map, agent) values(:name, :session, :map, :agent);");
		$sth->bindValue(':name', $scriptId, PDO::PARAM_STR);
		$sth->bindValue(':session', $sessionName, PDO::PARAM_STR);
		$sth->bindValue(':map', $mapName, PDO::PARAM_STR);
		$sth->bindValue(':agent', $agentName, PDO::PARAM_STR);
		$sth->execute();

		$script = "#!/bin/bash\n\n";
		$script .= Config::$OACISCLI_PATH . " create_parameter_sets";
		$script .= ' -s ' . $sessionName;
		$script .= ' -i \'{"MAP":"' . $mapName . '","F":"' . $agentName . '","P":"' . $agentName . '","A":"' . $agentName . '"}\'';
		$script .= ' -r \'{"num_runs":1,"mpi_procs":0,"omp_threads":0,"priority":1,"submitted_to":"' . ClusterManager::getMainHostGroup() . '","host_parameters":null}\'';
		$script .= ' -o /tmp/out_' . $scriptId . '.json';
		$script .= "\n";
		$script .= 'php ' . realpath(dirname(__FILE__)) . '/update_runid.php \'' . $scriptId . '\' /tmp/out_' . $scriptId . '.json';
		file_put_contents('/home/oacis/rrs-oacis/oacis-queue/scripts/' . $scriptId, $script);
		exec('nohup /home/oacis/rrs-oacis/oacis-queue/main.pl ' . $scriptId . ' > /dev/null &');

		return true;
	}

	/**
	 *
	 * */
	public static function rerun($runId)
	{
		$db = self::connectDB();
		$sth = $db->prepare("select runId,paramId,agent,map,session from run where runId=:runId;");
		$sth->bindValue(':runId', $runId, PDO::PARAM_STR);
		$sth->execute();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($row['agent']) || $row['agent'] == '') {
				continue;
			}

			$scriptId = uniqid();

			$sth = $db->prepare("update run set name=:name, agent=:agent where runId=:runId;");
			$sth->bindValue(':name', $scriptId, PDO::PARAM_STR);
			$sth->bindValue(':runId', $runId, PDO::PARAM_STR);
			$sth->bindValue(':agent', $row['agent'], PDO::PARAM_STR);
			$sth->execute();

			$script = "#!/bin/bash\n\n";
			$script .= "/home/oacis/oacis/bin/oacis_ruby /home/oacis/rrs-oacis/ruby/discard.rb " . $row['paramId'];
			$script .= "\n";
			$script .= "sleep 5";
			$script .= "\n";
			$script .= Config::$OACISCLI_PATH . " create_parameter_sets";
			$script .= ' -s ' . $row['session'];
			$script .= ' -i \'{"MAP":"' . $row['map'] . '","F":"' . $row['agent'] . '","P":"' . $row['agent'] . '","A":"' . $row['agent'] . '"}\'';
			$script .= ' -r \'{"num_runs":1,"mpi_procs":0,"omp_threads":0,"priority":1,"submitted_to":"' . ClusterManager::getMainHostGroup() . '","host_parameters":null}\'';
			$script .= ' -o /tmp/out_' . $scriptId . '.json >/tmp/e 2>&1';
			$script .= "\n";
			$script .= 'php ' . realpath(dirname(__FILE__)) . '/update_runid.php \'' . $scriptId . '\' /tmp/out_' . $scriptId . '.json';
			file_put_contents('/home/oacis/rrs-oacis/oacis-queue/scripts/' . $scriptId, $script);
			exec('nohup /home/oacis/rrs-oacis/oacis-queue/main.pl ' . $scriptId . ' > /dev/null &');
		}

		return true;
	}


	/**
	 *
	 * */
	private static function connectDB()
	{
		$db = DatabaseManager::getDatabase();
		$version = 0;
		$dbVersion = 0;
		$sth = $db->query("select value from system where name='version';");
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$dbVersion = $row['value'];
		}

		if ($dbVersion <= $version++ )
		{
			$db->query("insert into system(name,value) values('version', 1);");
		}
		if ($dbVersion <= $version++ )
		{
			$db->query("create table base(id integer primary key, name, alias, note);");
			$db->query("create table parameter(id integer primary key, base, name, def default '');");
			$db->query("create table replaceSets(id integer primary key, base);");
			$db->query("create table replaceSetsParameter(replaceSets, parameter);");
			$db->query("create table replaceSet(id integer primary key, replaceSets);");
			$db->query("create table replace(id integer primary key, replaceSet, parameter, value);");
			$db->query("create table run(id integer primary key, name, paramId, runId, state default -10, replaceSetArray default '', score);");
		}

		if ($dbVersion != $version)
		{
			$sth = $db->prepare("update system set value=:value where name='version';");
			$sth->bindValue(':value', $version, PDO::PARAM_INT);
			$sth->execute();
		}

		return $db;
	}
}

