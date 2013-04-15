<?php
	if(isset($_REQUEST['type'])) {

		// Resumenes will send an array of IDs
		// We should generate the PDFs then join them together, but let's limit the inputs...
		if($_REQUEST['type'] == 'resume' && isset($_REQUEST['id'])) {
			// Takes the ID of a patient's record
			$record  = new Record($db, $_REQUEST['id']);
			$patient = new Patient($db, $record->patient_id);
			$record->patient_firstname = $patient->firstname;
			$record->patient_lastname  = $patient->lastname;
			$record->resume = $record->resume();
			print_item($record, 'resumen', 'resources/latex/templates/resumen.tex');
		} else if($_REQUEST['type'] == 'enlistados') {

			$ref = '%' . (isset($_GET['referencia']) ? $_GET['referencia'] : '') . '%';
			$stmt = $db->prepare('SELECT record_id, patient_id, firstname, lastname FROM (SELECT r.id AS record_id, p.id AS patient_id, p.firstname AS firstname, p.lastname AS lastname FROM record AS r, patient AS p WHERE r.patient_id = p.id AND r.reference LIKE :ref AND r.add_date BETWEEN :date_from AND :date_to ORDER BY r.add_date DESC) AS d GROUP BY patient_id');
			$stmt->bindParam(':date_from', $_GET['date_from']);
			$stmt->bindParam(':date_to', $_GET['date_to']);
			$stmt->bindParam(':ref', $ref);
			if($stmt->execute()) {
				$result = $stmt->fetchAll();
				$num_results = sizeof($result);
				$dataobj = new stdClass(); // Generic object as container
				$dataobj->num_patients = $num_results;
				$dataobj->header = 'Enlistado de personas revisadas por Optica Horus\linebreak ' . (isset($_GET['referencia']) ? 'para ' . $_GET['referencia'] : '') . ' en el periodo de ' . $_GET['date_from'] . ' a ' . $_GET['date_to']; 
				// Slightly ugly hack for filename: firstname_lastname => from_date_to_date
				$dataobj->patient_firstname = $_GET['date_from'];
				$dataobj->patient_lastname  = $_GET['date_to'];
				$dataobj->diagnostics_list  = '';
			} else {
				die('An error occurred while fetching the results from the database.');
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
				$record     = new Record($db, $row['record_id']);
				$patient    = new Patient($db, $record->patient_id);
				$conditions = $record->diagnose();
				unset($conditions['emetropía']);

				$dataobj->diagnostics_list .= $patient->firstname . ' & ' . $patient->lastname . ' & ' . $record->list_conditions() . '\\\\ ' . PHP_EOL;

				foreach($conditions as $key => $value) {
					if($value) {
						$stats[$key] += 1;
					}
				}
			}

			// Statistics summary
			$dataobj->astigmatismo_n = number_format($stats['astigmatismo']);
			$dataobj->astigmatismo_p = round(($stats['astigmatismo'] / $num_results) * 100, 2);
			$dataobj->miopia_n = number_format($stats['miopía']);
			$dataobj->miopia_p = round(($stats['miopía'] / $num_results) * 100, 2);
			$dataobj->hipermetropia_n = number_format($stats['hipermetropía']);
			$dataobj->hipermetropia_p = round(($stats['hipermetropía'] / $num_results) * 100, 2);
			$dataobj->presbicia_n = number_format($stats['presbicia']);
			$dataobj->presbicia_p = round(($stats['presbicia'] / $num_results) * 100, 2);
			$dataobj->anisometropia_n = number_format($stats['anisometropía']);
			$dataobj->anisometropia_p = round(($stats['anisometropía'] / $num_results) * 100, 2);
			$dataobj->otros_n = 'indef.';
			$dataobj->otros_p = 'indef.';
			#$dataobj->otros_n = number_format($stats['otros']);
			#$dataobj->otros_p = round(($stats['otros'] / $num_results) * 100, 2);

			print_item($dataobj, 'enlistados', 'resources/latex/templates/enlistados.tex');

		} else if(isset($_REQUEST['id'])) { // Remission notes
			$rn               = new RemissionNote($db, $_GET['id']);
			$patient          = new Patient($db, $rn->patient_id);
			$rn->add_date     = substr($rn->add_date, 0, -9);
			$rn->patient_firstname = $patient->firstname;
			$rn->patient_lastname  = $patient->lastname;
			$rn->patient_telephone = (isset($patient->telephone) ? $patient->telephone : '---');
			$rn->latex_product_table = latex_product_table($rn->get_product_list(), $rn->down_payment); // Down payment is really a "donation"..

			if($_REQUEST['type'] == 'patient_remission') {
				print_item($rn, 'nota_remision_paciente', 'resources/latex/templates/patient_remission_note.tex');
			} else if($_REQUEST['type'] == 'optician_remission') {
				$record = $patient->get_latest_record();
				$rn->m_od = (strlen($record->m_od) > 3 ? str_replace(array('(', '%', '['), array(' ( ', ' \% ', ' [ '), $record->m_od) : '\emph{No medido}');
				$rn->m_oi = (strlen($record->m_oi) > 3 ? str_replace(array('(', '%', '['), array(' ( ', ' \% ', ' [ '), $record->m_oi) : '\emph{No medido}');
				print_item($rn, 'nota_remision_optica', 'resources/latex/templates/optician_remission_note.tex');
			}
		}
	}

	function print_item($data, $type, $template_path) {
		// Data: Object (RemissionNote, Record, ...)
		// Type: String (Decides the correct path for the output file)
		// Template path: String

		$fh = fopen($template_path, 'r');
		$template = fread($fh, filesize($template_path));
		fclose($fh);

		$latex = preg_replace('/#(\w+)#/e', '$data->\\1', $template);
		
		$dir = '/var/www/resources/latex/' . $type . '/' . date('Y') . '/' . date('m') . '/';
		if(!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		$filename = $type . '_' . str_replace(' ', '_', $data->patient_firstname) . '_' . str_replace(' ', '_', $data->patient_lastname);
		if($fh = fopen($dir . $filename . '.tex', 'w')) {
			if(fwrite($fh, $latex)) {
				#passthru('cd ' . $dir . '; pdflatex ' . $filename . '.tex');
				#die();
				exec('cd ' . $dir . '; pdflatex ' . $filename . '.tex');

				header('Content-type: application/pdf');
				header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: ' . filesize($dir . $filename . '.pdf'));
				header('Accept-Ranges: bytes');
				readfile($dir . $filename . '.pdf');
			} else {
				echo 'Could not write to ', $filename;
			}
			fclose($fh);
			chmod($dir, 0666, 0777);
			chmod($dir . $filename . '.tex', 0766);
		} else {
			echo 'Miserable failure while opening ', $filename, ' for writing.';
		}
	}

	function latex_product_table($data, $abono) {
		$output = '\renewcommand{\arraystretch}{1.4}' . PHP_EOL . '\begin{center}' . PHP_EOL
			. '\begin{tabular}{p{9cm} l}' . PHP_EOL . '\textbf{Descripción} & \textbf{Precio} \\\\' . PHP_EOL . '\hline' . PHP_EOL . '\hline' . PHP_EOL;
		$total = 0;
		foreach($data as $data) {
			$output .= str_replace(array('#'), array('\#'), $data['name']) . ' & \$ ' . number_format($data['price'], 2) . ' \\\\' . PHP_EOL;
			$total += (int)$data['price'];
		}
		$output .= '\hline' . PHP_EOL;
		$output .= '\hfill \textbf{Subtotal} & \$ ' . number_format($total, 2) . ' \\\\' . PHP_EOL;
		if($abono != 0) {
			$total -= $abono;
			$output .= '\hfill \textbf{Abono} & \$ ' . number_format($abono, 2) . ' \\\\' . PHP_EOL;
		}
		$output .= '\hfill \textbf{Total} & \$ ' . number_format($total, 2) . ' \\\\' . PHP_EOL;
		$output .= '\end{tabular}' . PHP_EOL . '\end{center}' . PHP_EOL;
		return $output;
	}
?>
