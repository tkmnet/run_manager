<?php

namespace rrsoacis\apps\tkmnet\run_manager;

use rrsoacis\component\common\AbstractPage;
use rrsoacis\system\Config;

class MainPage extends AbstractPage
{
	private $bases;
	private $base = null;
	private $cmd = '';
    private $cmd2 = '';
    private $cmd3 = '';
    private $param_count = 0;
	public function controller($params)
	{
		$this->param_count = count($params);
		if (count($params) >= 1) {
			if (count($params) >= 2) {
				$this->cmd = $params[1];
                if (count($params) >= 3) {
                    $this->cmd2 = $params[2];
                    if (count($params) >= 4) {
                        $this->cmd3 = $params[3];
                    }
                }
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
				"<a href='/app/tkmnet/run_manager'>Run Manager</a>",
				"<a href='/app/tkmnet/run_manager/".$this->base["name"]."'>".$this->base["name"]."</a>"]);
			$this->printRunList();
			if ($this->cmd2 === "postall") {
			    ?>
                <meta http-equiv="refresh" content="0.01;URL=../../../run_manager-control/postall/<?= $this->base["name"] ?>">
			    <?php
            } else if ($this->cmd2 === "update_score") {
                ?>
                <meta http-equiv="refresh" content="0.01;URL=<?= str_repeat("../", $this->param_count) =>run_manager-control/update_score/<?= $this->base["name"] ?>/<?= $this->cmd3 ?>">
                <?php
            }
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

		$allrun = 0;
        $finished = 0;
        $submitted = 0;
		$pendding = 0;
        foreach ($this->base["overview"] as $overview) {
            $allrun += $overview["number"];
            switch ($overview["state"]) {
                case 0: $finished = $overview["number"]; break;
                case 2: $submitted = $overview["number"]; break;
                case -10: $pendding = $overview["number"]; break;
            }
        }

		self::beginContent();
		?>
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Number of run</h3>
                        <div class="box-tools">
                            <button class="btn btn-info" onclick="location.href='/app/tkmnet/run_manager-control/update_score/<?= $this->base["name"] ?>'">
                                <i class="fa fa-refresh"></i> Update score</button>
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body table-responsive no-padding">
                        <div class="progress">
                            <div class="progress-bar progress-bar-success" role="progressbar" style="width:<?= $finished / $allrun * 100 ?>%">
                                Finished(<?= $finished ?>)
                            </div>
                            <div class="progress-bar progress-bar-warning" role="progressbar" style="width:<?= $submitted / $allrun * 100 ?>%">
                                Submitted(<?= $submitted ?>)
                            </div>
                            <div class="progress-bar progress-bar-info" role="progressbar" style="width:<?= $pendding / $allrun * 100 ?>%">
                                Pendding(<?= $pendding ?>)
                            </div>
                        </div>
                        <div class="text-center" style="margin-bottom:1em;margin-top: -1em;">
                            <?php foreach ($this->base["overview"] as $overview) { ?>
                                <?= $overview["state"] ?>(<?= $overview["number"] ?>),
                            <?php } ?>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
        </div>
		<div class="row">
			<div class="col-xs-12">
				<div class="box box-success">
					<div class="box-header">
						<h3 class="box-title">Pending Run List</h3>
						<div class="box-tools">
							<button class="btn btn-info" onclick="location.href='/app/tkmnet/run_manager-control/postall/<?= $this->base["name"] ?>'">
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
									<td><button class="btn btn-xs btn-info" onclick="location.href='/app/tkmnet/run_manager-control/post/<?= $run["name"] ?>'">
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
                        <h3 class="box-title">
                            <form method="post" action="../run_manager-control/rename/<?= $this->base["name"] ?>">
                                <input type="text" name="alias" value="<?= $this->base["alias"] ?>">
                            </form>
                        </h3>
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
				<a href="/app/tkmnet/run_manager-add_dict">
					<i class="fa fa-book"></i> Dict
				</a>
			<?php } else if ($this->cmd === "") { ?>
				<a href="/app/tkmnet/run_manager-control/duplicate_base/<?= $this->base["name"] ?>">
					<i class="fa fa-clone"></i> Duplicate Base
				</a>
			<?php } else if ($this->cmd === "runlist") { ?>
				<a href="/app/tkmnet/run_manager-control/getcsv/<?= $this->base["name"] ?>">
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
