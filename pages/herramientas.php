<?php
	// Patient merge list
	if(isset($_GET['merge_id']) && is_numeric($_GET['merge_id']) && !in_array($_GET['merge_id'], $_SESSION['merge_patients'])) {
		if(!isset($_SESSION['merge_patients'])) {
			$_SESSION['merge_patients'] = [];
		}

		$_SESSION['merge_patients'][] = $_GET['merge_id'];
	} else if(isset($_GET['rem_merge_id'])) {
		$key = array_search($_GET['rem_merge_id'], $_SESSION['merge_patients']);
		if($key !== False) {
			unset($_SESSION['merge_patients'][$key]);
		}
	}

	// Merge patients
	if(isset($_GET['btn_merge']) && isset($_SESSION['merge_patients'])) {
		$patients    = [];
		$latest_ts   = 0;
		$most_recent;
		foreach($_SESSION['merge_patients'] as $patient_id) {
			$p                = new Patient($db, $patient_id);
			$add_ts           = strtotime($p->add_date);
			$patients[$p->id] = $p;
			if($add_ts > $latest_ts) {
				$latest_ts   = $add_ts;
				$most_recent = $p;
			}
		}

		// Remove most recent from patient list
		unset($patients[$most_recent->id]);

		foreach($patients as $p) {
			$p->merge($most_recent);
		}

		unset($_SESSION['merge_patients']);
		echo '<p class="notification success"><strong>Éxito:</strong> Se fusionó ', sizeof($patients) + 1, ' perfiles de <a href="?page=expedientes&patient_id=',
			$most_recent->id, '">', $most_recent->get_full_name(), '</a>.</p>', PHP_EOL;
	}

	// Add lens
	if(isset($_POST['btn_add_lens'])) {
		if(!empty($_POST['lens_name']) && !empty($_POST['lens_price'])) {
			if($db->query($query)) {
				echo '<p class="notification success"><strong>Éxito:</strong> Se creó el lente <em>', $_POST['lens_name'], '</em>.</p>', PHP_EOL;
			} else {
				echo '<p class="notification error"><strong>Error:</strong> No se pudo crear el lente. ¿Ya existe un lente con el mismo nombre?</p>', PHP_EOL;
			}
		} else {
			echo '<p class="notification error"><strong>Error:</strong> Por favor define nombre y precio estándar para el nuevo lente.</p>', PHP_EOL;
		}

	}

	// Remove (mark as ignored) lens
	if(isset($_GET['rem_lens']) && is_numeric($_GET['rem_lens'])) {
		if($db->query('UPDATE lens SET ignored = 1 WHERE id = ' . $_GET['rem_lens'])) {
			echo '<p class="notification success"><strong>Éxito:</strong> Se borró el lente.</p>', PHP_EOL;
		} else {
			echo '<p class="notification error"><strong>Error:</strong> No se pudo borrar el lente.</p>', PHP_EOL;
		}
	}

	// DB upload
?>

<h1><img src="resources/img/icon-settings.png" height="48" width="48" alt=""> Herramientas administrativas</h1>


<h2>Fusionar pacientes</h2>
<p class="small">Para fusionar perfiles de pacientes, visite a los expedientes correspondientes y haz click en el boton de fusión.<br>
El historial completo del paciente quedará bajo el perfil más reciente.</p>

<?php
	if(isset($_SESSION['merge_patients']) && sizeof($_SESSION['merge_patients']) > 0) {
		echo '<table style="width: 35%;" class="noeffects"><thead><tr><th>#</th><th>Fecha de creción</th><th>Nombre</th><th></th></thead><tbody>', PHP_EOL;
		foreach($_SESSION['merge_patients'] as $patient_id) {
			$p = new Patient($db, $patient_id);
			echo '<tr><th>', $p->id, '</th><td>', substr($p->add_date, 0, 10), '</td><td>', $p->get_full_name(), '</td><td><a href="?page=herramientas&rem_merge_id=', $p->id,
				'"><img src="resources/img/icon-delete.png" height="16" width="16" alt="Borrar renglón"></a></td></tr>', PHP_EOL;
		}
		echo '<tr><td colspan="4" style="text-align: right;"><br><a href="?page=herramientas&btn_merge"><button class="yellow">Fusionar pacientes</button></td></tr>', PHP_EOL;
		echo '</tbody></table>';
	}
?>


<h2>Lentes</h2>

<table style="width: 50%;">
	<thead>
		<tr><th>Nombre</th><th>Precio estándar</th><td></td></tr>
	</thead><tbody>
<?php
	$result = $db->query('SELECT * FROM lens WHERE NOT ignored ORDER BY name');
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		echo '<tr><td>', $row['name'], '</td><td>$', number_format($row['default_price'], 2), '</td><td><a href="?page=herramientas&rem_lens=', $row['id'],
			'"><img src="resources/img/icon-delete.png" height="16 width="16" alt="Borrar lente"></a></td></tr>', PHP_EOL;
	}
?>
	</tbody>
</table>

<fieldset style="width: 50%;">
	<legend>Añadir lente</legend>
	<form action="?page=herramientas" method="post">
		<table class="noeffects">
			<tr>
				<td><label for="lens_name">Nombre</label><br><input type="text" name="lens_name" id="lens_name"></td>
				<td><label for="lens_price">Precio estándar</label><br><input type="number" name="lens_price" id="lens_price"></td>
				<td><br><input type="submit" name="btn_add_lens" value="Añadir lente"></td>
			</tr>
		</table>
	</form>
</fieldset>



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
