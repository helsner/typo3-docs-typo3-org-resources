#!/bin/bash

# ------------------------------------------------------
# Builds a Sphinx project for TYPO3 projects
#
# Exit Status:
#
# 1: Configuration file cron_rebuild.conf was not found
# 2: Invalid Git directory for the project
# 3: Non-existing project directory
# 4: No documentation found
# 5: No success for include file check
# ------------------------------------------------------

# Retrieve current directory (as absolute path)
MAKE_DIRECTORY=$(unset CDPATH && cd "$(dirname "$0")" && echo $PWD)
pushd $MAKE_DIRECTORY >/dev/null

if [ ! -r "cron_rebuild.conf" ]; then
    echo "Cannot find configuration file cron_rebuild.conf in $MAKE_DIRECTORY" 2>&1
    exit 1
fi

. cron_rebuild.conf

# ------------------------------------------------------
#
# The cron job sends an email. Include a bit of information
# at the top so we know what project is being build.
#
# ------------------------------------------------------

echo "=================================================="
echo "Project   : $PROJECT"
echo "Version   : $VERSION"
echo "GitDir    : $GITDIR"
echo "Repository: $GITURL"


# Replace all slashes to dashes for a temporary build directory name
ORIG_BUILDDIR=$BUILDDIR
BUILDDIR=/tmp/${BUILDDIR//\//-}

# Export variables to be used by Makefile later on
export BUILDDIR
export T3DOCDIR

# ------------------------------------------------------
#
# This function takes care of packaging the
# HTML documentatin as a zip file and recreates
# the index of available packages.
#
# ------------------------------------------------------
function packagedocumentation() {
    local PACKAGEDIR
    if [ "${PACKAGE_LANGUAGE}" == "default" ]; then
        PACKAGEDIR=$ORIG_BUILDDIR/../packages
    else
        PACKAGEDIR=$ORIG_BUILDDIR/../../packages
    fi
    local ARCHIVE=${PROJECT}-${VERSION}-${PACKAGE_LANGUAGE}.zip

    rm -rf /tmp/$PACKAGE_KEY /tmp/$ARCHIVE
    mkdir -p /tmp/$PACKAGE_KEY/$PACKAGE_LANGUAGE/html
    cp -r $BUILDDIR/* /tmp/$PACKAGE_KEY/$PACKAGE_LANGUAGE/html
    pushd /tmp >/dev/null
    zip -r -9 $ARCHIVE $PACKAGE_KEY
    mkdir -p $PACKAGEDIR
    mv $ARCHIVE $PACKAGEDIR/
    rm -rf /tmp/$PACKAGE_KEY
    popd >/dev/null

    # Create documentation pack index
    pushd $PACKAGEDIR >/dev/null
    rm -f packages.xml
    touch packages.xml

    echo -e "<?xml version=\"1.0\" standalone=\"yes\" ?>"                   >> packages.xml
    echo -e "<documentationPackIndex>"                                      >> packages.xml
    echo -e "\t<meta>"                                                      >> packages.xml
    echo -e "\t\t<timestamp>$(date +"%s")</timestamp>"                      >> packages.xml
    echo -e "\t\t<date>$(date +"%F %T")</date>"                             >> packages.xml
    echo -e "\t</meta>"                                                     >> packages.xml
    echo -e "\t<languagePackIndex>"                                         >> packages.xml

    for p in $(find . -name \*.zip | sort);
    do
            local _VERSION=$(echo $p | sed -r "s/.*-([0-9.]*|latest)-([a-z]*)\.zip\$/\1/")
            local _LANGUAGE=$(echo $p | sed -r "s/.*-([0-9.]*|latest)-([a-z]*)\.zip\$/\2/")
            echo -e "\t\t<languagepack version=\"$_VERSION\" language=\"$_LANGUAGE\">" >> packages.xml
            echo -e "\t\t\t<md5>$(md5sum $p | cut -d" " -f1)</md5>"         >> packages.xml
            echo -e "\t\t</languagepack>"                                   >> packages.xml
    done

    echo -e "\t</languagePackIndex>"                                        >> packages.xml
    echo -e "</documentationPackIndex>"                                     >> packages.xml

    popd >/dev/null
}

# ------------------------------------------------------
#
# Checks if rebuild is needed by comparing a checksum
# of the documentation files with the last build's
# checksum.
#
# Returns 1 if rebuild is needed, otherwise 0.
#
# ------------------------------------------------------
function rebuildneeded() {
    if [ -r "$T3DOCDIR/Index.rst" ]; then
        local CHECKSUM=$(find "$T3DOCDIR" -type f -exec md5sum {} \; | md5sum | awk '{ print $1 }')
    elif [ -r "$GITDIR/README.rst" ]; then
        local CHECKSUM=$(md5sum "$GITDIR/Documentation/README.rst" | awk '{ print $1 }')
    else
        # No documentation, should not happen
        return 0
    fi

    if [ ! -r "$MAKE_DIRECTORY/build.checksum" ]; then
        # Never built
        echo $CHECKSUM > "$MAKE_DIRECTORY/build.checksum"
        return 1
    else
        local LAST_CHECKSUM=$(cat "$MAKE_DIRECTORY/build.checksum")
    fi

    if [ "$LAST_CHECKSUM" == "$CHECKSUM" ]; then
        return 0
    else
        echo $CHECKSUM > "$MAKE_DIRECTORY/build.checksum"
        return 1
    fi
}

if [ -r "REBUILD_REQUESTED" ]; then
    if [ -n "$GITURL" ]; then
        if [ ! -r "$GITDIR" ]; then
            git clone $GITURL $GITDIR
        fi
        cd $GITDIR
        if [ ! -d ".git" ]; then
            echo "Cannot proceed, not a Git directory: $GITDIR" 2>&1
            exit 2
        fi
        git fetch
        git checkout $GITBRANCH
        git pull
        git status
    elif [ ! -r "$GITDIR" ]; then
        echo "No Git URL provided and non-existing directory: $GITDIR" 2>&1
        exit 3
    fi

    # Check for valid documentation
    if [ ! -r "$T3DOCDIR/Index.rst" ] && [ ! -r "$T3DOCDIR/README.rst" ]; then
        if [ -r "./README.rst" ]; then
            export T3DOCDIR=$GITDIR
        else
            echo "No documentation found: $GITDIR" 2>&1
            exit 4
        fi
    fi

    rebuildneeded
    if [ $? -eq 0 ]; then
        echo "Documentation did not change: rebuild is not needed"
        # Remove request
        rm -I "$MAKE_DIRECTORY/REBUILD_REQUESTED"
        exit 0
    fi


    # check include files
    /home/mbless/scripts/bin/check_include_files.py --verbose "$T3DOCDIR" >"$MAKE_DIRECTORY"/included-files-check.log.txt

    if [ $? -ne 0 ]; then
        echo "Problem with include files"
        # Remove request
        rm -I "$MAKE_DIRECTORY/REBUILD_REQUESTED"
        exit 5
    fi

    # cron: add to stdout which goes via mail to Martin
    cat "$MAKE_DIRECTORY"/included-files-check.log.txt


    cd $MAKE_DIRECTORY
    rm -rf $BUILDDIR
    #make -e clean
    make -e html

    # Package the documentation
    packagedocumentation

    # Create other versions of the documentation
    # make -e gettext
    # make -e json
    make -e singlehtml
    # make -e dirhtml

    ln -s $MAKE_DIRECTORY $BUILDDIR/_make

    # Make simple README documentation accessible
    pushd $BUILDDIR >/dev/null
    if [ ! -r "Index.html" ] && [ -r "README.html" ]; then
        ln -s README.html Index.html
    fi
    popd >/dev/null

    # Switch rendered documentation in public_html
    rm -rf $ORIG_BUILDDIR
    mv $BUILDDIR $ORIG_BUILDDIR
    chgrp -R www-default $ORIG_BUILDDIR

    # Recreate "stable" link if needed
    STABLE_VERSION=$(find $ORIG_BUILDDIR/.. -maxdepth 1 -type d -exec basename {} \; \
        | grep -E "\d*\." | sort -rV | head -n1)
    if [ ! -r "$ORIG_BUILDDIR/../$STABLE_VERSION/objects.inv" ]; then
        # Highest version is not a Sphinx project => bad output thus skip!
        STABLE_VERSION=""
    fi
    if [ -z "$STABLE_VERSION" ] && [ "$VERSION" == "latest" ]; then
        STABLE_VERSION=latest
    fi
    if [ -n "$STABLE_VERSION" ]; then
        if [ ! -r "$ORIG_BUILDDIR/../stable" ] || [ -h "$ORIG_BUILDDIR/../stable" ]; then
            pushd $ORIG_BUILDDIR/.. >/dev/null
            echo "Recreating stable symbolic link in $PWD"
            rm -I stable
            ln -s $STABLE_VERSION stable
            popd >/dev/null
        fi
    fi

    # Remove request
    rm -I REBUILD_REQUESTED
fi

cp cron_rebuild.conf dirs-of-last-build.txt
echo "----------------------------------------" >> dirs-of-last-build.txt
echo "MAKE_DIRECTORY : $MAKE_DIRECTORY" >> dirs-of-last-build.txt
echo "BUILDDIR       : $BUILDDIR"       >> dirs-of-last-build.txt
echo "T3DOCDIR       : $T3DOCDIR"       >> dirs-of-last-build.txt
echo "STABLE_VERSION : $STABLE_VERSION" >> dirs-of-last-build.txt

popd >/dev/null

