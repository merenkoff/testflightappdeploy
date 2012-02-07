#!/usr/bin/php
<?php

###########################################################
# Config section
# Please replace XXX with your credentials
# No slashes after paths please
###########################################################

// Paths
define('SCRIPT_DIR_PATH', 'path/to/testflightappdeploy');

// Xcode stuff
// define('TARGET_SDK', 'iphoneos');

// If left commented script will prompt you for input
// Useful when using with CI
// define('DEVELOPER_IDENTITY', '');
// define('PROV_PROFILE_PATH', '');

// TestFlightApp stuff
define('TESTFLIGHT_API_TOKEN', 'xxx');
define('TESTFLIGHT_TEAM_TOKEN', 'xxx');
define('TESTFLIGHT_DISTRIBUTION_LISTS', '');

###########################################################
# Launch
###########################################################

require_once(SCRIPT_DIR_PATH . '/launch.php');