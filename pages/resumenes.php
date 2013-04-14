<?php
	if(isset($_POST['btn_generate'])) {
		if(isset($_POST['date_from']) && isset($_POST['date_to'])) {
			$date_regex = '/^(\d{4})-(\d{2})-(\d{2})$/';
			if(!preg_match($date_regex, $_POST['date_from']) || !preg_match($date_regex, $_POST['date_to'])) {
				echo '<div class="notification warning">Las fechas no están correctamente formateadas; deben de ser del formato YYYY-MM-DD.</div>', PHP_EOL;
			} else {
				$stmt = $db->prepare('SELECT record_id, patient_id, firstname, lastname FROM (SELECT r.id AS record_id, p.id AS patient_id, p.firstname AS firstname, p.lastname AS lastname FROM record AS r, patient AS p WHERE r.patient_id = p.id AND r.add_date BETWEEN :date_from AND :date_to ORDER BY r.add_date DESC) AS d GROUP BY patient_id');
				$stmt->bindParam(':date_from', $_POST['date_from']);
				$stmt->bindParam(':date_to', $_POST['date_to']);
				if($stmt->execute()) {
					$result = $stmt->fetchAll();
					$num_results = sizeof($result);
				}
			}
		}
	}

	if(isset($_POST['btn_print'])) {
		$ids = (isset($_POST['print']) ? $_POST['print'] : array());
		if(sizeof($ids) == 0) {
			echo '<div class="notification warning">Ningún resumen seleccionado para impresión.</div>', PHP_EOL;
		} else {
			echo 'The following items have been selected for printing:';
			print_r($ids);
		}
	}
?>

<h1>Resumenes</h1>

<form id="generate" action="" method="post">
	<table class="noeffects" style="width: auto;">
		<tr>
			<td>
				<label for="date_from">Desde la fecha</label><br>
				<input type="date" name="date_from" id="date_from" tabindex="1" value="<?php if(isset($_POST['date_from'])) { echo $_POST['date_from']; } ?>">
			</td>
			<td>
				<label for="date_from">Hasta la fecha</label><br>
				<input type="date" name="date_to" id="date_to" tabindex="2" value="<?php if(isset($_POST['date_to'])) { echo $_POST['date_to']; } else { echo date('Y-m-d'); } ?>">
			</td>
			<td>
				<br>
				<input type="submit" id="btn_generate" name="btn_generate" value="Generar resumenes" tabindex="3">
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

<fieldset>
	<legend>Resumenes de <?php echo $_POST['date_from'], ' a ', $_POST['date_to']; ?></legend>
	<form id="printlist" action="?page=print" method="post" target="_blank">
		<input type="hidden" name="type" value="resume">
		<table>
			<thead>
				<tr><td><img src="resources/img/icon-print.png" height="16" width="16" alt="Print"></td><td></td></tr>
			</thead>
			<tbody>
				<?php
					foreach($result as $row) {
						$record = new Record($db, $row['record_id']);
						echo '<tr><td><input type="checkbox" name="id[]" id="print_', $row['record_id'], '" value="', $row['record_id'], '"', ((!$record->print_resume && !$record->print_date) ? ' checked="checked"' : ''), '></td>',
							'<td><label for="print_', $row['record_id'], '">',
							'<strong>', ucfirst($row['firstname']), ' ', strtoupper($row['lastname']), '</strong>',
							(isset($record->print_date) ? '<span class="floatright">Última impresión: ' . $record->print_date . '</span>' : ''), '<br>',
							$record->resume(false), PHP_EOL, // true/false for patient version
							'</label></td></tr>', PHP_EOL;
					}
				?>
			</tbody>
		</table>
		<br>
		<input type="submit" name="btn_print" id="btn_print" value="Imprimir resumenes seleccionados" class="floatright">
	</form>
</fieldset>

<?php } } ?>
