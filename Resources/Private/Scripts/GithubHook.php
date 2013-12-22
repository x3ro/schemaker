<?php

// base repository URL, used for easier definition of $monitored below
$gh = 'https://github.com/FluidTYPO3/';
// file to write spool when detecting changes in monitored repositories; spools schemaker rebuild of XSD through scheduler task.
$spoolFile = __DIR__ . '/typo3temp/schemaker-spool.json';
touch($spoolFile);
$spoolFile = realpath($spoolFile);
// add monitored repositories here as full URLs. You can use a base like $gh.
$monitored = array($gh . 'vhs', $gh . 'flux', $gh . 'fluidbackend', $gh . 'fluidwidget');
// target HEAD, usually refs/heads/master when your "master" branch is the one receiving merges.
$targetHead = 'refs/heads/master';

// extract payload, determine trigger repository name and HEAD reference. Only process $targetHead
$payload = json_decode($_POST['payload']);
$repositoryUrl = $payload->repository->url;
$repositoryName = $payload->repository->name;
$head = $payload->ref;

// trigger on monitored repositories and monitored head only; write spool file.
if (TRUE === in_array($repositoryUrl, $monitored) && $targetHead === $head) {
	$spool = json_decode(file_get_contents($spoolFile));
	if (FALSE === is_object($spool)) {
		$spool = new stdClass();
	}
	$spool->$repositoryName = TRUE;
	file_put_contents($spoolFile, json_encode($spool));
}

// bob is your uncle - use `./typo3/cli_dispatch.phpsh extbase schema:scheduled --git-mode 1` or add a scheduler task to
// process the spool file we just wrote.
