#!/bin/sh

###########################################################
# Config section
# Please replace XXX with your credentials
# No slashes after paths please
###########################################################

# Paths
scriptPath="/path/to/testflightappdeploy"

# Xcode stuff
targetSDK="iphoneos"

# Provisioning
developerIdentity="iPhone Developer: Your name (XXX)"
provisioningProfileFileName="XXX.mobileprovision" # Choose one from ~/Library/MobileDevice/Provisioning Profiles/

# TestFlightApp stuff
testFlightAPIToken="XXX"
testFlightTeamToken="XXX"
distributionLists=""

###########################################################
# Launch
###########################################################

source "$scriptPath/launch.sh"