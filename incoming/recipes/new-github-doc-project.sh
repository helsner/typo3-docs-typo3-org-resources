#!/usr/bin/env bash

GITHUB_USER=wmdbsystems
GITHUB_REPO=TYPO3.Guide.Composer
GITHUB_BRANCH=master
PUBLIC_MANUAL_NAME=TYPO3ComposerGuide


# appears in the manual
PROJECT=$PUBLIC_MANUAL_NAME
# appears in the manual
VERSION=latest

mkdir -p \
   /home/mbless/public_html/typo3cms/drafts/github/$GITHUB_USER/$PUBLIC_MANUAL_NAME/latest

ln -s \
   /home/mbless/scripts/config/_htaccess \
   /home/mbless/public_html/typo3cms/drafts/github/$GITHUB_USER/$PUBLIC_MANUAL_NAME/.htaccess

ln -s \
   /home/mbless/public_html/typo3cms/drafts/github/$GITHUB_USER/$PUBLIC_MANUAL_NAME/latest \
   /home/mbless/public_html/typo3cms/drafts/github/$GITHUB_USER/$PUBLIC_MANUAL_NAME/stable

git clone \
   https://github.com/$GITHUB_USER/$GITHUB_REPO.git \
   /home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git

mkdir -p \
   /home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make

ln -s \
   /home/mbless/scripts/bin/request_rebuild.php \
   /home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make/request_rebuild.php

ln -s \
   /home/mbless/scripts/bin/cron_rebuild-2015-10.sh \
   /home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make/cron_rebuild.sh

ln -s \
   /home/mbless/scripts/bin/conf-2015-10.py \
   /home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make/conf.py

ln -s \
   /home/mbless/scripts/config/Makefile-2015-10 \
   /home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make/Makefile

DESTFILE=/home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make/buildsettings.sh

echo "# buildsettings.sh" >$DESTFILE
echo "" >>$DESTFILE
echo "# absolute path, or relative to conf.py, without suffix (.rst)" >>$DESTFILE
echo "MASTERDOC=../${GITHUB_REPO}.git/Documentation/Index" >>$DESTFILE
echo "# absolute path, or relative to conf.py" >>$DESTFILE
echo "LOGDIR=." >>$DESTFILE
echo "" >>$DESTFILE
echo "PROJECT=${PROJECT}" >>$DESTFILE
echo "VERSION=${VERSION}" >>$DESTFILE
echo "" >>$DESTFILE
echo "# Where to publish documentation" >>$DESTFILE
echo "BUILDDIR=/home/mbless/public_html/typo3cms/drafts/github/$GITHUB_USER/$PUBLIC_MANUAL_NAME/latest" >>$DESTFILE
echo "" >>$DESTFILE
echo "# If GITURL is empty then GITDIR is expected to be "ready" to be processed" >>$DESTFILE
echo "GITURL=https://github.com/$GITHUB_USER/$GITHUB_REPO.git" >>$DESTFILE
echo "GITDIR=/home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git" >>$DESTFILE
echo "GITBRANCH=$GITHUB_BRANCH" >>$DESTFILE
echo "" >>$DESTFILE
echo "# Path to the documentation within the Git repository" >>$DESTFILE
echo "T3DOCDIR=\$GITDIR/Documentation" >>$DESTFILE
echo "" >>$DESTFILE
echo "# Packaging information" >>$DESTFILE
echo "PACKAGE_ZIP=0" >>$DESTFILE
echo "PACKAGE_KEY=doesntmatter" >>$DESTFILE
echo "PACKAGE_LANGUAGE=default" >>$DESTFILE

val001=https://github.com/$GITHUB_USER/$GITHUB_REPO
val002=https://docs.typo3.org/~mbless/github.com/$GITHUB_USER/$GITHUB_REPO.git.make/request_rebuild.php
echo "${val001},${val002}" >>/home/mbless/public_html/services/known-github-manuals.txt

echo "/home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make/cron_rebuild.sh" \
     >>/home/mbless/HTDOCS/git.typo3.org/Documentation/cron_rebuild_included.sh

cd /home/mbless/HTDOCS/github.com/$GITHUB_USER/$GITHUB_REPO.git.make
php request_rebuild.php

