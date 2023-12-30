# Óptica Horus

## Instalación

En el escritorio, crea los directorios `Fotos`, `Reportes` y `Respaldos`:

```shell
mkdir --parents /home/beatriz/Desktop/Fotos
mkdir --parents /home/beatriz/Desktop/Reportes
mkdir --parents /home/beatriz/Desktop/Respaldos
```
Los directorios deben de tener permisos del tipo:

```shell
chmod 0777 /home/beatriz/Desktop/Fotos
chmod 0777 /home/beatriz/Desktop/Reportes
chmod 0777 /home/beatriz/Desktop/Respaldos
```

Copia el respaldo de la base de datos `optica.sql` al directorio `/home/beatriz/Desktop/Respaldos`.

Carga el respaldo de la base de datos en el volumen de Docker.

```shell
docker run \
    --rm \
    --volume /home/beatriz/Desktop/Respaldos:/data \
    --volume optica_mysql_vol:/var/lib/mysql \
    evaristor/optica:latest bash -c "/etc/init.d/mysql start && mysql -uoptica -pHorus optica < /data/optica.sql"
```

## Ejecución

```shell
docker run \
    --detach \
    --name optica \
    --publish 80:80 \
    --restart always \
    --volume /home/beatriz/Desktop/Fotos:/var/www/html/optica/resources/uploads/images \
    --volume /home/beatriz/Desktop/Reportes:/var/www/html/optica/resources/latex \
    --volume /home/beatriz/Desktop/Respaldos:/var/www/html/optica/resources/dbdumps \
    --volume optica_mysql_vol:/var/lib/mysql \
    evaristor/optica:latest bash -c "/etc/init.d/mysql start && apache2ctl -D FOREGROUND"
```

## Abre el programa de la óptica

Abre `http://localhost/optica` en Google Chrome.

## Respaldo
Primero detenemos todos los contenedores y después:

```shell
docker run \
    --rm \
    --volume /home/beatriz/Desktop/Respaldos:/data \
    --volume optica_mysql_vol:/var/lib/mysql \
    evaristor/optica:latest bash -c "/etc/init.d/mysql start && mysqldump -uoptica -pHorus optica > /data/optica.sql"
```

