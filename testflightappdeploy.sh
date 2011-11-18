#!/bin/sh

# Created by Misha Karpenko on 15/11/11.
# https://github.com/mishakarpenko/testflightappdeploy
# http://mishakarpenko.com

# No slashes after paths please

# Time
currentTime=$(date "+%H.%M.%S-%d.%m.%y")

# Automation paths
scriptPath=$(dirname $(readlink $0))
configFilePath=$scriptPath/config.sh

projectDir=$(pwd)

# Include config
source $configFilePath

# Get project name
xcodeprojPath=$(find $projectDir -iname "*.xcodeproj")
projectName=${xcodeprojPath/\/*\//} # Remove path
projectName=${projectName/.*/} # Remove extension

# Build dirs
buildDir=$scriptPath/$projectName
tempBuildDir=$projectDir/build

# File names
appFileName=$buildDir/$projectName.app
ipaFileName=$buildDir/$projectName.ipa
dSYMFileName=$appFileName.dSYM
zipdSYMFileName=$dSYMFileName.zip

# Log
logDir=$buildDir/logs
logPath=$logDir/$currentTime.log
 
# Make log dir
mkdir -p $logDir

# Hello everybody
echo "\nAutomated iOS project TestFlight deploying"
echo "Created by Misha Karpenko"

# Compile the project
echo "\nBuilding project..."
echo "\nBuilding project..." >> $logPath

xcodebuild -target "$projectName" -sdk "$targetSDK" -configuration Release CONFIGURATION_BUILD_DIR="$buildDir" >> $logPath

# Check if build succeeded
if [ $? != 0 ]
then
  echo "Encountered an error"
  echo "Encountered an error" >> $logPath
  exit 1  
fi

echo "Done"
echo "Done" >> $logPath

# Clean temp files
rm -rf $tempBuildDir

# Sign app
echo "\nSigning..."
echo "\nSigning..." >> $logPath

/usr/bin/xcrun -sdk "$targetSDK" PackageApplication -v "$appFileName" -o "$ipaFileName" --sign "$developerIdentity" --embed "$provisioningProfile" >> $logPath

# Check if signing succeeded
if [ $? != 0 ]
then
  echo "Encountered an error"
  echo "Encountered an error" >> $logPath
  exit 1  
fi

echo "Done"
echo "Done" >> $logPath

# Zip dSYM
zip -r $zipdSYMFileName $dSYMFileName >> $logPath

# Upload to TestFlight
echo "\nUploading to TestFlight...\n"
echo "\nUploading to TestFlight..." >> $logPath

curl http://testflightapp.com/api/builds.json -F file="@$ipaFileName" -F dsym="@$zipdSYMFileName" -F api_token="$testFlightAPIToken" -F team_token="$testFlightTeamToken" -F notes="This build was uploaded via the upload API." -F notify=True -F distribution_lists="$distributionLists" >> $logPath

echo "\nDone"
echo "Done" >> $logPath
echo ""
