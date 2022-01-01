# Óptica Horus

## Instalación

Crea el directorio `~/Documents/base_datos_optica/`.

```shell
mkdir --parents /home/beatriz/Documents/base_datos_optica
```

Copia el respaldo de la base de datos `optica.sql` al directorio `~/Documents/base_datos_optica/`.

Carga el respaldo de la base de datos en el volumen de Docker.

```shell
docker run \
    --rm \
    --volume /home/beatriz/Documents/base_datos_optica:/data \
    --volume optica_mysql_vol:/var/lib/mysql \
    evaristor/optica:latest bash -c "/etc/init.d/mysql start && mysql -uoptica -pHorus optica < /data/optica.sql"
```

## Ejecución

```shell
docker run \
    --detach \
    --publish 80:80 \
    --restart always \
    --volume optica_mysql_vol:/var/lib/mysql \
    evaristor/optica:latest bash -c "/etc/init.d/mysql start && apache2ctl -D FOREGROUND"
```

## Abre el programa de la óptica

Abre `http://localhost/optica` en Google Chrome.

