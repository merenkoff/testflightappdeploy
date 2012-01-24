#!/bin/sh

###########################################################
# Automated iOS project deploy script for TestFlightApp.com
# Created by Misha Karpenko on 15/11/11
# https://github.com/mishakarpenko/testflightappdeploy
# http://mishakarpenko.com
###########################################################

###########################################################
# Config section
# No slashes after paths please
###########################################################

# Time
currentTime=$(date "+%H.%M.%S-%d.%m.%y")

# Your Xcode project directory
projectDir=$(pwd)

# Get project name from *.xcodeproj file
xcodeprojPath=$(find $projectDir -iname "*.xcodeproj")
projectName=${xcodeprojPath/\/*\//} # Remove path
projectName=${projectName/.*/} # Remove extension

# Build dirs
buildDir=$scriptPath/builds/$projectName
tempBuildDir=$projectDir/build

# File names
#$buildDir/$productName.app
ipaFileName=$buildDir/$projectName.ipa

# Log
logDir=$buildDir/logs
logPath=$logDir/$currentTime.log

# Make log dir
mkdir -p $logDir

###########################################################
# Build project
###########################################################
 
# Hello everybody
echo "Automated iOS project deploy script for TestFlightApp.com"

echo "Building project..." | tee -a $file

xcodebuild -target "$projectName" -sdk "$targetSDK" -configuration Release CONFIGURATION_BUILD_DIR="$buildDir" >> $logPath

# Make app filename
appFileName=$buildDir/$(ls -1 "$buildDir" | grep ".*\.app$" | head -n1)

# Check if build succeeded
if [ $? != 0 ]
then
  echo "Encountered an error" | tee -a $file
  exit 1  
fi

# Clean temp files
rm -rf $tempBuildDir

###########################################################
# Sign app
###########################################################

echo "Packaging and signing..." | tee -a $file

/usr/bin/xcrun -sdk "$targetSDK" PackageApplication -v "$appFileName" -o "$ipaFileName" >> $logPath

# Check if signing succeeded
if [ $? != 0 ]
then
  echo "Encountered an error" | tee -a $file
  exit 1  
fi

###########################################################
# Upload to TestFlightApp
###########################################################

echo "Uploading to TestFlight..." | tee -a $file

read -p "Build notes: "
buildNotes=$REPLY

read -p "Notify testers from $distributionLists? y/n: "
[[ $REPLY = "y" ]] && notify=True || notify=False

curl http://testflightapp.com/api/builds.json --progress-bar -F file="@$ipaFileName" -F api_token="$testFlightAPIToken" -F team_token="$testFlightTeamToken" -F notes="$buildNotes" -F notify=$notify -F distribution_lists="$distributionLists" >> $logPath
