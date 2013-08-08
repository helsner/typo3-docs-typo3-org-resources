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

# Supported locales: http://sphinx-doc.org/latest/config.html#intl-options
SPHINX_LOCALES="bn ca cs da de es et eu fa fi fr hr hu it ja ko lt lv nb_NO ne nl pl pt_BR ru sk sl sv tr uk_UA zh_CN zh_TW"

# ------------------------------------------------------
#
# The cron job sends an email. Include a bit of information
# at the top so we know what project is being build.
#
# ------------------------------------------------------
function projectinfo2stdout() {
    echo "=================================================="
    echo "Project   : $PROJECT"
    echo "Version   : $VERSION"
    echo "GitDir    : $GITDIR"
    echo "Repository: $GITURL"
}

# ------------------------------------------------------
#
# Lazily move files using hard-linking to other versions
# of the same files whenever possible.
#
# ------------------------------------------------------
function lazy_mv() {
    local FROM_DIR="$1"
    local TO_DIR="$2"

    # Move directory to its final place
    rm -rf $TO_DIR
    mv $FROM_DIR $TO_DIR

    # Find duplicates one level higher and replace them with hard-links
    fdupes -rLq $TO_DIR/..
}

# ------------------------------------------------------
#
# This function takes care of compiling the project
# as PDF, removing any intermediate LaTeX files.
#
# ------------------------------------------------------
function compilepdf() {
    local EXITCODE
    local PDFFILE
    local TARGETPDF

    grep -A3 latex_elements $MAKE_DIRECTORY/10+20+30_conf_py.yml | egrep "^    preamble: \\\\usepackage{typo3}" >/dev/null
    if [ $? -ne 0 ]; then
        echo "PDF rendering is not configured, skipping."
        return
    fi

    make -e latex
    # Fix generated Makefile for batch processing
    sed -i"" 's/pdflatex /pdflatex -interaction=nonstopmode -halt-on-error /' $BUILDDIR/latex/Makefile
    make -C $BUILDDIR/latex all-pdf
    EXITCODE=$?

    PDFFILE=$BUILDDIR/latex/$PROJECT.pdf
    if [ "$PACKAGE_LANGUAGE" == "default" ]; then
        TARGETPDF=manual.$PROJECT-$VERSION.pdf
    else
        TARGETPDF=manual.$PROJECT-$VERSION.${PACKAGE_LANGUAGE}.pdf
    fi

    if [ $EXITCODE -ne 0 ]; then
        # Store log into pdflatex.txt, may be useful to investigate
        cat $BUILDDIR/latex/*.log >> $MAKE_DIRECTORY/pdflatex.txt
        echo "Could not compile as PDF, skipping."
    elif [ ! -f "$PDFFILE" ]; then
        EXITCODE=1
        echo "Could not find output PDF, skipping."
    else
        # Move PDF to a directory "_pdf" (instead of "latex")
        mkdir $BUILDDIR/_pdf
        mv $PDFFILE $BUILDDIR/_pdf/$TARGETPDF

        # Create a .htaccess that redirects everything to the real PDF
        # Remove "/home/mbless/public_html" at the beginning
        TARGETDIR=$(echo $ORIG_BUILDDIR | cut -b25-)/_pdf

        pushd $BUILDDIR/_pdf >/dev/null
        echo "RewriteEngine On"                                    >  .htaccess
        echo "RewriteCond %{REQUEST_FILENAME} !-f"                 >> .htaccess
        echo "RewriteRule ^(.*)\$ $TARGETDIR/$TARGETPDF [L,R=301]" >> .htaccess
        popd >/dev/null
    fi

    # Remove LaTeX intermediate files
    rm -rf $BUILDDIR/latex

    return $EXITCODE
}

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

    # Move PDF if needed
    if [ -d "$BUILDDIR/_pdf" ]; then
        mkdir -p /tmp/$PACKAGE_KEY/$PACKAGE_LANGUAGE/pdf
        pushd /tmp/$PACKAGE_KEY/$PACKAGE_LANGUAGE > /dev/null
        find html/_pdf/ -type f -name \*.pdf -exec mv {} pdf/ \;
        rm -rf html/_pdf/
        popd >/dev/null
    fi

    pushd /tmp >/dev/null
    zip -r -9 -q $ARCHIVE $PACKAGE_KEY
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

function renderdocumentation() {
    BASE_DIR="$1"
    T3DOCDIR="$2"
    IS_TRANSLATION=$3

    echo
    echo "======================================================"
    echo "Now rendering language $PACKAGE_LANGUAGE"
    echo "======================================================"
    echo

    if [ "$PACKAGE_LANGUAGE" != "default" ]; then
        # We want localized static labels with Sphinx
        # and LaTeX
        export LANGUAGE=$PACKAGE_LANGUAGE
    fi

    # cron: add to stdout which goes via mail to Martin
    #cat "$MAKE_DIRECTORY"/included-files-check.log.txt

    BACKUP_BUILDDIR=$BUILDDIR

    if [ $IS_TRANSLATION -eq 1 ]; then
        local LAST_SEGMENT=$(basename $BUILDDIR)
        BUILDDIR=$BUILDDIR/../$PACKAGE_LANGUAGE/$LAST_SEGMENT

        # Override Settings.yml (conf.py is hardcoded to ./Documentation/Settings.yml)
        if [ -r "$T3DOCDIR/Settings.yml" ]; then
            cp $T3DOCDIR/Settings.yml $BASE_DIR/
        fi
    fi

    # Replace all slashes to dashes for a temporary build directory name
    ORIG_BUILDDIR=$BUILDDIR
    BUILDDIR=/tmp/${BUILDDIR//[\/.]/-}

    # Export variables to be used by Makefile later on
    export BUILDDIR
    export T3DOCDIR

    cd $MAKE_DIRECTORY
    rm -rf $BUILDDIR
    #make -e clean
    make -e html

    if [ "$PACKAGE_ZIP" == "1" ]; then
        # Prepare PDF using LaTeX
        compilepdf

        # Package the documentation
        packagedocumentation
    fi

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
    lazy_mv $BUILDDIR $ORIG_BUILDDIR
    chgrp -R www-default $ORIG_BUILDDIR

    # Recreate "stable" link if needed
    STABLE_VERSION=$(find $ORIG_BUILDDIR/.. -maxdepth 1 -type d -exec basename {} \; \
        | grep -E "^[0-9]+\." | sort -rV | head -n1)
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

    BUILDDIR=$BACKUP_BUILDDIR
}

if [ -r "REBUILD_REQUESTED" ]; then

    projectinfo2stdout

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
        # Discard any change
        git reset --hard origin/$GITBRANCH
        git status
    elif [ ! -r "$GITDIR" ]; then
        echo "No Git URL provided and non-existing directory: $GITDIR" 2>&1
        exit 3
    fi

    # Check for valid documentation
    if [ ! -r "$T3DOCDIR/Index.rst" ] && [ ! -r "$T3DOCDIR/README.rst" ]; then
        if [ -r "./README.rst" ]; then
            T3DOCDIR=$GITDIR
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
    /home/mbless/scripts/bin/check_include_files.py --verbose "$T3DOCDIR" > "${MAKE_DIRECTORY}/included-files-check.log.txt"
    if [ $? -ne 0 ]; then
        echo "Problem with include files"
        # Remove request
        rm -I "$MAKE_DIRECTORY/REBUILD_REQUESTED"
        exit 5
    fi

    if [ -n "$GITURL" ]; then
        if [ -r "$GITDIR" ]; then
            pushd $T3DOCDIR >/dev/null

            # Temporarily remove localization directories from Sphinx to prevent warnings with unreferenced files and duplicate labels
            find . -regex ".*/Localization\.[a-zA-Z_]*" -exec rm -rf {} \;

            popd >/dev/null
        fi
    fi

    BACKUP_T3DOCDIR=$T3DOCDIR
    renderdocumentation $T3DOCDIR $T3DOCDIR 0

    if [ -n "$GITURL" ]; then
        if [ -r "$GITDIR" ]; then
            pushd $T3DOCDIR >/dev/null

            # Fetch back localization directories
            git reset --hard origin/$GITBRANCH

            popd >/dev/null
        fi
    fi

    if [ "$PACKAGE_LANGUAGE" == "default" ]; then
        for L in $SPHINX_LOCALES; do
            T3DOCDIR=$BACKUP_T3DOCDIR
            PACKAGE_LANGUAGE=$L
            if [ -r "$T3DOCDIR/Localization.$L/Index.rst" ]; then
                renderdocumentation $T3DOCDIR $T3DOCDIR/Localization.$L 1
            fi
        done
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

