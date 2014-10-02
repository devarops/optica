<?php
	if(isset($_POST['btn_submit'])) {
		if(isset($_POST['date_from']) && isset($_POST['date_to'])) {
			$date_regex = '/^(\d{4})-(\d{2})-(\d{2})$/';
			if(!preg_match($date_regex, $_POST['date_from']) || !preg_match($date_regex, $_POST['date_to'])) {
				echo '<div class="notification warning">Las fechas no están correctamente formateadas; deben de ser del formato YYYY-MM-DD.</div>', PHP_EOL;
			} else {
				$ref = '%' . (isset($_POST['referencia']) ? $_POST['referencia'] : '') . '%';
				//$stmt = $db->prepare('SELECT r.id AS record_id, p.id AS patient_id, p.firstname, p.lastname FROM patient AS p, record AS r WHERE r.patient_id = p.id AND r.reference LIKE :ref AND r.add_date BETWEEN :date_from AND :date_to GROUP BY p.id ORDER BY r.add_Date');

				//$stmt = $db->prepare('SELECT record_id, patient_id, firstname, lastname FROM (SELECT r.id AS record_id, p.id AS patient_id, p.firstname AS firstname, p.lastname AS lastname FROM record AS r, patient AS p WHERE r.patient_id = p.id AND r.reference LIKE :ref AND r.add_date BETWEEN :date_from AND :date_to ORDER BY r.add_date DESC) AS d GROUP BY patient_id');
				$stmt = $db->prepare('SELECT record_id, patient_id, firstname, lastname FROM (SELECT r.id AS record_id, p.id AS patient_id, p.firstname AS firstname, p.lastname AS lastname FROM record AS r, patient AS p WHERE r.patient_id = p.id AND r.reference LIKE :ref AND r.add_date >= CAST(:date_from AS date) AND r.add_date <= DATE_ADD(CAST(:date_to AS date), INTERVAL 1 DAY) ORDER BY r.add_date DESC) AS d GROUP BY patient_id');
				$stmt->bindParam(':date_from', $_POST['date_from']);
				$stmt->bindParam(':date_to', $_POST['date_to']);
				$stmt->bindParam(':ref', $ref);
				if($stmt->execute()) {
					$result = $stmt->fetchAll();
					$num_results = sizeof($result);
				}
// -----
				$stats = array(
					'astigmatismo'    => 0,
					'miopía'          => 0,
					'hipermetropía'   => 0,
					'presbicia'       => 0,
					'anisometropía'   => 0,
					'astenopía'       => 0
				);  

				foreach($result as $row) {
					$record = new Record($db, $row['record_id']);
					$conditions = $record->diagnose();
					unset($conditions['emetropía']);
		
					foreach($conditions as $key => $value) {
						if($value) {
							$stats[$key] += 1;
						}
					}
				}
//----

			}
		}
	}
?>

<h1><img src="resources/img/icon-group.png" height="48" width="48" alt=""> Enlistar pacientes</h1>

<form id="generate_enlistado" action="" method="post">
	<table class="noeffects" style="width: auto;">
		<tr>
			<td>
				<label for="date_from">Desde la fecha</label><br>
				<input type="date" name="date_from" id="date_from" tabindex="1" value="<?php if(isset($_POST['date_from'])) { echo $_POST['date_from']; } ?>">
			</td>
				<td>
				<label for="date_to">Hasta la fecha</label><br>
				<input type="date" name="date_to" id="date_to" tabindex="2" value="<?php if(isset($_POST['date_to'])) { echo $_POST['date_to']; } ?>">
			</td>
			<td>
				<label for="referencia">Referidos por</label><br>
				<input type="text" name="referencia" id="referencia" placeholder="Unidad" tabindex="3" value="<?php if(isset($_POST['referencia'])) { echo $_POST['referencia']; } ?>">
			</td>
			<td>
				<br>
				<input type="submit" name="btn_submit" id="btn_submit" value="Generar enlistado" tabindex="4">
			</td>
		</tr>
	</table>
</form>

<?php
	if(isset($num_results)) {
		if(!$num_results) {
			echo '<p><em>La búsqueda terminó sin resultados.</em></p>', PHP_EOL;
		} else {
			echo '<p><em>La búsqueda terminó con ' . ($num_results == 1 ? '1 resultado' : $num_results . ' resultados') . '</em></p>', PHP_EOL;
?>

<table class="tablesorter">
	<thead>
		<tr><th>Nombre</th><th>Apellido</th><th>Diagnóstico</th></tr>
	</thead>
	<tbody>
		<?php
			foreach($result as $row) {
				$record = new Record($db, $row['record_id']);
				echo '<tr onclick="document.location = \'?page=expedientes&patient_id=', $row['patient_id'], '\'" style="cursor: pointer;"><td>', $row['firstname'], '</td><td>', $row['lastname'], '</td><td>', $record->list_conditions(), '</td></tr>', PHP_EOL;
			}
		?>
	</tbody>
</table>

<br><br>
<?php echo '<a target="_blank" href="?page=print&amp;type=enlistados&amp;date_from=', $_POST['date_from'], '&amp;date_to=', $_POST['date_to'], '&amp;referencia=', (isset($_POST['referencia']) ? $_POST['referencia'] : ''), '"><button type="button" id="btn_print" class="floatright">Imprimir enlistados</button></a>', PHP_EOL; ?><br><br>


<h2>Estadísticas</h2>

<fieldset id="resumen_pacientes">
	<legend>Resumen de diagnosticos</legend>
	<table class="noeffects simpleborder">
		<tbody>
			<tr>
				<th style="width: 250px;">Total de personas revisadas</th>
				<td colspan="2"><?php echo number_format($num_results); ?></td>
			</tr>
			<tr>
				<th colspan="3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ametropías</th>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Astigmatismo</td>
				<td><?php echo number_format($stats['astigmatismo']); ?></td>
				<td><?php echo round(($stats['astigmatismo'] / $num_results) * 100, 2); ?>%</td>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Miopía</td>
				<td><?php echo number_format($stats['miopía']); ?></td>
				<td><?php echo round(($stats['miopía'] / $num_results) * 100, 2); ?>%</td>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hipermetropía</td>
				<td><?php echo number_format($stats['hipermetropía']); ?></td>
				<td><?php echo round(($stats['hipermetropía'] / $num_results) * 100, 2); ?>%</td>
			</tr>
			<tr>
				<th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Presbícia</th>
				<td><?php echo number_format($stats['presbicia']); ?></td>
				<td><?php echo round(($stats['presbicia'] / $num_results) * 100, 2); ?>%</td>
			</tr>
			<tr>
				<th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Anisometropía</th>
				<td><?php echo number_format($stats['anisometropía']); ?></td>
				<td><?php echo round(($stats['anisometropía'] / $num_results) * 100, 2); ?>%</td>
			</tr>
		</tbody>
	</table>
</fieldset>



<?php } } ?>
