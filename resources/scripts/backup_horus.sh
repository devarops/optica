#!/bin/bash
UN="optica"
PW="Horus"
DEST="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/../dbdumps"
DATE="$(date +"%Y-%m-%d")"
FILE="$DEST/opticahorus_$DATE.sql"
mkdir -p $DEST
mysqldump -u$UN -p$PW opticahorus > $FILE