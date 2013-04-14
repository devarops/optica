UN="horus"
PW="kardon"
DEST="/var/www/resources/dbdumps"
DATE="$(date +"%Y-%m-%d")"
FILE="$DEST/opticahorus_$DATE.sql"

mysqldump -u$UN -p$PW opticahorus > $FILE
