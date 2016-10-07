#!/bin/bash

source ~/venvs/tct/venv/bin/activate
MAKEDIR=$(cd "$(dirname "$0")" && echo $PWD)
if [ ! -r "$MAKEDIR/REBUILD_REQUESTED" ]; then
  exit 0
fi

echo

rm $MAKEDIR/conf.py
ln -s /home/mbless/scripts/bin/conf-2015-10.py $MAKEDIR/conf.py

tct \
  run RenderDocumentation \
  -c makedir "$MAKEDIR" --clean-but 30

tct \
  run RenderDocumentation \
    -c makedir "$MAKEDIR" \
    -c email_user_to martin.bless@gmail.com \
    -c talk 1

rm "$MAKEDIR/REBUILD_REQUESTED" >/dev/null 2>&1

echo


