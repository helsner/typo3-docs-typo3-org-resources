#! /usr/bin/python
#
# coding: utf-8

# mb, 2016-12-09, 2016-12-09

from __future__ import print_function

import codecs
try:
    import collections
    withCollection = True
except ImportError:
    withCollection = False
    pass
try:
    from collections import OrderedDict
    withOrderedDict = True
except ImportError:
    withOrderedDict = False
    pass
import datetime
import grp
import os
import subprocess
import sys
import tempfile
import time

live_run = 1
talk = 0

oldmask = os.umask (002)
www_data = grp.getgrnam('www-data')
if withOrderedDict:
    P = params_dict = collections.OrderedDict()
    buildsettings = collections.OrderedDict()
else:
    P = params_dict = {}
    buildsettings = {}


# global params

P['CRON_REBUILD_INCLUDED_FILE'] = '/home/mbless/HTDOCS/git.typo3.org/Documentation/cron_rebuild_included.sh'
P['KNOWN_GITHUB_MANUALS'] = '/home/mbless/public_html/services/known-github-manuals.txt'
P['LOCAL_PATH_GITHUB_REPOS'] = '/home/mbless/HTDOCS/github.com'
P['LOCAL_PATH_REPOS'] = '/home/mbless/HTDOCS'
P['PUBLISH_BASE_default'] = 'typo3cms/drafts'
P['PUBLISH_FOLDER_VERSION'] = 'latest'
P['WEBROOT_FOLDER'] = '/home/mbless/public_html'


def logstamp(unixtime=None, fmt='%Y-%m-%d %H:%M'):
    "Return a timestamp suitable for logging like '2016-07-26 21:05'"

    if unixtime is None:
        unixtime = time.time()
    return datetime.datetime.fromtimestamp(unixtime).strftime(fmt)

def logstamp_finegrained(unixtime=None, fmt='%Y-%m-%d_%H-%M-%S_%f'):
    "Return fine grained timestamp like `2016-07-26_21-05-59_888999`."

    return logstamp(unixtime, fmt=fmt)

def addLineToFile(f1path, newline, cmpstr=None, mode='remove'):
    """Add a line to a utf-8 textfile.

    `f1path` is the path to the file. `newline` is the line to be added. You need to add the
    newline character `\n` yourself. If `newline` is None nothing will be added. You can
    set `cmpstr` to a unicode string. In that case each line is checked if it starts with
    `cmpstr`. If it does, action is taken according to `mode`. For `remove` the old line is
    dropped and the new line is added at the end, if it is not None.
    If `mode` is set to 'replace` the old line is replaced by the new line if that is not None.
    If it has been added at least once it will not be added at the end again.

    The modification is placed in a tempfile first. Then the original file is renamed
    to a backup file. Afterwards the tempfile is renamed and becomes the 'new original'
    file.

    This function does not do special error handling.

    """

    tstamp = logstamp_finegrained(os.path.getmtime(f1path))
    f1name = os.path.split(f1path)[1]
    f1path_backup = f1path + '.' + tstamp
    temp = tempfile.NamedTemporaryFile(prefix = f1name + '.', delete=False)
    temp.close()
    os.chmod(temp.name, 0664)
    replaced = False
    changed = False
    with codecs.open(temp.name, 'w', 'utf-8') as f2:
        for old in codecs.open(f1path, 'r', 'utf-8'):
            if cmpstr is not None and old.startswith(cmpstr):
                if mode == 'remove':
                    changed = True
                    continue
                elif mode == 'replace':
                    if newline is not None:
                        old = newline
                        replaced = True
                        if old != newline:
                            changed = True
            f2.write(old)
        if newline is not None and not replaced:
            f2.write(newline)
            changed = True
    if changed:
        os.rename(f1path, f1path_backup)
        os.rename(temp.name, f1path)
    else:
        os.remove(temp.name)
    return changed


#            a                                                              b           c                d                             e
# csvline    GITURL                                                         GITBRANCH   PUBLISH_FOLDER   PUBLISH_PATH                , PUBLISH_BASE'
# csvline = 'https://github.com/TYPO3-Documentation/TYPO3CMS-Code-Examples, master    ,                , github/TYPO3-Documentation/ , /typo3cms/drafts/'
# csvline = 'https://github.com/dwenzel/t3events                          , develop   ,                , github/dwenzel/             , /typo3cms/drafts/'
# csvline = 'https://github.com/dwenzel/t3events_reservation              , develop   ,                , github/dwenzel/             , /typo3cms/drafts/'
# csvline = 'https://github.com/dwenzel/t3events_course                   , master    ,                , github/dwenzel/             , /typo3cms/drafts/'
# csvline = 'https://code.tritum.de/TYPO3.CMS/Form_Documentation          , master    ,                , code.tritum.de/TYPO3.CMS/   , /typo3cms/drafts/'

csvline = 'https://code.tritum.de/TYPO3.CMS/Form_Documentation'



# user params

if 0 and 'as example':
    # giturl: no .git at the end!
    P['GITURL']         = 'https://github.com/TYPO3-Documentation/TYPO3CMS-Code-Examples'
    P['GITBRANCH']      = 'master'
    P['PUBLISH_FOLDER'] = 'CoreCodeExamples'
    P['PUBLISH_PATH']   = 'github/TYPO3-Documentation'
    P['PUBLISH_BASE']   = 'typo3cms/drafts'

splitted = csvline.split(',')
while len(splitted) < 5:
    splitted.append('')
a, b, c, d, e = splitted


# GITURL
a = a.strip()
url_protocol = ''
url_domain = ''
isGithubCom = False
PUBLISH_PATH_candidate, PUBLISH_FOLDER_candidate = os.path.split(a)
for proto in ['https', 'http']:
    if PUBLISH_PATH_candidate.startswith(proto + '://'):
        url_protocol = proto
        PUBLISH_PATH_candidate = PUBLISH_PATH_candidate[len(proto)+3:]
splitted = PUBLISH_PATH_candidate.split('/')
if len(splitted):
    url_domain = splitted[0]
    url_repo = '/'.join(splitted[1:] + [PUBLISH_FOLDER_candidate])
url_repo = url_repo.rstrip('.git')
isGithubCom = url_domain.lower() == 'github.com'
if isGithubCom:
    PUBLISH_PATH_candidate[0] = 'github'
isGithubCom = url_domain.lower() == 'github.com'

P['PUBLISH_FOLDER_candidate'] = PUBLISH_FOLDER_candidate
P['PUBLISH_PATH_candidate'] = PUBLISH_PATH_candidate
P['url_repo'] = url_repo
P['url_domain'] = url_domain
P['url_protocol'] = url_protocol


P['GITURL'] = a

# GITBRANCH
b = b.strip()
b = b if b else 'master'
P['GITBRANCH'] = b

# PUBLISH_FOLDER
c = c.strip()
c = c if c else PUBLISH_FOLDER_candidate
P['PUBLISH_FOLDER'] = c

# PUBLISH_PATH
d = d.strip()
d = d if d else PUBLISH_PATH_candidate
P['PUBLISH_PATH'] = d

# PUBLISH_BASE
e = e.strip()
e = e if e else P['PUBLISH_BASE_default']
P['PUBLISH_BASE'] = e



# derived

P['GITHUB_REPO'] = os.path.split(P['GITURL'])[1]

if isGithubCom:
    P['GITHUB_USER'] = os.path.split(os.path.split(P['GITURL'])[0])[1]
else:
    P['GITHUB_USER'] = None


# templates

P['CONF_PY_TEMPLATE'] = '/home/mbless/scripts/bin/conf-2015-10.py'
P['CRON_REBUILD_TEMPLATE'] = '/home/mbless/scripts/bin/cron_rebuild-RenderDocumentation.sh'
P['HTACCESS_TEMPLATE'] = '/home/mbless/scripts/config/_htaccess-2016-08.txt'
P['REQUEST_REBUILD_TEMPLATE'] = '/home/mbless/scripts/bin/request_rebuild.php'


P['builddir'] = '%(WEBROOT_FOLDER)s/%(PUBLISH_BASE)s/%(PUBLISH_PATH)s/%(PUBLISH_FOLDER)s/%(PUBLISH_FOLDER_VERSION)s' % P
P['gitdir'] = '%(LOCAL_PATH_GITHUB_REPOS)s/%(GITHUB_USER)s/%(GITHUB_REPO)s.git' % P
if isGithubCom:
    P['gitdir'] = '%(LOCAL_PATH_GITHUB_REPOS)s/%(PUBLISH_PATH_candidate)s/%(GITHUB_REPO)s.git' % P
else:
    P['gitdir'] = '%(LOCAL_PATH_REPOS)s/%(PUBLISH_PATH_candidate)s/%(GITHUB_REPO)s.git' % P

P['makedir'] = P['gitdir'] + '.make'
P['builddir_parent'] = os.path.split(P['builddir'])[0]

if talk:
    k_len_max = max([len(k) for k in P.keys()])
    for k, v in P.items():
        print( '%s%s = %s' % (k, ' '*(k_len_max - len(k)), v))

if live_run:
    # prepare the webfolder
    if not os.path.exists(P['builddir']):
        os.makedirs(P['builddir'])
    if not os.path.exists(P['builddir_parent'] + '/.htaccess'):
        os.symlink(P['HTACCESS_TEMPLATE'], P['builddir_parent'] + '/.htaccess')

if live_run:
    # prepare the makedir
    if not os.path.exists(P['makedir']):
        os.makedirs(P['makedir'])
        os.symlink(P['CONF_PY_TEMPLATE'],         P['makedir'] + '/conf.py')
        os.symlink(P['CRON_REBUILD_TEMPLATE'],    P['makedir'] + '/cron_rebuild.sh')
        os.symlink(P['REQUEST_REBUILD_TEMPLATE'], P['makedir'] + '/request_rebuild.php')

# create buildsettings.sh

buildsettings['MASTERDOC'] = P['gitdir'] + '/Documentation/Index'
buildsettings['LOGDIR'] = '.'
buildsettings['PROJECT'] = P['PUBLISH_FOLDER']
buildsettings['VERSION'] = P['GITBRANCH']
buildsettings['BUILDDIR'] = P['builddir']
buildsettings['GITURL'] = P['GITURL']
buildsettings['GITDIR'] = P['gitdir']
buildsettings['GITBRANCH'] = P['GITBRANCH']

buildsettings['T3DOCDIR'] = P['gitdir'] + '/Documentation'
buildsettings['PACKAGE_ZIP'] = '0'
buildsettings['PACKAGE_KEY'] = 'unused'
buildsettings['PACKAGE_LANGUAGE'] = 'default'


buildsettings_text = """\
# buildsettings.sh

# absolute path, or relative to conf.py, without suffix (.rst)
MASTERDOC=%(MASTERDOC)s

# absolute path, or relative to conf.py
LOGDIR=%(LOGDIR)s

PROJECT=%(PROJECT)s
VERSION=%(VERSION)s

# leave out, or 0 or 1
# #TER_EXTENSION=
# TER_EXTENSION=0
# TER_EXTENSION=1

# leave out, or blank, or en_US, or de_DE, or ...
# #LOCALIZATION=
# LOCALIZATION=
# LOCALIZATION=en_US
# LOCALIZATION=de_DE

# Where to publish documentation
BUILDDIR=%(BUILDDIR)s

# If GITURL is empty then GITDIR is expected to be "ready" to be processed
# This means, no GIT CLONE or GIT PULL or GIT CHECKOUT is done
GITURL=%(GITURL)s

# the path to the cloned repo (has '.git' at the end)
GITDIR=%(GITDIR)s

# the branch to be checked out
GITBRANCH=%(GITBRANCH)s

# Path to the documentation within the Git repository
T3DOCDIR=%(T3DOCDIR)s

# Packaging information
PACKAGE_ZIP=%(PACKAGE_ZIP)s
PACKAGE_KEY=%(PACKAGE_KEY)s
PACKAGE_LANGUAGE=%(PACKAGE_LANGUAGE)s
""" % buildsettings

if talk:
    print(buildsettings_text)

if live_run:
    f2path = os.path.join(P['makedir'], 'buildsettings.sh')
    if not os.path.exists(f2path):
        with codecs.open(f2path, 'w', 'utf-8') as f2:
            f2.write(buildsettings_text)


head, makedir_name = os.path.split(P['makedir'])
head, github_user = os.path.split(head)
knowngm_k = P['GITURL']
knowngm_v = 'https://docs.typo3.org/~mbless/github.com/%s/%s/request_rebuild.php' % (github_user, makedir_name)


# enable Github hook
# /home/mbless/public_html/services/known-github-manuals.txt
lineToAdd = knowngm_k + ',' + knowngm_v + '\n'
cmpstr = knowngm_k + ','
if talk:
    print()
    print('KNOWN_GITHUB_MANUALS:', P['KNOWN_GITHUB_MANUALS'])
    print('lineToAdd...........:', lineToAdd)
    print('cmpstr..............:', cmpstr)

if live_run:
    if isGithubCom:
        addLineToFile(P['KNOWN_GITHUB_MANUALS'], lineToAdd, cmpstr=cmpstr, mode='remove')


# include in cronjob
# /home/mbless/HTDOCS/git.typo3.org/Documentation/cron_rebuild_included.sh
# lineToAdd = '/home/mbless/HTDOCS/github.com/%s/%s/cron_rebuild.sh\n' % (github_user, makedir_name)
lineToAdd = '%(makedir)s/cron_rebuild.sh\n' % P

if talk:
    print()
    print('CRON_REBUILD_INCLUDED_FILE:', P['CRON_REBUILD_INCLUDED_FILE'])
    print('lineToAdd.................:', lineToAdd)

if live_run:
    f2path = P['CRON_REBUILD_INCLUDED_FILE']
    changed = addLineToFile(f2path, lineToAdd, cmpstr=lineToAdd, mode='replace')
    if changed:
        gid = grp.getgrnam("www-default").gr_gid
        os.chown(f2path, -1, gid)
        os.chmod(f2path, 0774)


print('Done:')
print('  GITURL        :', P['GITURL'])
print('  GITBRANCH     :', P['GITBRANCH'])
print('  PUBLISH_FOLDER:', P['PUBLISH_FOLDER'])
print()

