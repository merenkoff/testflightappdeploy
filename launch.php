<?php

###########################################################
# Automated iOS project deploy script for TestFlightApp.com
# Created by Misha Karpenko on 15/11/11
# https://github.com/mishakarpenko/testflightappdeploy
# http://mishakarpenko.com
###########################################################

// Building and signing is done in xcodebuildprovisioning script
require_once SCRIPT_DIR_PATH . '/xcodebuildprovisioning/launch.php';

###########################################################
# Upload to TestFlightApp
###########################################################

log_message("\033[32mtestflighappdeploy\033[37m");

echo PHP_EOL . 'Build notes: ';
$buildNotes = trim(fgets(STDIN));

$notify = 'false';
if (defined(TESTFLIGHT_DISTRIBUTION_LISTS)) {
	while ($notify != 'y' && $notify != 'n') {
		echo 'Notify testers from ' . TESTFLIGHT_DISTRIBUTION_LISTS . '? y/n: ';
		$notify = trim(fgets(STDIN));
	}
	$notify = $notify == 'y' ? 'true' : 'false';
}

$command = 'curl http://testflightapp.com/api/builds.json --progress-bar -F file="@' . $ipaPath . '" -F api_token="' . TESTFLIGHT_API_TOKEN . '" -F team_token="' . TESTFLIGHT_TEAM_TOKEN . '" -F notes="' . $buildNotes . '" -F notify=' . $notify . ' -F distribution_lists="' . TESTFLIGHT_DISTRIBUTION_LISTS . '" >> "' . $logPath . '"';
exec($command);

log_message("\033[32mDone.\033[37m");