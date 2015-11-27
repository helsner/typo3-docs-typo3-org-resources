#!/usr/bin/env bash

mkdir -p \
   /home/mbless/public_html/typo3cms/drafts/github/wmdbsystems/RSTingWithPhpStormGuide/latest

ln -s \
   /home/mbless/scripts/config/_htaccess \
   /home/mbless/public_html/typo3cms/drafts/github/wmdbsystems/RSTingWithPhpStormGuide/.htaccess

ln -s \
   /home/mbless/public_html/typo3cms/drafts/github/wmdbsystems/RSTingWithPhpStormGuide/latest
   /home/mbless/public_html/typo3cms/drafts/github/wmdbsystems/RSTingWithPhpStormGuide/stable

cp -rp \
   /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Tutorial.ContributionWorkflow.git.make \
   /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make

rm /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/warnings.txt
rm /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/dirs-of-last-build.txt
rm /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/included-files-check.log.txt
rm /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/Settings.pprinted.txt
rm /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/build.checksum

# nano /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/buildsettings.sh

git clone \
   https://github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git \
   /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git

# /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/cron_rebuild.sh
# /home/mbless/HTDOCS/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/request_rebuild.php
# https://docs.typo3.org/~mbless/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/request_rebuild.php
# https://github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm,https://docs.typo3.org/~mbless/github.com/wmdbsystems/TYPO3.Guide.rsting-with-phpstorm.git.make/request_rebuild.php
# /home/mbless/HTDOCS/git.typo3.org/Documentation/cron_rebuild_included.sh

