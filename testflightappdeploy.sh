#!/bin/sh

###########################################################
# Config section
# Please replace XXX with your credentials
# No slashes after paths please
###########################################################

# Paths
scriptPath="/path/to/testflightappdeploy"

# Xcode stuff
targetSDK="iphoneos5.0"

# Provisioning
developerIdentity="iPhone Developer: Your name (XXX)"
provisioningProfilePath="/Users/XXX/Library/MobileDevice/Provisioning Profiles"
provisioningProfileFileName="XXX.mobileprovision"

# TestFlightApp stuff
testFlightAPIToken="XXX"
testFlightTeamToken="XXX"
distributionLists=""

###########################################################
# Launch
###########################################################

source "$scriptPath/launch.sh"