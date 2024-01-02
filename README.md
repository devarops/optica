# Óptica Horus

## Instalación

En el escritorio, crea los directorios `Fotos`, `Reportes` y `Respaldos`:

```shell
mkdir --parents /home/beatriz/Desktop/Fotos
mkdir --parents /home/beatriz/Desktop/Reportes
mkdir --parents /home/beatriz/Desktop/Respaldos
```

Copia el contenido del respaldo:

```shell
cp --recursive $RESPALDO/Fotos/* /home/beatriz/Desktop/Fotos
cp --recursive $RESPALDO/Reportes/* /home/beatriz/Desktop/Reportes
cp --recursive $RESPALDO/Respaldos/* /home/beatriz/Desktop/Respaldos
```

Los directorios deben de tener permisos del tipo:

```shell
chmod --recursive 0777 /home/beatriz/Desktop/Fotos
chmod --recursive 0777 /home/beatriz/Desktop/Reportes
chmod --recursive 0777 /home/beatriz/Desktop/Respaldos
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

### En la terminal

Primero detenemos todos los contenedores y después:

```shell
docker run \
    --rm \
    --volume /home/beatriz/Desktop/Respaldos:/data \
    --volume optica_mysql_vol:/var/lib/mysql \
    evaristor/optica:latest bash -c "/etc/init.d/mysql start && mysqldump -uoptica -pHorus optica > /data/optica.sql"
```

### Haciendo clic en un ícono del escritorio

Alternativamente, puedes hacer clic en un ícono del escritorio. Así no necesitas usar la terminal.

Copia el archivo `resources/scripts/backup_optica.desktop` al Escritorio.

El archivo anterior deben ser ejecutable: `chmod +x`.

Finalmente, haz clic en el ícono que aparecerá en el escritorio.

## Restauración

### En la terminal

Para restaurar el respaldo ejecutamos la siguiente instrucción:

```shell
docker exec optica bash -c "mysql -uoptica -pHorus optica < /var/www/html/optica/resources/dbdumps/optica.sql"
```

### Haciendo clic en un ícono del escritorio

Alternativamente, puedes hacer clic en un ícono del escritorio. Así no necesitas usar la terminal.

Copia el archivo `resources/scripts/restore_optica.desktop` al Escritorio y el archivo `resources/scripts/restore_optica.sh` a `/home/beatriz/scripts/`.

Los archivos anteriores deben ser ejecutables: `chmod +x`.

Finalmente, haz clic en el ícono que aparecerá en el escritorio.
