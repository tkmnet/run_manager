<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\manager\AgentManager;
use rrsoacis\manager\MapManager;
use rrsoacis\system\Config;

class AddDictPage extends AbstractPage
{
	public function controller($params)
	{
		if (count($params) == 1) {
			if ($params[0] === "add" && isset($_POST["input"])) {
				$group = trim($_POST["group"]);
				$input = explode("\n", $_POST["input"]);
				$input = array_map('trim', $input);
				$input = array_filter($input, 'strlen');
				$input = array_values($input);

				foreach ($input as $value) {
					$item = explode(":", $value);
					$item = array_map('trim', $item);
					if (count($item) == 2) {
						BaseManager::addDict($item[0], $item[1], $group);
					}
				}

				header('location: '.Config::$TOP_PATH.'app/tkmnet/run_manager-add_dict');
				return;
			} else if ($params[0] === "gettxt") {
				header('Content-Type: text/plain;');
				echo BaseManager::getDictText();
				return;
			}
		}

		$this->setTitle("Run Manager");
		$this->printPage();
	}

	function body()
	{
		self::writeContentHeader("Add Dict", "Run Manager",
			["<a href='./run_manager'>Run Manager</a>"]);

		self::beginContent();
		$this->printDictForm();
		?>
		<?php
		self::endContent();
	}

	function printDictForm()
	{
		?>
		<div class="box box-primary">
			<div class="box-header with-border">
			</div>

			<form id="add_parameter-form" action="./run_manager-add_dict/add" method="POST"
						class="form-horizontal">
				<div class="box-body">
					<div class="form-group">
						<label for="group" class="col-sm-2 control-label">Group</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" id="group" name="group">
						</div>
					</div>
					<div class="form-group">
						<label for="input" class="col-sm-2 control-label">Contents (Name : Value)</label>
						<div class="col-sm-10">
							<textarea class="form-control" id="input" name="input" rows="16"></textarea>
						</div>
					</div>
				</div>
				<!-- /.box-body -->
				<div class="box-footer">
					<button type="submit" class="btn btn-primary pull-right">Add</button>
				</div>
				<!-- /.box-footer -->
				<input type="hidden" name="action" value="create">
			</form>
		</div>
		<!-- /.box -->
		<?php
	}
}
