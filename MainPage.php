<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\system\Config;

class MainPage extends AbstractPage
{
	private $bases;
	private $base = null;
	private $cmd = '';
	public function controller($params)
	{
		if (count($params) >= 1) {
			if (count($params) >= 2) {
				$this->cmd = $params[1];
			}
			$this->base = BaseManager::getBase($params[0]);
		} else {
			$this->bases = BaseManager::getBases();
		}
		$this->setTitle("Run Manager");
		$this->printPage();
	}

	function body()
	{
		if ($this->cmd === "runlist") {
			self::writeContentHeader("RunList", $this->base["name"], [
				"<a href='../../run_manager'>Run Manager</a>",
				"<a href='../../run_manager/".$this->base["name"]."'>".$this->base["name"]."</a>"]);
			$this->printRunList();
		} else if ($this->base != null) {
			self::writeContentHeader($this->base["name"],"Run Manager", ["<a href='../run_manager'>Run Manager</a>"]);
			$this->printBasePage();
		} else {
			self::writeContentHeader("Run Manager");
			$this->printBaseList();
		}
	}

	function printRunList()
	{
		$pendingRuns = BaseManager::getPendingRunList($this->base["name"], 0, 10);
		self::beginContent();
		?>
		<div class="row">
			<div class="col-xs-12">
				<div class="box box-success">
					<div class="box-header">
						<h3 class="box-title">Pending Run List</h3>
						<div class="box-tools">
							<button class="btn btn-info" onclick="location.href='../../run_manager-control/postall/<?= $this->base["name"] ?>'">
								<i class="fa fa-arrow-right"></i> Post all</button>
						</div>
					</div>
					<!-- /.box-header -->
					<div class="box-body table-responsive no-padding">
						<table class="table">
							<tr>
								<th>Name</th>
								<th>ReplaceSetArray</th>
								<th></th>
							</tr>
							<?php foreach ($pendingRuns as $run) { ?>
								<tr>
									<td><?= $run["name"] ?></td>
									<td><?= $run["replaceSetArray"] ?></td>
									<td><button class="btn btn-xs btn-info" onclick="location.href='../../run_manager-control/post/<?= $run["name"] ?>'">
											<i class="fa fa-arrow-right"></i> Post</button></td>
								</tr>
							<?php } ?>
						</table>
					</div>
					<!-- /.box-body -->
				</div>
				<!-- /.box -->
			</div>
		</div>
		<?php
		self::endContent();
	}

	function printBasePage()
	{
		self::beginContent();
		?>
		<div class="row">
			<div class="col-xs-12">
				<div class="box">
					<div class="box-header">
						<h3 class="box-title"><?= $this->base["alias"] ?></h3>
						<div class="box-tools">
							<button class="btn btn-success" onclick="location.href='./<?= $this->base["name"] ?>/runlist'">RunList</button>
							<button class="btn btn-default" onclick="window.open('http://'+location.host.replace(location.port,3000)+'/simulators/<?= $this->base["name"] ?>');">OACIS</button>
						</div>
					</div>
					<!-- /.box-header -->
					<div class="box-body table-responsive no-padding">
						<table class="table">
							<tr>
								<th>Parameter Name</th>
								<th>Default Value</th>
							</tr>
							<?php foreach ($this->base["parameters"] as $param) { ?>
								<tr>
									<td><?= $param["name"] ?></td>
									<td><?= $param["def"] ?></td>
								</tr>
							<?php } ?>
						</table>
					</div>
					<!-- /.box-body -->
					<div class="box-footer">
						<div class="text-right">
							Number of trials : <?= BaseManager::getTrialCount($this->base["name"]) ?>
							<?php if ($this->base["version"] >= 2) { ?>
								<button class="btn btn-xs btn-primary" onclick="location.href='../run_manager-control/addtrial/<?= $this->base["name"] ?>'"><i class="fa fa-plus"></i></button>
							<?php } ?>
						</div>
					</div>
				</div>
				<!-- /.box -->
			</div>
		</div>
		<?php $setsCount = 0; ?>
		<?php foreach ($this->base["replaceSets"] as $sets) { ?>
		<div class="row">
			<div class="col-xs-12">
				<div class="box box-primary">
					<div class="box-header">
						<h3 class="box-title">ReplaceSets <?= $setsCount++ ?></h3>
						<div class="box-tools">
							<button class="btn btn-default"
											onclick="location.href='../run_manager-add_rep/<?= $this->base["name"] ?>/<?= $sets["id"] ?>'">
								<i class="fa fa-plus"></i></button>
						</div>
					</div>
					<!-- /.box-header -->
					<div class="box-body table-responsive no-padding">
						<table class="table">
							<tr>
							<?php foreach ($sets["parameters"] as $parameter) { ?>
									<th style="font-weight: bold;"><?= $parameter["name"] ?></th>
							<?php } ?>
							</tr>
							<?php foreach ($sets["replaceSet"] as $set) { ?>
							<tr>
								<?php foreach ($set["replace"] as $replace)/* non checked value */ { ?>
									<td><?= $replace["value"] ?></td>
								<?php } ?>
							</tr>
							<?php } ?>
						</table>
					</div>
					<!-- /.box-body -->
				</div>
				<!-- /.box -->
			</div>
		</div>
	  <?php }
	  $replaceable = BaseManager::getReplaceableParameter($this->base["name"]);
		if (count($replaceable) > 0) {
		?>
		<div class="row">
			<div class="col-xs-12">
				<button class="btn btn-primary btn-block"
								onclick="location.href='../run_manager-add_reps/<?= $this->base["name"] ?>'">
					<i class="fa fa-plus"></i></button>
			</div>
		</div>

		<?php
		}
		?>
		<?php
		self::endContent();
	}

	function printBaseList()
	{
		self::beginContent();
		?>
		<div class="row">
			<div class="col-xs-12">
				<div class="box">
					<div class="box-header">
						<h3 class="box-title">Base List</h3>
						<div class="box-tools">
							<button class="btn btn-info" onclick="window.location='./run_manager-add_base'"><i class="fa fa-plus"></i></button>
						</div>
					</div>
					<!-- /.box-header -->
					<div class="box-body table-responsive no-padding">
						<table class="table table-hover">
							<tr>
								<th>Alias</th>
								<th>Name</th>
							</tr>
							<?php foreach ($this->bases as $base) { ?>
							<tr class="linked-row" data-href="./run_manager/<?= $base["name"] ?>">
								<td><?= $base["alias"] ?></td>
								<td><?= $base["name"] ?></td>
							</tr>
              <?php } ?>
						</table>
						<script type="text/javascript">
							$(".linked-row").click(function() { location.href = $(this).data("href"); });
						</script>
					</div>
					<!-- /.box-body -->
				</div>
				<!-- /.box -->
			</div>
		</div>
		<?php
		self::endContent();
	}

	function footer()
	{
		?>
		<footer class="main-footer">
			<?php if ($this->base === null) { ?>
				<a href="./run_manager-add_dict">
					<i class="fa fa-book"></i> Dict
				</a>
			<?php } else if ($this->cmd === "") { ?>
				<a href="../run_manager-control/duplicate_base/<?= $this->base["name"] ?>">
					<i class="fa fa-clone"></i> Duplicate Base
				</a>
			<?php } else if ($this->cmd === "runlist") { ?>
				<a href="../../run_manager-control/getcsv/<?= $this->base["name"] ?>">
					<i class="fa fa-table"></i> Result CSV
				</a>
			<?php } ?>
			<div class="pull-right hidden-xs">
				<b>Version</b> <?= Config::APP_VERSION ?>
			</div>
			<br>
		</footer>
		<!-- =============================================== -->
		</div>
		<!-- ./wrapper -->
		<?php include Config::$SRC_REAL_URL . 'component/common/footerscript.php';?>
		</body>
		</html>
		<?php
	}
}
