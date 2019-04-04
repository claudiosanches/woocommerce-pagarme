#!/bin/sh
STAGED_FILES=`git diff --name-only --diff-filter=d origin/master | grep .php`

if [ "$STAGED_FILES" != "" ]; then
  echo $STAGED_FILES
  ./vendor/bin/phpcs --encoding=utf-8 -n -p $STAGED_FILES
  if [ $? != 0 ]; then
    exit 1
  fi
fi