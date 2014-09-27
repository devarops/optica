<?php
	// Patient merge list
	if(isset($_GET['merge_id']) && is_numeric($_GET['merge_id']) && !in_array($_GET['merge_id'], $_SESSION['merge_patients'])) {
		if(!isset($_SESSION['merge_patients'])) {
			$_SESSION['merge_patients'] = [];
		}

		$_SESSION['merge_patients'][] = $_GET['merge_id'];
	} else if(isset($_GET['rem_merge_id'])) {
		$key = array_search($_GET['rem_merge_id'], $_SESSION['merge_patients']);
		if($key) {
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

	// DB upload
?>

<h1><img src="resources/img/icon-settings.png" height="48" width="48" alt=""> Herramientas administrativas</h1>


<h2>Fusionar pacientes</h2>
<p class="small">Para fusionar perfiles de pacientes, visite a los expedientes correspondientes y haz click en el boton de fusión.<br>
El historial completo del paciente quedará bajo el perfil más reciente.</p>

<?php
	if(isset($_SESSION['merge_patients'])) {
		echo '<table style="width: 35%;" class="noeffects"><thead><tr><th>#</th><th>Fecha de creción</th><th>Nombre</th><th></th></thead><tbody>', PHP_EOL;
		foreach($_SESSION['merge_patients'] as $patient_id) {
			$p = new Patient($db, $patient_id);
			echo '<tr><th>', $p->id, '</th><td>', substr($p->add_date, 0, 10), '</td><td>', $p->get_full_name(), '</td><td><a href="?page=herramientas&rem_merge_id=', $p->id, '"><img src="resources/img/icon-delete.png" height="16" width="16" alt="Borrar renglón"></a></td></tr>', PHP_EOL;
		}
		echo '<tr><td colspan="4" style="text-align: right;"><br><a href="?page=herramientas&btn_merge"><button class="yellow">Fusionar pacientes</button></td></tr>', PHP_EOL;
		echo '</tbody></table>';
	}
?>




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
