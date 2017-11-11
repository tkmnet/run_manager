<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\manager\AgentManager;
use rrsoacis\manager\MapManager;
use rrsoacis\system\Config;

class AddBasePage extends AbstractPage
{
	public function controller($params)
	{
		if (count($params) == 1) {
			if ($params[0] === "add") {
				$mods = [];
				$devs = [];
				$modCount = 0;
				$devCount = 0;
				while (isset($_POST["mod".$modCount])) {
					$mod[0] = $_POST["mod".$modCount];
					$mod[1] = $_POST["mod_def".$modCount];
					$mods[] = $mod;
					$modCount++;
				}
				while (isset($_POST["dev".$devCount])) {
					$dev[0] = $_POST["dev".$devCount];
					$dev[1] = $_POST["dev_def".$devCount];
					$devs[] = $dev;
					$devCount++;
				}

				BaseManager::addBase($_POST["alias"], $_POST["note"],
					$_POST["map"], $_POST["agent_a"], $_POST["agent_f"], $_POST["agent_p"], $_POST["use_prec"],
					$_POST["is_mod"], $_POST["is_dev"], $mods, $devs);

				header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager');
				return;
			}
		}

		$this->setTitle("Run Manager");
		$this->printPage();
	}

	function body()
	{
		self::writeContentHeader("Add Base", "Run Manager",
			["<a href='./run_manager'>Run Manager</a>"]);

		self::beginContent();
		$this->printBaseForm();
		?>
		<?php
		self::endContent();
	}

	function printBaseForm()
	{
		?>
		<div class="box box-primary">
			<div class="box-header with-border">
			</div>

			<form id="add_parameter-form" action="./run_manager-add_base/add" method="POST"
						class="form-horizontal">
				<div class="box-body">

					<div class="form-group">
						<label for="alias" class="col-sm-2 control-label">Alias</label>
						<div class="col-sm-10">
							<input type="text" id="inputAlias" class="form-control" name="alias" placeholder="Alias Name" required>
						</div>
					</div>

					<div class="form-group">
						<label for="note" class="col-sm-2 control-label">Note</label>
						<div class="col-sm-10">
							<textarea class="form-control" id="note" name="note"></textarea>
						</div>
					</div>

					<div class="form-group">
						<label for="map" class="col-sm-2 control-label">Map</label>
						<div class="col-sm-5">
							<input type="text" class="form-control" name="map_text" value="MAP" readonly>
						</div>
						<div class="col-sm-5">
							<select class="form-control" name="map">
								<option value="">Value</option>
								<?php foreach (MapManager::getMaps() as $map) { ?>
									<option><?= $map["name"] ?></option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label for="agent_a" class="col-sm-2 control-label">Ambulance</label>
						<div class="col-sm-5">
							<input type="text" class="form-control" name="agent_a_text" value="AGENT_A" readonly>
						</div>
						<div class="col-sm-5">
							<select class="form-control" name="agent_a">
								<option value="">Value</option>
								<?php foreach (AgentManager::getAgents() as $agent) { ?>
									<option><?= $agent["name"] ?></option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label for="agent_f" class="col-sm-2 control-label">Fire</label>
						<div class="col-sm-5">
							<input type="text" class="form-control" name="agent_f_text" value="AGENT_F" readonly>
						</div>
						<div class="col-sm-5">
							<select class="form-control" name="agent_f">
								<option value="">Value</option>
								<?php foreach (AgentManager::getAgents() as $agent) { ?>
									<option><?= $agent["name"] ?></option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label for="agent_p" class="col-sm-2 control-label">Police</label>
						<div class="col-sm-5">
							<input type="text" class="form-control" name="agent_p_text" value="AGENT_P" readonly>
						</div>
						<div class="col-sm-5">
							<select class="form-control" name="agent_p">
								<option value="">Value</option>
								<?php foreach (AgentManager::getAgents() as $agent) { ?>
									<option><?= $agent["name"] ?></option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label for="use_prec_text" class="col-sm-2 control-label">Enable Precompute</label>
						<div class="col-sm-5">
							<input type="text" class="form-control" name="use_prec_text" value="USE_PREC" readonly>
						</div>
						<div class="col-sm-5">
							<label class="radio-inline">
								<input type="radio" class="" name="use_prec" value="" checked="checked">Empty
							</label>
							<label class="radio-inline">
								<input type="radio" class="" name="use_prec" value="1">TRUE
							</label>
							<label class="radio-inline">
								<input type="radio" class="" name="use_prec" value="0">FALSE
							</label>
						</div>
					</div>

					<div class="form-group">
						<label for="is_mod_text" class="col-sm-2 control-label">Enable MOD</label>
						<div class="col-sm-5">
							<input type="text" class="form-control" name="is_mod_text" value="IS_MOD" readonly>
						</div>
						<div class="col-sm-5">
							<label class="radio-inline">
								<input type="radio" class="" name="is_mod" value="" checked="checked">Empty
							</label>
							<label class="radio-inline">
								<input type="radio" class="" name="is_mod" value="1">TRUE
							</label>
							<label class="radio-inline">
								<input type="radio" class="" name="is_mod" value="0">FALSE
							</label>
						</div>
					</div>

					<div class="form-group">
						<label for="is_dev_text" class="col-sm-2 control-label">Enable DEV</label>
						<div class="col-sm-5">
							<input type="text" class="form-control" name="is_dev_text" value="IS_DEV" readonly>
						</div>
						<div class="col-sm-5">
							<label class="radio-inline">
								<input type="radio" class="" name="is_dev" value="" checked="checked">Empty
							</label>
							<label class="radio-inline">
								<input type="radio" class="" name="is_dev" value="1">TRUE
							</label>
							<label class="radio-inline">
								<input type="radio" class="" name="is_dev" value="0">FALSE
							</label>
						</div>
					</div>

					<script type="text/javascript">
						var modCount = 0;
						var devCount = 0;

						var backupFeildName = [];
						var backupFeildValue = [];

						var backupFields = function () {
							for (i = 0; i < backupFeildName.length; i++) {
								backupFeildValue[i] = document.getElementById(backupFeildName[i]).value;
							}
						}

						var loadFields = function () {
							for (i = 0; i < backupFeildName.length; i++) {
								document.getElementById(backupFeildName[i]).value = backupFeildValue[i];
							}
						}

						var addMod = function () {
							backupFields();
							params = document.getElementById("params");
							params.innerHTML += '<div class="form-group"><label for="mod' + modCount
								+ '" class="col-sm-2 control-label">MOD_</label><div class="col-sm-5">'
								+ '<input type="text" list="L_MOD" class="form-control" id="mod' + modCount + '" name="mod' + modCount + '" placeholder="Name" required>'
								+ '</div><div class="col-sm-5"><input type="text" class="form-control" id="mod_def' + modCount
								+ '" name="mod_def' + modCount + '" placeholder="Default value"></div></div>';
							loadFields();
							backupFeildName.push('mod' + modCount);
							backupFeildName.push('mod_def' + modCount);
							modCount++;
						}

						var addDev = function () {
							backupFields();
							params = document.getElementById("params");
							params.innerHTML += '<div class="form-group"><label for="dev' + devCount
								+ '" class="col-sm-2 control-label">DEV_</label><div class="col-sm-5">'
								+ '<input type="text" list="L_DEV" class="form-control" id="dev' + devCount + '" name="dev' + devCount + '" placeholder="Name" required>'
								+ '</div><div class="col-sm-5"><input type="text" class="form-control" id= "dev_def' + devCount
								+ '" name="dev_def' + devCount + '" placeholder="Default value"></div></div>';
							loadFields();
							backupFeildName.push('dev' + devCount);
							backupFeildName.push('dev_def' + devCount);
							devCount++;
						}
					</script>

					<datalist id="L_MOD">
						<?php foreach (BaseManager::getDict("MOD") as $val) { ?>
						<option><?= $val ?></option>
						<?php } ?>
					</datalist>
					<datalist id="L_DEV">
						<?php foreach (BaseManager::getDict("DEV") as $val) { ?>
							<option><?= $val ?></option>
						<?php } ?>
					</datalist>

					<div id="params">
					</div>
					<div class="col-sm-6 text-center" style="margin-bottom: 1em;">
						<button class="btn btn-block" onclick="addMod()"><i class="fa fa-plus"></i> MOD</button>
					</div>
					<div class="col-sm-6 text-center">
						<button class="btn btn-block" onclick="addDev()"><i class="fa fa-plus"></i> DEV</button>
					</div>

				</div>
				<!-- /.box-body -->
				<div class="box-footer">
					<!-- <button type="submit" class="btn btn-default">キャンセル</button> -->
					<button type="submit" class="btn btn-primary pull-right">Add</button>
				</div>
				<!-- /.box-footer -->
				<input type="hidden" name="action" value="create">
			</form>
			<div id="add_parameter-form-overlay" class="overlay" style="display: none;">
				<i class="fa fa-refresh fa-spin"></i>
			</div>
		</div>
		<!-- /.box -->
		<?php
	}
}
