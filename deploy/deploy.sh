#!/bin/bash

RSYNC="`/usr/bin/which rsync`"
ROOT="`dirname $0`/.."
SOURCE="$ROOT"
DEST="/var/www/dabr/"

EXCLUDE="--exclude=\".git**\" --exclude=\"deploy/***\""
FILTER="--filter=\"- .git - .gitignore - deploy/\""

DRYRUN="--dry-run"

if [[ $# -eq 1 ]]; then
	if [[ "$1" == "-f" ]]; then
		DRYRUN=""
	fi
fi

echo $RSYNC $DRYRUN --itemize-changes --delete -rtv $EXCLUDE $SOURCE $DEST
$RSYNC $DRYRUN --itemize-changes --delete -rtv $FILTER $SOURCE $DEST
