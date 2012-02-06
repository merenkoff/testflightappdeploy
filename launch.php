<?php

###########################################################
# Automated iOS project deploy script for TestFlightApp.com
# Created by Misha Karpenko on 15/11/11
# https://github.com/mishakarpenko/testflightappdeploy
# http://mishakarpenko.com
###########################################################

ini_set('display_errors', '0');

echo 'Automated iOS project deploy script for TestFlightApp.com' . PHP_EOL;

###########################################################
# Dirs
###########################################################

// Your Xcode project directory
$projectDir = getcwd();

// Get project name from *.xcodeproj file
$projectName;
$handle = opendir($projectDir);
if ( ! $handle) die('Error: couldn\'t read project directory' . PHP_EOL);
while (($fileName = readdir($handle)) !== false) {
	$matches = array();
	preg_match('/(?P<projectName>.*)\.xcodeproj/', $fileName, $matches);
	if (isset($matches['projectName'])) {
		$projectName = $matches['projectName'];
	}
}
closedir($handle);
if ( ! isset($projectName)) die('Error: couldn\'t find *.xcodeproj file' . PHP_EOL);

// Script path
$output = array();
exec('cd ' . SCRIPT_PATH . ' && pwd', $output);
$scriptPath = $output[0];

// Build dirs
$buildDir = $scriptPath . '/builds';
$projectBuildDir = $buildDir . '/' . $projectName;
$tempBuildDir = $projectDir . '/build';

// IPA
$ipaFileName = $projectBuildDir . '/' . $projectName . '.ipa';

$logDir = $projectBuildDir . '/logs';
$logPath = $logDir . '/' . date('Y.m.d-H.i.s') . '.log';

if ( ! file_exists($buildDir)) mkdir($buildDir);
if ( ! file_exists($projectBuildDir)) mkdir($projectBuildDir);
if ( ! file_exists($logDir)) mkdir($logDir);

###########################################################
# Identity
###########################################################

// Get developer identities
$developerIdentities = array();

$output = array();
exec('security find-identity -v -p codesigning', $output);
if (empty($output)) die('Error: security output is empty' . PHP_EOL);
	
foreach ($output as $item) {
	$matches = array();
	preg_match('/"(?P<identity>.*)"/', $item, $matches);
	if (isset($matches['identity'])) {
		$developerIdentities[] = $matches['identity'];
	}
}
if (empty($developerIdentities)) die('Error: couldn\'t find any developer identity' . PHP_EOL);

// Choose identity
echo 'Choose your identity:' . PHP_EOL;
for ($i = 0; $i < count($developerIdentities); $i++) {
	echo ($i + 1) . ') ' . $developerIdentities[$i] . PHP_EOL;
}

$developerIdentityChoice = -1;
while ($developerIdentityChoice <= 0 ||
	   $developerIdentityChoice > count($developerIdentities)) {
	echo 'Please enter a number (from 1 to ' . count($developerIdentities) . '): ';
	$developerIdentityChoice = fgets(STDIN);
}

$developerIdentity = $developerIdentities[intval($developerIdentityChoice) - 1];

###########################################################
# Find certificate name
###########################################################

// Get pem
$output = array();
exec('security find-certificate -c "' . $developerIdentity . '" -p', $output);

// Remove BEGIN and END OF CERTIFICATE
unset($output[count($output) - 1]);
unset($output[0]);
$pem = implode('', $output);

$output = array();
exec('cd ~/Library/MobileDevice/Provisioning\ Profiles/ && pwd', $output);
$provisioningProfilesDir = $output[0];
if ( ! file_exists($provisioningProfilesDir)) die('Error: couldn\'t find provisioning profiles directory' . PHP_EOL);

$provisioningProfileFileName;
$handle = opendir($provisioningProfilesDir);
if ( ! $handle) die('Error: couldn\'t read provisioning profiles directory' . PHP_EOL);
while (($fileName = readdir($handle)) !== false) {
	if (pathinfo($fileName, PATHINFO_EXTENSION) != 'mobileprovision') continue;

	$contents = file_get_contents($provisioningProfilesDir . '/' . $fileName);
	$contents = str_replace("\t", '', $contents);
	$contents = str_replace("\n", '', $contents);

	$found = strpos($contents, $pem);
	if ($found !== false) {
		$provisioningProfileFileName = $provisioningProfilesDir . '/' . $fileName;
	}
}
closedir($handle);

if ( ! isset($provisioningProfileFileName)) die('Error: couldn\'t find provisioning profile' . PHP_EOL);
echo 'Found provisioning profile:' . PHP_EOL . $provisioningProfileFileName . PHP_EOL;

###########################################################
# Build project
###########################################################

echo 'Building project...' . PHP_EOL;
$command = 'xcodebuild -target "' . $projectName . '" -sdk "' . TARGET_SDK . '" -configuration Release CONFIGURATION_BUILD_DIR="' . $projectBuildDir . '" >> "' . $logPath . '"' . PHP_EOL;
echo $command;
exec($command);

// Make app filename
$appFileName;
$handle = opendir($projectBuildDir);
if ( ! $handle) die('Error: couldn\'t read project build dir' . PHP_EOL);
while (($fileName = readdir($handle)) !== false) {
	if (pathinfo($fileName, PATHINFO_EXTENSION) != 'app') continue;
	$appFileName = $projectBuildDir . '/' . $fileName;
}
closedir($handle);

// Clean temp files
exec('rm -rf ' . $tempBuildDir);

###########################################################
# Sign app
###########################################################

echo "Packaging and signing..." . PHP_EOL;
$command = '/usr/bin/xcrun -sdk "' . TARGET_SDK . '" PackageApplication -v "' . $appFileName . '" -o "' . $ipaFileName . '" --sign "' . $developerIdentity . '" --embed "' . $provisioningProfileFileName . '" >> "' . $logPath . '"' . PHP_EOL;
echo $command;
exec($command);

###########################################################
# Upload to TestFlightApp
###########################################################

echo 'Uploading to TestFlight...' . PHP_EOL;

echo 'Build notes: ';
$buildNotes = fgets(STDIN);

$notify;
while ($notify != 'y' && $notify != 'n') {
	echo 'Notify testers from ' . TARGET_SDK . '? y/n: ';
	$notify = trim(fgets(STDIN));
}
$notify = $notify == 'y' ? 'true' : 'false';

$command = 'curl http://testflightapp.com/api/builds.json --progress-bar -F file="@' . $ipaFileName . '" -F api_token="' . TESTFLIGHT_API_TOKEN . '" -F team_token="' . TESTFLIGHT_TEAM_TOKEN . '" -F notes="' . $buildNotes . '" -F notify=' . $notify . ' -F distribution_lists="' . TESTFLIGHT_DISTRIBUTION_LISTS . '" >> "' . $logPath . '"' . PHP_EOL;
exec($command);

echo 'Done.' . PHP_EOL;