#!/bin/bash
docker exec optica bash -c "mysql -uoptica -pHorus optica < /var/www/html/optica/resources/dbdumps/optica.sql"
