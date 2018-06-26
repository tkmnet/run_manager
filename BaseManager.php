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
use rrsoacis\manager\ScriptManager;
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

			$base["overview"] = [];
			$sth2 = $db->prepare("select state,count(*) as number from run where base=:baseId group by state;");
			$sth2->bindValue(':baseId', $base["id"], PDO::PARAM_INT);
			$sth2->execute();
			while ($row2 = $sth2->fetch(PDO::FETCH_ASSOC)) {
				$base["overview"][] = $row2;
			}
		}
		return $base;
	}

	public static function addBase($alias, $note, $logmode, $map, $agent_a, $agent_f, $agent_p, $use_prec, $is_mod, $is_dev, $mods, $devs)
	{
		$tmpFileOut = '/tmp/rrsoacis-out-' . uniqid();
		$tmpFileIn = '/tmp/rrsoacis-in-' . uniqid();
		system("sudo -i -u oacis " . Config::$OACISCLI_PATH . " simulator_template -o " . $tmpFileOut . " 2>&1");
		$simulator = json_decode(file_get_contents($tmpFileOut), true);
		system("rm -f " . $tmpFileOut);
		$simulator['name'] = "RO_tkmnetRM_" . uniqid();
		$simulator['command'] = '/home/oacis/rrs-oacis/rrsenv/script/rrscluster run -c ../rrscluster.cfg -i ./_input.json -l ./ --ce';
		$simulator['executable_on_ids'][] = ClusterManager::getMainHostGroup();
		$simulator['support_input_json'] = true;

		$simulator['parameter_definitions'] = [];

		$parameter1 = [];
		$parameter1['key'] = 'RUN_NAME';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

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
		$parameter1['key'] = 'USE_PREC';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '0';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'LOGMODE';
		$parameter1['type'] = 'String';
		$parameter1['default'] = 'ALL';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'IS_MOD';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '0';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		$parameter1 = [];
		$parameter1['key'] = 'IS_DEV';
		$parameter1['type'] = 'String';
		$parameter1['default'] = '0';
		$parameter1['description'] = '';
		$simulator['parameter_definitions'][] = $parameter1;

		foreach ($mods as $mod) {
			$parameter1 = [];
			$parameter1['key'] = 'MOD_'.str_replace('.', '__DOT__', $mod[0]);
			$parameter1['type'] = 'String';
			$parameter1['default'] = '';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;
		}

		foreach ($devs as $dev) {
			$parameter1 = [];
			$parameter1['key'] = 'DEV_'.str_replace('.', '__DOT__', $dev[0]);
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
		$sth = $db->prepare("insert into base(name, alias, note, version) values(:name, :alias, :note, :version);");
		$sth->bindValue(':name', $simulatorId, PDO::PARAM_STR);
		$sth->bindValue(':alias', $alias, PDO::PARAM_STR);
		$sth->bindValue(':note', $note, PDO::PARAM_STR);
		$sth->bindValue(':version', self::getbaseVersion(), PDO::PARAM_INT);
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
		self::addParameterToDB($db, $baseId, "USE_PREC", $use_prec);
		self::addParameterToDB($db, $baseId, "IS_MOD", $is_mod);
		self::addParameterToDB($db, $baseId, "IS_DEV", $is_dev);

		foreach ($mods as $mod) {
			self::addParameterToDB($db, $baseId, "MOD_".$mod[0], $mod[1]);
		}

		foreach ($devs as $dev) {
			self::addParameterToDB($db, $baseId, "DEV_".$dev[0], $dev[1]);
		}

		self::addParameterToDB($db, $baseId, "LOGMODE", $logmode);
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
			$value = (isset($values[str_replace('.', '__DOT__', $row["name"])]) ? $values[str_replace('.', '__DOT__', $row["name"])] : "");
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

		$base = BaseManager::getBase($name);

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

		$sth = $db->prepare("select trial from base where name=:base;");
		$sth->bindValue(':base', $name, PDO::PARAM_STR);
		$sth->execute();
		$trial = $sth->fetch(PDO::FETCH_ASSOC)["trial"];

		$sth = $db->query($select);
		$paramsArray = [];
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$params = "";
			foreach ($row as $r) {
				if ($params !== "") {
					$params .= ",";
				}
				$params .= $r;
			}
			$paramsArray [] = $params;
		}

		$db->beginTransaction();
		foreach ($paramsArray as $params) {
			for ($n = 0; $n < $trial; $n++) {
				$sth2 = $db->prepare("insert into run(name, base, trial, replaceSetArray) values(:name, :base, :trial, :params);");
				$sth2->bindValue(':name', str_replace(".", "0", uniqid("", true)), PDO::PARAM_STR);
				$sth2->bindValue(':base', $base["id"], PDO::PARAM_INT);
				$sth2->bindValue(':params', $params, PDO::PARAM_STR);
				$sth2->bindValue(':trial', $n, PDO::PARAM_INT);
				$sth2->execute();
			}
		}
		$db->commit();
	}

	public static function postRunToOACIS($runName, $db = null)
	{
		if ($db === null)
		{
			$db = self::connectDB();
		}

		$run = self::getRun($runName);
		if ($run != null)
		{
			$scriptId = $run["name"];
			$simulatorName = $run["baseName"];

			$input ='"RUN_NAME":"'.$run["name"].'"';
			foreach ($run["params"] as $param)
			{
				if ($input !== "") { $input .= ","; }
				$input .='"'.str_replace('.', '__DOT__', $param[0]).'":"'.$param[1].'"';
			}
			foreach (self::getDefaultParameters($run["baseName"]) as $param)
			{
				if ($input !== "") { $input .= ","; }
				$input .='"'.str_replace('.', '__DOT__', $param[0]).'":"'.$param[1].'"';
			}

			$out_filename = '/tmp/out_' . $scriptId . '.json';

			$command = Config::$OACISCLI_PATH . " create_parameter_sets";
			$command .= ' -s ' . $simulatorName;
			$command .= ' -i \\\'{'.$input.'}\\\'';
			$command .= ' -r \\\'{"num_runs":1,"mpi_procs":0,"omp_threads":0,"priority":1,"submitted_to":"' . ClusterManager::getMainHostGroup() . '","host_parameters":null}\\\'';
			$command .= ' -o ' . $out_filename;

			$script = "exec('".$command."');";

			$script .= '$id = "'.$scriptId.'";';
			$script .= '$filename = "'.$out_filename.'";';
			$script .= '$outputs = json_decode( file_get_contents($filename), true );';
			$script .= 'foreach ($outputs as $out) {';
			$script .= '  $paramId = $out["parameter_set_id"];';
			$script .= '  $runId = "";';
			$script .= '  $paramSetJson = file_get_contents("http://localhost:3000/parameter_sets/".$paramId.".json");';
			$script .= '  $paramSet = json_decode($paramSetJson, true);';
			$script .= '  foreach ($paramSet["runs"] as $run) { $runId = $run["id"]; }';
			$script .= '  $db = getDatabase();';
			$script .= '  $sth = $db->prepare("update run set paramId=:paramId, runId=:runId, state=2 where name=:name;");';
			$script .= '  $sth->bindValue(":paramId", $paramId, PDO::PARAM_STR);';
			$script .= '  $sth->bindValue(":runId", $runId, PDO::PARAM_STR);';
			$script .= '  $sth->bindValue(":name", $id, PDO::PARAM_STR);';
			$script .= '  $sth->execute();';
			$script .= '}';

			$script .= "exec('rm -f ".$out_filename."');";

			ScriptManager::queuePhpScript($script, "/tmp/testout.txt");

			$sth = $db->prepare("update run set state=1 where name=:name;");
			$sth->bindValue(':name', $runName, PDO::PARAM_STR);
			$sth->execute();
		}
	}

	public static function getDefaultParameters($name)
	{
		$db = self::connectDB();
		$sth = $db->prepare("select * from base,parameter where base.id=parameter.base and base.name=:name and def!='';");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->execute();
		$params = [];
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$param = [];
			$param[0] = $row["name"];
			$param[1] = $row["def"];
			$params[] = $param;
		}
		return $params;
	}

	public static function getReplaceSetFromID($replaceSetID)
	{
		$db = self::connectDB();
		$sth = $db->prepare("select * from replaceSet where id=:id;");
		$sth->bindValue(':id', $replaceSetID, PDO::PARAM_INT);
		$sth->execute();
		$replaceSet = null;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$replaceSet = $row;
			$sth2 = $db->prepare("select *,parameter.name as parameterName from parameter,replace where parameter.id=replace.parameter and replaceSet=:setId;");
			$sth2->bindValue(':setId', $replaceSet["id"], PDO::PARAM_INT);
			$sth2->execute();
			$replaces = [];
			while ($row2 = $sth2->fetch(PDO::FETCH_ASSOC))
			{
				$replaces[] = $row2;
			}
			$replaceSet["replace"] = $replaces;
		}
		return $replaceSet;
	}

	public static function getRun($runName)
	{
		$db = self::connectDB();
		$sth = $db->prepare("select *,base.name as baseName from base,run where run.base=base.id and run.name=:name;");
		$sth->bindValue(':name', $runName, PDO::PARAM_STR);
		$sth->execute();
		$run = null;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$run = $row;
			$params = [];
			$replaceSetIDArray = explode(",", $row["replaceSetArray"]);
			$run["replaceSets"] = [];
			foreach ($replaceSetIDArray as $replaceSetID)
			{
				$replaceSet = self::getReplaceSetFromID($replaceSetID);
				foreach ($replaceSet["replace"] as $replace)
				{
					$param = [];
					$param[0] = $replace["parameterName"];
					$param[1] = $replace["value"];
					$params[] = $param;
				}
				$run["replaceSets"][] = $replaceSet["replace"];
			}
			$run["params"] = $params;
		}
		return $run;
	}

	public static function getPendingRunList($name, $offset, $limit)
	{
		$db = self::connectDB();
		$sth = $db->prepare("select * from base,run where run.base=base.id and base.name=:base and run.state<0;");
		$sth->bindValue(':base', $name, PDO::PARAM_STR);
		$sth->execute();
		$runs = [];
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$runs[] = $row;
		}
		return $runs;
	}

	public static function getDictText($group="")
	{
		$db = self::connectDB();
		$sth = $db->prepare("select name,value from dict where group_name=:group;");
		$sth->bindValue(':group', $group, PDO::PARAM_STR);
		$sth->execute();
		$result = "";
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$result .= $row["name"].":".$row["value"]."\n";
		}
		return $result;
	}

	public static function addDict($name, $value, $group="")
	{
		if ($name === null || $value === null || $name === "" || $value === "") { return; }

		$db = self::connectDB();
		$sth = $db->prepare("select count(*) as cnt from dict where name=:name and value=:value and group_name=:group;");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':value', $value, PDO::PARAM_STR);
		$sth->bindValue(':group', $group, PDO::PARAM_STR);
		$sth->execute();
		if ($sth->fetch(PDO::FETCH_ASSOC)["cnt"] > 0) { return; }
		$sth = $db->prepare("insert into dict(name, value, group) values(:name, :value, :group);");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':value', $value, PDO::PARAM_STR);
		$sth->bindValue(':group', $group, PDO::PARAM_STR);
		$sth->execute();
	}

	public static function getDict($name, $group="")
	{
		$db = self::connectDB();
		$sth = $db->prepare("select value from dict where name=:name and group_name=:group;");
		$sth->bindValue(':name', $name, PDO::PARAM_STR);
		$sth->bindValue(':group', $group, PDO::PARAM_STR);
		$sth->execute();
		$sth->execute();
		$results = [];
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$results[] = $row["value"];
		}
		return $results;
	}

	public static function duplicateBase($name)
	{
		$base = self::getBase($name);
		if ($base !== null)
		{
			$tmpFileOut = '/tmp/rrsoacis-out-' . uniqid();
			$tmpFileIn = '/tmp/rrsoacis-in-' . uniqid();
			system("sudo -i -u oacis " . Config::$OACISCLI_PATH . " simulator_template -o " . $tmpFileOut . " 2>&1");
			$simulator = json_decode(file_get_contents($tmpFileOut), true);
			system("rm -f " . $tmpFileOut);
			$simulator['name'] = "RO_tkmnetRM_" . uniqid();
			$simulator['command'] = '/home/oacis/rrs-oacis/rrsenv/script/rrscluster run -c ../rrscluster.cfg -i ./_input.json -l ./';
			$simulator['executable_on_ids'][] = ClusterManager::getMainHostGroup();
			$simulator['support_input_json'] = true;

			$simulator['parameter_definitions'] = [];

			$parameter1 = [];
			$parameter1['key'] = 'RUN_NAME';
			$parameter1['type'] = 'String';
			$parameter1['default'] = '';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;

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
			$parameter1['key'] = 'USE_PREC';
			$parameter1['type'] = 'String';
			$parameter1['default'] = '0';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;

			$parameter1 = [];
			$parameter1['key'] = 'LOGMODE';
			$parameter1['type'] = 'String';
			$parameter1['default'] = 'ALL';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;

			$parameter1 = [];
			$parameter1['key'] = 'IS_MOD';
			$parameter1['type'] = 'String';
			$parameter1['default'] = '0';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;

			$parameter1 = [];
			$parameter1['key'] = 'IS_DEV';
			$parameter1['type'] = 'String';
			$parameter1['default'] = '0';
			$parameter1['description'] = '';
			$simulator['parameter_definitions'][] = $parameter1;

			$mods = [];
			$devs = [];
			foreach ($base["parameters"] as $p)
			{
				$pName = $p["name"];
				if (substr($pName, 0, strlen("MOD_")) === "MOD_") { $mods[] = $pName; }
				else if (substr($pName, 0, strlen("DEV_")) === "DEV_") { $devs[] = $pName; }
			}

			foreach ($mods as $mod)
			{
				$parameter1 = [];
				$parameter1['key'] = str_replace('.', '__DOT__', $mod);
				$parameter1['type'] = 'String';
				$parameter1['default'] = '';
				$parameter1['description'] = '';
				$simulator['parameter_definitions'][] = $parameter1;
			}

			foreach ($devs as $dev)
			{
				$parameter1 = [];
				$parameter1['key'] = str_replace('.', '__DOT__', $dev);
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
			$sth = $db->prepare("insert into base(name, alias, note, version) values(:name, :alias, :note, :version);");
			$sth->bindValue(':name', $simulatorId, PDO::PARAM_STR);
			$sth->bindValue(':alias', $base["alias"] . "_copy", PDO::PARAM_STR);
			$sth->bindValue(':note', $base["note"], PDO::PARAM_STR);
			$sth->bindValue(':version', self::getbaseVersion(), PDO::PARAM_INT);
			$sth->execute();

			$sth = $db->prepare("select id from base where name=:name;");
			$sth->bindValue(':name', $simulatorId, PDO::PARAM_STR);
			$sth->execute();
			$baseId = 0;
			while ($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$baseId = $row["id"];
			}

			$sth = $db->prepare("insert into parameter select null as id, :newbase as base, name, def from parameter where base=:base;");
			$sth->bindValue(':newbase', $baseId, PDO::PARAM_INT);
			$sth->bindValue(':base', $base["id"], PDO::PARAM_INT);
			$sth->execute();

			if ($base["version"] < 2) { self::addParameterToDB($db, $baseId, "LOGMODE", ''); }

			return $simulatorId;
		}
	}

	public static function getOacisParameterSets($run)
	{
		$rawData = @file_get_contents('http://127.0.0.1:3000/parameter_sets/'.$run["paramId"].'.json');
		if ($rawData === false) { return false; }
		return json_decode($rawData);
	}

	public static function getOacisRun($run, $db = null)
	{
		$rawData = @file_get_contents('http://127.0.0.1:3000/runs/'.$run["runId"].'.json');

		if ($rawData === false)
		{
			$oacisPS = self::getOacisParameterSets($run);
			if ($oacisPS === false) { return false; }
			if (count($oacisPS->runs) <= 0) { return false; }

			$newRunId = $oacisPS->runs[count($oacisPS->runs)-1]->id;

			if ($db === null) { $db = self::connectDB(); }
			$sth = $db->prepare("update run set runId=:runId where id=:id;");
			$sth->bindValue(':id', $run["id"], PDO::PARAM_INT);
			$sth->bindValue(':runId', $newRunId, PDO::PARAM_STR);
			$sth->execute();

			$rawData = file_get_contents('http://127.0.0.1:3000/runs/'.$newRunId.'.json');
		}

		return json_decode($rawData);
	}

	public static function updateScore($runName, $db = null)
	{
		$run = self::getRun($runName);
		if ($run !== null)
		{
			$oacisRun = self::getOacisRun($run, $db);
			$newScore = -1;
			$finished = false;
			if ($oacisRun !== false)
			{
				if ($oacisRun->status === "finished")
				{
					if (isset($oacisRun->result->Score))
					{
						$newScore = $oacisRun->result->Score;
						$finished = true;
					}
					else
					{
						ScriptManager::queueBashScript("/home/oacis/oacis/bin/oacis_ruby /home/oacis/rrs-oacis/ruby/repost.rb ". $oacisRun->id);
					}
				}
			}

			$csvtext = "";
			foreach ($run["replaceSets"] as $replaceSet)
			{
				$replaceSetText = "";
				foreach ($replaceSet as $replace)
				{
					if ($replaceSetText !== "") { $replaceSetText .= "|"; }
					$replaceSetText .= $replace["value"];
				}
				$csvtext .= $replaceSetText . ",";
			}
			//if ($newScore !== null) { $csvtext .= $newScore . ","; }
			//else { $csvtext .= "-1,"; }
			//$csvtext .= "\n";

			if ($db === null) { $db = self::connectDB(); }
			if ($finished)
			{
				$sth = $db->prepare("update run set score=:score,state=0,csvtext=:csvtext where id=:id;");
				$sth->bindValue(':id', $run["id"], PDO::PARAM_INT);
				$sth->bindValue(':score', $newScore, PDO::PARAM_STR);
				$sth->bindValue(':csvtext', $csvtext, PDO::PARAM_STR);
				$sth->execute();
			}
			else if ($run["score"] != -1)
			{
				$sth = $db->prepare("update run set score=:score,csvtext=:csvtext where id=:id;");
				$sth->bindValue(':id', $run["id"], PDO::PARAM_INT);
				$sth->bindValue(':score', $newScore, PDO::PARAM_STR);
				$sth->bindValue(':csvtext', $csvtext, PDO::PARAM_STR);
				$sth->execute();
			}
		}
	}

	public static function printResultCsv($name, $mode = "all")
	{
		$base = self::getBase($name);
		if ($base !== null)
		{
			$result = "";

			/*
			 * //get Default parameter
			 foreach (self::getDefaultParameters($run["baseName"]) as $param) {
				 if ($input !== "") { $input .= ","; }
		$input .='"'.str_replace('.', '__DOT__', $param[0]).'":"'.$param[1].'"';
		}
			 */

			$db = self::connectDB();
			$sth = $db->prepare("select name from run where base=:base limit 1;");
			$sth->bindValue(':base', $base["id"], PDO::PARAM_INT);
			$sth->execute();
			while ($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$run = self::getRun($row["name"]);
				foreach ($run["replaceSets"] as $replaceSet)
				{
					$replaceSetText = "";
					foreach ($replaceSet as $replace)
					{
						if ($replaceSetText !== "") { $replaceSetText .= "|"; }
						$replaceSetText .= $replace["parameterName"];
					}
					$result .= $replaceSetText . ",";
				}
				$result .= "\"Score\",\n";
				print $result;
			}

			if ($mode === "all")
			{
				$sth = $db->prepare("select csvtext,score from run where base=:base;");
				$sth->bindValue(':base', $base["id"], PDO::PARAM_INT);
				$sth->execute();
				while ($row = $sth->fetch(PDO::FETCH_ASSOC))
				{ print $row["csvtext"] . $row["score"] . ",\n"; }
			}
			else if ($mode === "max")
			{
				$sth = $db->prepare("select csvtext,score,replaceSetArray from run where base=:base order by replaceSetArray;");
				$sth->bindValue(':base', $base["id"], PDO::PARAM_INT);
				$sth->execute();
				$lastReplaceSetArrayText = "";
				$currentlyMax = null;
				while ($row = $sth->fetch(PDO::FETCH_ASSOC))
				{
					if ($lastReplaceSetArrayText !== $row["replaceSetArray"]
						&& $lastReplaceSetArrayText !== "")
					{
						print $currentlyMax["csvtext"] . $currentlyMax["score"] . "\n";
						$lastReplaceSetArrayText = "";
						$currentlyMax = null;
					}
					if ($currentlyMax == null || $currentlyMax["score"] < $row["score"])
					{
						$currentlyMax = $row;
					}
					$lastReplaceSetArrayText = $row["replaceSetArray"];
				}
				if ($lastReplaceSetArrayText !== $row["replaceSetArray"]
					&& $lastReplaceSetArrayText !== "")
				{
					print $currentlyMax["csvtext"] . $currentlyMax["score"] . "\n";
				}
			}
			else if ($mode === "mean")
			{
				$sth = $db->prepare("select csvtext,score,replaceSetArray from run where base=:base order by replaceSetArray;");
				$sth->bindValue(':base', $base["id"], PDO::PARAM_INT);
				$sth->execute();
				$lastReplaceSetArrayText = "";
				$lastCsvtext = "";
				$runArray = [];
				while ($row = $sth->fetch(PDO::FETCH_ASSOC))
				{
					if ($lastReplaceSetArrayText !== $row["replaceSetArray"]
						&& $lastReplaceSetArrayText !== "")
					{
						print $lastCsvtext . self::mean($runArray) . "\n";
						$lastReplaceSetArrayText = "";
						$runArray = [];
					}
					$runArray[] = $row["score"];
					$lastReplaceSetArrayText = $row["replaceSetArray"];
					$lastCsvtext = $row["csvtext"];
				}
				if ($lastReplaceSetArrayText !== $row["replaceSetArray"]
					&& $lastReplaceSetArrayText !== "")
				{
					print $lastCsvtext . self::mean($runArray) . "\n";
				}
			}
			else if ($mode === "median")
			{
				$sth = $db->prepare("select csvtext,score,replaceSetArray from run where base=:base order by replaceSetArray;");
				$sth->bindValue(':base', $base["id"], PDO::PARAM_INT);
				$sth->execute();
				$lastReplaceSetArrayText = "";
				$lastCsvtext = "";
				$runArray = [];
				while ($row = $sth->fetch(PDO::FETCH_ASSOC))
				{
					if ($lastReplaceSetArrayText !== $row["replaceSetArray"]
						&& $lastReplaceSetArrayText !== "")
					{
						print $lastCsvtext . self::median($runArray) . "\n";
						$lastReplaceSetArrayText = "";
						$runArray = [];
					}
					$runArray[] = $row["score"];
					$lastReplaceSetArrayText = $row["replaceSetArray"];
					$lastCsvtext = $row["csvtext"];
				}
				if ($lastReplaceSetArrayText !== $row["replaceSetArray"]
					&& $lastReplaceSetArrayText !== "")
				{
					print $lastCsvtext . self::median($runArray) . "\n";
				}
			}
		}

		return "";
	}

	private static function mean($array)
	{
		$count = count($array); 
		if ($count <= 0) { return 0; }
		$sum = array_sum($array); 
		return $sum / $count; 
	}

	private static function median($array)
	{
		$count = count($array); 
		if ($count <= 0) { return 0; }
		sort($array, SORT_NUMERIC); 
		if ($count % 2 == 0)
		{
			$middle = $count /2; 
			return ($array[$middle-1] + $array[$middle]) /2.0; 
		}
		else
		{
			$middle = round($count /2); 
			return $array[$middle-1]; 
		}
	}

	public static function getTrialCount($name)
	{
		$base = self::getBase($name);
		if ($base === null) { return null; }
		return $base["trial"];
	}

	public static function addTrial($name)
	{
		$base = self::getBase($name);
		if ($base === null) { return null; }

		$trial = $base["trial"] + 1;

		$db = self::connectDB();
		$sth = $db->prepare("update base set trial=:trial where name=:base;");
		$sth->bindValue(':base', $base["name"], PDO::PARAM_STR);
		$sth->bindValue(':trial', $trial, PDO::PARAM_INT);
		$sth->execute();

		$sth = $db->prepare("select * from run,base where run.base=base.id and base.name=:base and run.trial=0;");
		$sth->bindValue(':base', $base["name"], PDO::PARAM_STR);
		$sth->execute();
		$runs = [];
		while ($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$runs[] = $row;
		}
		$db->beginTransaction();
		foreach ($runs as $run)
		{
			$sth2 = $db->prepare("insert into run(name, base, trial, replaceSetArray) values(:name, :base, :trial, :params);");
			$sth2->bindValue(':name', str_replace(".", "0", uniqid("", true)), PDO::PARAM_STR);
			$sth2->bindValue(':base', $run["id"], PDO::PARAM_INT);
			$sth2->bindValue(':params', $run["replaceSetArray"], PDO::PARAM_STR);
			$sth2->bindValue(':trial', ($trial -1), PDO::PARAM_INT);
			$sth2->execute();
		}
		$db->commit();
	}

	public static function updateAliasName($name, $newalias)
	{
		$base = self::getBase($name);
		if ($base === null) { return null; }

		$db = self::connectDB();
		$sth = $db->prepare("update base set alias=:alias where name=:base;");
		$sth->bindValue(':base', $base["name"], PDO::PARAM_STR);
		$sth->bindValue(':alias', $newalias, PDO::PARAM_STR);
		$sth->execute();
	}

	public static function getbaseVersion()
	{
		$db = self::connectDB();
		$sth = $db->query('select value from system where name="baseVersion";');
		return $sth->fetch(PDO::FETCH_ASSOC)["value"];
	}

	public static function connectDB()
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
			$db->query("create table run(id integer primary key, name, base, paramId, runId, state default -10, replaceSetArray default '', score);");
		}
		if ($dbVersion <= $version++ )
		{
			$db->query("create table dict(id integer primary key, name, value);");
		}
		if ($dbVersion <= $version++ )
		{
			$db->query("alter table base add column trial default 1;");
			$db->query("alter table run add column trial default 0;");
		}
		if ($dbVersion <= $version++ )
		{
			$db->query("alter table base add column version default 1;");
			$db->query("insert into system(name,value) values('baseVersion', 2);");
		}
		if ($dbVersion <= $version++ )
		{
			$db->query("alter table run add column csvtext default '';");
			$db->query("update run set state=2 where state=0;");
		}
		if ($dbVersion <= $version++ )
		{
			$db->query("update run set state=2 where state=0;");
		}
		if ($dbVersion <= $version++ )
		{
			$db->query("alter table dict add column group_name default '';");
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

