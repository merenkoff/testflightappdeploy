<?php

###########################################################
# Automated iOS project deploy script for TestFlightApp.com
# Created by Misha Karpenko on 15/11/11
# https://github.com/mishakarpenko/testflightappdeploy
# http://mishakarpenko.com
###########################################################

ini_set('display_errors', '0');

###########################################################
# Helper function
###########################################################

/**
 * @param string $message
 */
function log_message($message)
{
	echo PHP_EOL . $message . PHP_EOL;
}

/**
 * @param string $message
 */
function die_with_error($message)
{
	log_message('Error: ' . $message);
	die();
}

function exec_command($command, $returnAsString, $dieIfEmpty = TRUE, $echoCommand = FALSE)
{
	if ($echoCommand) log_message($command);

	$output = array();
	exec($command, $output);

	if ($dieIfEmpty && empty($output)) {
		die_with_error('"' . $command . '" output is empty');
	}

	if ($returnAsString) {
		$output = implode('', $output);
	}
	return $output;
}

###########################################################
# Start
###########################################################

log_message("\033[32mAutomated iOS project deploy script for TestFlightApp.com\033[37m");

###########################################################
# Dirs
###########################################################

// Your Xcode project directory
$projectDirPath = getcwd();

// Get project name from *.xcodeproj file
$projectName;
$handle = opendir($projectDirPath);
if ( ! $handle) die_with_error('Couldn\'t read project directory');
while (($fileName = readdir($handle)) !== false) {
	$matches = array();
	preg_match('/(?P<projectName>.*)\.xcodeproj/', $fileName, $matches);
	if (isset($matches['projectName'])) {
		$projectName = $matches['projectName'];
	}
}
closedir($handle);
if ( ! isset($projectName)) die_with_error('Couldn\'t find *.xcodeproj file');

// Get full script dir path
$scriptFullPath = exec_command('cd ' . SCRIPT_DIR_PATH . ' && pwd', TRUE);

// Build dirs
$buildDirPath = $scriptFullPath . '/builds';
$projectBuildDirPath = $buildDirPath . '/' . $projectName;
$tempBuildDirPath = $projectDirPath . '/build';

// IPA
$ipaPath = $projectBuildDirPath . '/' . $projectName . '.ipa';

$logDirPath = $projectBuildDirPath . '/logs';
$logPath = $logDirPath . '/' . date('Y.m.d-H.i.s') . '.log';

if ( ! file_exists($buildDirPath)) mkdir($buildDirPath);
if ( ! file_exists($projectBuildDirPath)) mkdir($projectBuildDirPath);
if ( ! file_exists($logDirPath)) mkdir($logDirPath);

###########################################################
# Identity
###########################################################

$developerIdentity;
if (defined('DEVELOPER_IDENTITY')) {
	$developerIdentity = DEVELOPER_IDENTITY;
} else {
	// Get developer identities
	$developerIdentities = array();

	// Get codesigning identities from keychain
	$output = exec_command('security find-identity -v -p codesigning');
		
	foreach ($output as $item) {
		$matches = array();
		preg_match('/"(?P<identity>.*)"/', $item, $matches);
		if (isset($matches['identity'])) {
			$developerIdentities[] = $matches['identity'];
		}
	}
	if (empty($developerIdentities)) die_with_error('Couldn\'t find any developer identity');

	// Choose identity
	log_message("Choose your identity:");
	for ($i = 0; $i < count($developerIdentities); $i++) {
		echo ($i + 1) . ') ' . $developerIdentities[$i] . PHP_EOL;
	}

	$developerIdentityChoice = -1;
	while ($developerIdentityChoice <= 0 ||
		   $developerIdentityChoice > count($developerIdentities)) {
		echo 'Please enter a number (from 1 to ' . count($developerIdentities) . '): ';
		$developerIdentityChoice = trim(fgets(STDIN));
	}

	$developerIdentity = $developerIdentities[intval($developerIdentityChoice) - 1];
}

log_message('Using identity "' . $developerIdentity . '"');

###########################################################
# Find certificate name
###########################################################

$provProfilePath;
if (defined('PROV_PROFILE_PATH')) {
	$provProfilePath = PROV_PROFILE_PATH;	
} else {
	// Get pem
	$output = exec_command('security find-certificate -c "' . $developerIdentity . '" -p');

	// Remove BEGIN and END OF CERTIFICATE
	unset($output[count($output) - 1]);
	unset($output[0]);
	$pem = implode('', $output);

	$provProfilesDirPath = exec_command('cd ~/Library/MobileDevice/Provisioning\ Profiles/ && pwd', TRUE);

	$provProfilePath;
	$handle = opendir($provProfilesDirPath);
	if ( ! $handle) die_with_error('Couldn\'t read provisioning profiles directory');
	while (($fileName = readdir($handle)) !== false) {
		if (pathinfo($fileName, PATHINFO_EXTENSION) != 'mobileprovision') continue;

		$contents = file_get_contents($provProfilesDirPath . '/' . $fileName);
		$contents = str_replace("\t", '', $contents);
		$contents = str_replace("\n", '', $contents);

		$found = strpos($contents, $pem);
		if ($found !== false) {
			$provProfilePath = $provProfilesDirPath . '/' . $fileName;
		}
	}
	closedir($handle);

	if ( ! isset($provProfilePath)) die_with_error('Couldn\'t find provisioning profile');
}

log_message('Using provisioning profile:' . PHP_EOL . $provProfilePath);

###########################################################
# Build project
###########################################################

$targetSDK;
if (defined('TARGET_SDK')) {
	$targetSDK = TARGET_SDK;
} else {
	$targetSDK = 'iphoneos';
}

log_message("\033[32mBuilding project...\033[37m");
exec_command('xcodebuild -target "' . $projectName . '" -sdk "' . $targetSDK . '" -configuration Release CONFIGURATION_BUILD_DIR="' . $projectBuildDirPath . '" >> "' . $logPath . '"', FALSE, FALSE, TRUE);

// Make app filename
$appPath;
$handle = opendir($projectBuildDirPath);
if ( ! $handle) die_with_error('Couldn\'t read project build dir');
while (($fileName = readdir($handle)) !== false) {
	if (pathinfo($fileName, PATHINFO_EXTENSION) != 'app') continue;
	$appPath = $projectBuildDirPath . '/' . $fileName;
}
closedir($handle);
if ( ! file_exists($appPath)) die_with_error('Didn\'t create APP file');

// Clean temp files
exec_command('rm -rf ' . $tempBuildDirPath, FALSE, FALSE);

###########################################################
# Sign app
###########################################################

log_message("\033[32mPackaging and signing...\033[37m");
exec_command('/usr/bin/xcrun -sdk "' . $targetSDK . '" PackageApplication -v "' . $appPath . '" -o "' . $ipaPath . '" --sign "' . $developerIdentity . '" --embed "' . $provProfilePath . '" >> "' . $logPath . '"', FALSE, FALSE, TRUE);
if ( ! file_exists($ipaPath)) die_with_error('Didn\'t create IPA file');

###########################################################
# Upload to TestFlightApp
###########################################################

log_message("\033[32mUploading to TestFlight...\033[37m");

echo PHP_EOL . 'Build notes: ';
$buildNotes = trim(fgets(STDIN));

$notify;
while ($notify != 'y' && $notify != 'n') {
	echo 'Notify testers from ' . TESTFLIGHT_DISTRIBUTION_LISTS . '? y/n: ';
	$notify = trim(fgets(STDIN));
}
$notify = $notify == 'y' ? 'true' : 'false';

$command = 'curl http://testflightapp.com/api/builds.json --progress-bar -F file="@' . $ipaPath . '" -F api_token="' . TESTFLIGHT_API_TOKEN . '" -F team_token="' . TESTFLIGHT_TEAM_TOKEN . '" -F notes="' . $buildNotes . '" -F notify=' . $notify . ' -F distribution_lists="' . TESTFLIGHT_DISTRIBUTION_LISTS . '" >> "' . $logPath . '"';
exec($command);

log_message("\033[32mDone.\033[37m");