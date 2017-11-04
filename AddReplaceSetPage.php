<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\system\Config;

class AddReplaceSetPage extends AbstractPage
{
	private $base = null;
	private $replaceSets = null;
	public function controller($params)
	{
		if (count($params) == 3) {
			$this->base = BaseManager::getBase($params[0]);
			foreach ($this->base["replaceSets"] as $replaceSets) {
				if ($replaceSets["id"] == $params[1]) {
					$this->replaceSets = $replaceSets;
					break;
				}
			}

			if ($params[2] === "add" && $this->replaceSets != null) {
				BaseManager::addReplaceSet($this->base["name"], $this->replaceSets["id"], $_POST);

				header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[0]);
				return;
			}
		} else if (count($params) == 2) {
			$this->base = BaseManager::getBase($params[0]);
			foreach ($this->base["replaceSets"] as $replaceSets) {
				if ($replaceSets["id"] == $params[1]) {
					$this->replaceSets = $replaceSets;
					break;
				}
			}

			$this->setTitle("Run Manager");
			$this->printPage();
		}

		header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager');
	}

	function body()
	{
		self::writeContentHeader("Add ReplaceSet", $this->base["name"]."/".$this->replaceSets["id"], [
			"<a href='../../run_manager'>Run Manager</a>",
			"<a href='../../run_manager/".$this->base["name"]."'>".$this->base["name"]."</a>"]);

		self::beginContent();
		$this->printReplaceSetsForm();
		?>
		<?php
		self::endContent();
	}

	function printReplaceSetsForm()
	{
		?>
		<div class="box box-primary">
			<div class="box-header with-border">
				<h3 class="box-title">
					Input values of parameters
				</h3>
			</div>

			<form id="add_parameter-form" action="./<?= $this->replaceSets["id"] ?>/add" method="POST"
						class="form-horizontal">
				<div class="box-body">
					<?php foreach ($this->replaceSets["parameters"] as $param) { ?>
						<div class="form-group">
							<div class="col-sm-6">
								<input type="text" class="form-control" name="KEY-<?= $param["name"] ?>" value="<?= $param["name"] ?>" readonly>
							</div>
							<div class="col-sm-6">
								<input type="text" class="form-control" name="<?= $param["name"] ?>" placeholder='Value' required>
							</div>
						</div>
					<?php } ?>
				</div>
				<!-- /.box-body -->
				<div class="box-footer">
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
