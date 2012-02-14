#Automated iOS project deploy script for testflightapp.com

Script written in PHP. It gives you a list of developer identities to select from and automatically finds a *.mobileprovision file for it. Or you can specify both in the config.

Feel free to send me your propositions and improvements.
Will be useful to launch right after your VCS commit or push.

###How to use

1. First copy testflightappdeploy.php file into your projects directory (near your *.xcodeproj file), and edit it entering your TestFlight API tokens and stuff.

1. Make testflightappdeploy.php executable: chmod +x testflightappdeploy.php

1. That's all, run ./testflightappdeploy.php from your project directory.
