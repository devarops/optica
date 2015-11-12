Óptica Horus
============

# Intalación

## 1. Instalar LaTeX
Ejecutar en la terminal:

```
sudo apt-get update

sudo apt-get install texlive texlive-lang-spanish
```

## 2. Instalar LAMP (Linux, Apache, MySQL y PHP)
Ejecutar en la terminal:

```
sudo apt-get install lamp-server^
```

El "`^`" al final del comando anterior es importante.

Eventualmente aparecerá una pantalla azul de instalación de MySQL que te pedirá que definas una contraseña para el root de MySQL; Usa: _Horus_

## 3. Crear usuario y base de datos en MySQL
Ejecutar en la terminal:

```
mysql -uroot -p
```

Pedirá la contraseña del root de MySQL; la contraseña es _Horus_. Ahora estás dentro de MySQL, por eso te aparecerá el prompt `mysql>` al inicio de cada línea.

```
mysql> create user "optica"@"localhost";

mysql> create database optica;

mysql> grant all privileges on optica.* to "optica"@"localhost" identified by "Horus";

mysql> flush privileges;

mysql> exit;
```

## 4. Importar los expedientes
Ejecutar en la terminal:

```
mysql -uoptica -p optica < /RUTA_DEL_RESPALDO/optica_20XX-XX-XX.sql
```

(Reemplaza `RUTA_DEL_RESPALDO` por la dirección de la carpeta donde se encuentra el respaldo, y reemplaza las equis por la fecha del respaldo más reciente.)
Pedirá una contraseña, es _Horus_.

## 5. Descargar el programa de la óptica
Instala Mercurial para descargar el programa de la óptica. Ejecutar en la terminal:

```
sudo apt-get install mercurial
```

Entrar a la carpeta donde se descargará el programa (ver nota 1):

```
cd /var/www/html
```

Descargar el programa de la óptica desde Bitbucket:

```
sudo hg clone https://bitbucket.org/evaristor/optica
```

Bitbucket pedirá nombre de usuario y contraseña. El nombre de usuario es: _BeatrizMayoral_ y la contraseña es: _Horus_.

## 6. Cambiar al dueño y los permisos del programa
Ejecutar en la terminal (ver nota 2):

```
sudo chown -R beatriz  /var/www/html

sudo chmod -R ugo+rwx  /var/www/html
```

## 7. Copiar accesos directos para respaldar y actualizar
Para que te aparezcan las opciones de _Respaldar Expedientes_ y _Actualizar Programa_ en las aplicaciones, ejecuta en la terminal el siguiente comando (es una sola línea).

```
sudo cp /var/www/html/optica/resources/scripts/*.desktop /usr/share/applications
```

(Hay un espacio entre el *.desktop y el /usr.) Puedes arrastrar ambos íconos, _Respaldar Expedientes_ y _Actualizar Programa_, que aparecerán en la carpeta `/usr/share/applications` a la barra lateral izquierda de íconos. O los puedes llamar desde las aplicaciones.

## 8. Crear accesos directos a carpetas de Reportes y Respaldos
Para crear en el escritorio accesos directos a los directorios `/var/www/html/optica/resources/latex` y `/var/www/html/optica/resources/dbdumps`, ejecutar en la terminal:

```
ln -s /var/www/html/optica/resources/latex/ ~/Desktop/Reportes

ln -s /var/www/html/optica/resources/dbdumps/ ~/Desktop/Respaldos
```

En el primero se guardan las notas, resúmenes y enlistados, en el segundo los respaldos de los expedientes.

## 9. Abre el programa de la óptica
Abre `http://localhost/optica` en Google Chrome.

-----

Notas:

1. Puedes verificar dónde se debe crear la carpeta _optica_ con el comando `cat /etc/apache2/sites-enabled/000-default.conf | grep DocumentRoot`
1. Puedes verificar el nombre del usuario con el comando `whoami`. Reemplaza `beatriz` con el nombre del usuario.
