#! /usr/bin/python
# coding: ascii

"""\
Check include files in a ReST documentation project.

This script will verify that all ReST source files reside within or
below a given ``startdir``. This also holds for files that are included
via a ``.. include::`` directive and proceeds recursively. Files
included by a ``.. literalinclude::`` are checked as well except hat
the contents is not checked and no recursion will happen. Processing
will stop with the first non matching file. Exitcode 0 signals success,
exitcode 1 means "illegal path found" and exitcode 2 signals a
commandline or general error.
(check_include_files.py, mb, 2013-07-23, 2013-07-25)

"""

__version__ = '0.1.0'
__history__ = ""
__copyright__ = """\

Copyright (c), 2013-2099, Martin Bless  <martin@mbless.de>

All Rights Reserved.

Permission to use, copy, modify, and distribute this software and its
documentation for any purpose and without fee or royalty is hereby
granted, provided that the above copyright notice appears in all copies
and that both that copyright notice and this permission notice appear
in supporting documentation or portions thereof, including
modifications, that you make.

THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO
THIS SOFTWARE, INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS, IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL,
INDIRECT OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING
FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT,
NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION
WITH THE USE OR PERFORMANCE OF THIS SOFTWARE!
"""

import os
import sys
import re

ospj = os.path.join
ospe = os.path.exists

checkedfiles = []
checkedincludefiles = []
forbiddenfiles = []
forbiddenfilesparents = []

# PATHPARTS = splitpath(startdir)
# N_PARTS = len(PATHPARTS)

def normalizepath(p):
    return os.path.normcase(os.path.realpath(p))

def splitpath(p):
    """Split a path to a list of its parts

    Example::

        input : 'D:/Repositories/Demo/Documentation/Index.rst'
        result: ['D:/','Repositories','Demo','Documentation','Index.rst']

    """
    result = []
    left, right = os.path.split(p)
    while True:
        if right:
            result.insert(0, right)
            if left:
                left, right = os.path.split(left)
                continue
        if left:
            result.insert(0, left)
        break
    return result

def processRstFile(filepath, parents=None, recurse=1):
    """Check a ReST source file.

    Check whether filepath is within or below ``startdir``.
    Process include directives.

    """
    ok = False
    restfile = normalizepath(filepath)
    if parents is None:
        parents = []
    if restfile in checkedfiles:
        ok = True
        return ok, parents
    else:
        parts = splitpath(restfile)
        if not parts[:N_PARTS] == PATHPARTS:
            forbiddenfiles.append(restfile)
            forbiddenfilesparents.append(parents)
            ok = False
            return ok, parents
        else:
            checkedfiles.append(restfile)
            ok = True
    if recurse and ospe(restfile):
        f1 = file(restfile)
        strdata = f1.read()
        f1.close()

        if 1 and 'look for ``.. include::`` directives':
            # '\n .. include:: abc.txt \n\n  .. include:: abc.txt'
            filenames = re.findall('^\s*\.\.\s+include::\s*(\S+)\s*$', strdata, flags=+re.MULTILINE)
            for filename in filenames:
                if os.path.isabs(filename):
                    restfile2 = filename
                else:
                    restfile2 = ospj(os.path.dirname(restfile), filename)
                restfile2 = normalizepath(restfile2)
                if not restfile2 in checkedincludefiles:
                    checkedincludefiles.append(restfile2)
                    parents.append(restfile)
                    ok, parents = processRstFile(restfile2, parents, recurse=1)
                    if not ok:
                        break
        if 1 and 'look for ``.. literalinclude::`` directives':
            # '\n .. literalinclude:: code.js \n\n  .. literalinclude:: code.php'
            filenames = re.findall('^\s*\.\.\s+literalinclude::\s*(\S+)\s*$', strdata, flags=+re.MULTILINE)
            for filename in filenames:
                if os.path.isabs(filename):
                    restfile2 = filename
                else:
                    restfile2 = ospj(os.path.dirname(restfile), filename)
                restfile2 = normalizepath(restfile2)
                if not restfile2 in checkedincludefiles:
                    checkedincludefiles.append(restfile2)
                    parents.append(restfile)
                    ok, parents = processRstFile(restfile2, parents, recurse=0)
                    if not ok:
                        break

    return ok, parents

def main(startdir):
    ok = True
    for path, dirs, files in os.walk(startdir):
        dirs.sort()
        files.sort()
        for fname in files:
            stem, ext = os.path.splitext(fname)
            if ext == '.rst':
                f1path = ospj(path, fname)
                ok, parents = processRstFile(f1path)
            if not ok:
                break
        if not ok:
            break
    return ok, parents

def removestartdir(fname):
    L = splitpath(fname)
    if L[:N_PARTS] == PATHPARTS:
        L = L[N_PARTS:]
        result = ospj(*L)
    else:
        result = fname
    return result

def printresult():
    print

    print 'checked files:'
    print '=============='
    if checkedfiles:
        for f in checkedfiles:
            print removestartdir(f)
    else:
        print 'None.'
    print

    print 'checked include files:'
    print '======================'
    if checkedincludefiles:
        for f in checkedincludefiles:
            print removestartdir(f)
    else:
        print 'None.'
    print

    print 'forbidden include files:'
    print '========================'
    if forbiddenfilesparents:
        for i, parents in enumerate(forbiddenfilesparents):
            j = 0
            for j in range(len(parents)):
                if j == 0:
                    indent = ''
                else:
                    indent = ('    '*(j-1)) + '|-- '
                fname = removestartdir(parents[j])
                print '%s%s' % (indent, fname)
            j += 1
            indent = ('    '*(j-1)) + '|-- '
            fname = removestartdir(forbiddenfiles[i])
            print '%s%s' % (indent, fname)
    else:
        print 'None.'
    print


def get_argparse_args():
    """Get commandline args using module 'argparse'. Python >= 2.7 required."""

    class License(argparse.Action):
        def __call__(self, parser, namespace, values, option_string=None):
            print __copyright__
            parser.exit()

    class History(argparse.Action):
        def __call__(self, parser, namespace, values, option_string=None):
            print __history__
            parser.exit()

    class Info(argparse.Action):
        def __call__(self, parser, namespace, values, option_string=None):
            print
            print __doc__
            parser.exit()

    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0], add_help=False)
    parser.add_argument('--verbose', '-v', help='verbose - list filenames', dest='verbose', action='store_true')
    parser.add_argument('--help', '-h', action='help', default=argparse.SUPPRESS, help='show this help message and exit')
    parser.add_argument('--info', help='show information and exit', nargs=0, action=Info)
    parser.add_argument('--version', help='show version and exit', action='version', version=__version__)
    parser.add_argument('--license', help='show license and exit', nargs=0, action=License)
    # parser.add_argument('--history', help='show history', nargs=0, action=History)
    parser.add_argument('startdir')
    return parser.parse_args()


class Namespace(object):
    """Simple object for storing attributes."""

    def __init__(self, **kwargs):
        for name in kwargs:
            setattr(self, name, kwargs[name])

if __name__ == "__main__":
    argparse_available = False
    try:
        import argparse
        argparse_available = True
    except ImportError:
        pass
    if not argparse_available:
        try:
            import local_argparse as argparse
            argparse_available = True
        except ImportError:
            pass
    if argparse_available:
        args = get_argparse_args()
    else:
        args = Namespace()
        args.startdir = ''
    if not args.startdir:
        msg = ("\nNote:\n"
               "   '%(prog)s'\n"
               "   needs module 'argparse' (Python >= 2.7) to handle commandline\n"
               "   parameters. It seems that 'argparse' is not available. Provide\n"
               "   module 'argparse' or hardcode parameters in the code instead (exitcode=2).\n" % {'prog': sys.argv[0]} )
        print msg
        sys.exit(2)
    if not os.path.isdir(args.startdir):
        print "argument is not a directory (exitcode=2)\n"
        sys.exit(2)

    # args.startdir = r'D:\Repositories\git.typo3.org\Documentation\TYPO3\Reference\CodingGuidelines.git\Documentation'
    startdir = normalizepath(args.startdir)
    PATHPARTS = splitpath(startdir)
    N_PARTS = len(PATHPARTS)
    ok, parents = main(startdir)
    if args.verbose:
        printresult()
    if ok:
        if args.verbose:
            print "exitcode=0"
        sys.exit(0)
    else:
        if args.verbose:
            print "exitcode=1"
        sys.exit(1)
