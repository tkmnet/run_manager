<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\system\Config;

class AddReplaceSetsPage extends AbstractPage
{
	private $base;
	public function controller($params)
	{
		if (count($params) == 2) {
			$this->base = BaseManager::getBase($params[0]);

			if ($params[1] === "add") {
				BaseManager::addReplaceSets($this->base["name"], $_POST["parameters"]);

				header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager/'.$params[0]);
				return;
			}
		} else if (count($params) == 1) {
			$this->base = BaseManager::getBase($params[0]);

			$this->setTitle("Run Manager");
			$this->printPage();
		}

		header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager');
	}

	function body()
	{
		self::writeContentHeader("Add ReplaceSets", $this->base["name"], [
			"<a href='../run_manager'>Run Manager</a>",
			"<a href='../run_manager/".$this->base["name"]."'>".$this->base["name"]."</a>"]);

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
					Select parameters to include
				</h3>
			</div>

			<form id="add_parameter-form" action="./<?= $this->base["name"] ?>/add" method="POST"
						class="form-horizontal">
				<div class="box-body">
					<?php foreach (BaseManager::getReplaceableParameter($this->base["name"]) as $param) { ?>
					<div class="form-check">
						<label class="form-check-label">
							<input type="checkbox" name="parameters[]" value="<?= $param["name"] ?>">
							<?= $param["name"] ?>
						</label>
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
