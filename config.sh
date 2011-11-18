#!/bin/sh

# Created by Misha Karpenko on 15/11/11.
# https://github.com/mishakarpenko/testflightappdeploy
# http://mishakarpenko.com

# No slashes after paths please

scriptPath=$(dirname $(readlink $0))
targetSDK="iphoneos5.0"
developerIdentity="iPhone Developer: Your name (XXXXXXXXX)"
provisioningProfile="$scriptPath/your_mobileprovision.mobileprovision"
distributionLists=""

testFlightAPIToken="api_token"
testFlightTeamToken="team_token"
