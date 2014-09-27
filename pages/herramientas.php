<?php
	

?>

<h1><img src="resources/img/icon-settings.png" height="48" width="48" alt=""> Herramientas administrativas</h1>





<h2>Administración del sistema</h2>

<h3>Base de datos</h3>

<table class="noeffects">
	<tr>
		<td>
			<form name="db_export" action="resources/scripts/db_backup.php" method="get">
				<label for="btn_export">Respaldar base de datos</label><br>
				<input type="submit" name="btn_export" id="btn_export" value="Respaldar base de datos">
			</form>
		</td><td>
			<form name="db_import" action="" method="post" enctype="multipart/form-data">
				<label for="import_file">Subir respaldo</label><br>
				<input type="file" name="import_file" id="import_file" placeholder="Archivo de respaldo (.sql)">
				<input type="submit" name="btn_import" value="Subir"><br>
				<span class="small"><strong>Aviso:</strong> Este acción reemplazará los datos actuales. Realize un respaldo antes de proceder!</span>
			</form>
		</td>
	</tr>
</table>
