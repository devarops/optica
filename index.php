<?php
	session_start();
	//error_reporting(E_ERROR | E_WARNING | E_PARSE);
	require_once('db_connect.php');
	require_once('classes/frame.php');
	require_once('classes/image.php');
	require_once('classes/record.php');
	require_once('classes/patient.php');
	require_once('classes/employee.php');
	require_once('classes/statistics.php');
	require_once('classes/investigation.php');
	require_once('classes/remission_note.php');

	/* Print case must go before everything else, since we'll be handling other headers */
	if(isset($_GET['page']) && $_GET['page'] == 'print') {
		require_once('resources/print.php');
		die();
	}

	/* General navigation */
	$pages = array(
		'expedientes',
		'enlistados',
		'armazones',
		'notas',
		'estadisticas',
		'investigaciones',
		'personal',
		'herramientas'
	);
	if(isset($_GET['page']) && in_array($_GET['page'], $pages)) {
		$page = $_GET['page'];
	} else {
		$page = 'expedientes';
	}

	/* POST handling */
	if(isset($_POST)) {
		// Patients and records
		// --------------------
		if(isset($_POST['patient'])) {
			$patient = new Patient($db, (isset($_POST['patient']['patient_id']) ? $_POST['patient']['patient_id'] : 0));

			if(!isset($_POST['patient']['is_flagged'])) {
				$patient->is_flagged = 0;
			}

			foreach($_POST['patient'] as $key => $value) {
				$patient->$key = $value;
			}

			if($patient->save()) {
				$_GET['patient_id'] = $patient->id; // Set in Patient::save() by PDO::lastInsertId()

				// Handle selected investigations -- this could be a bit prettier
				if(isset($_POST['investigations'])) { // Sort to ensure order is the same as those SELECTed
					sort($_POST['investigations']);
				}

				if($patient->investigations != $_POST['investigations']) {
					$db->query('DELETE FROM ct_patient_investigation WHERE patient_id=' . $patient->id);
					$stmt = $db->prepare('INSERT INTO ct_patient_investigation (patient_id, investigation_id) VALUES (:patient_id, :investigation_id)');
					$stmt->bindParam(':patient_id', $patient->id);
					foreach($_POST['investigations'] as $investigation_id) {
						$stmt->bindParam(':investigation_id', $investigation_id);
						$stmt->execute();
					}
				}

				// Records
				if(isset($_POST['new_record'])) {
					// Some Q&D cleaning before proceeding (lets us assign by looping below)
					unset($_POST['patient']);
					unset($_POST['investigations']);
					unset($_POST['btn_submit']);

					$record = new Record($db);

					// Split m_od and m_oi and insert them in their corresponding camps
					foreach(array('m_od', 'm_oi') as $side) {
						$values = $record->ecea_split($_POST[$side]);
						foreach(array('sphere', 'cylinder', 'axis', 'addition') as $key => $identifier) {
							$identifier = $side . '_' . $identifier;
							$record->$identifier = $values[$key];
						}
					}

					foreach($_POST as $key => $value) {
						$record->$key = $value;
					}
					$record->patient_id = $patient->id;
					$record->save();
				}

				$message = array('<p>Los datos fueron almacenados exitosamente!</p>', 'success');
			} else {
				$message = array('<p>Un error apareció durante el almacenamiento de los datos!</p>', 'error');
			}
		}

		// Investigations
		// --------------
		if(isset($_POST['btn_investigation'])) {
			$investigation              = new Investigation($db, (isset($_POST['investigation_id']) ? $_POST['investigation_id'] : 0));
			$investigation->title       = $_POST['title'];
			$investigation->description = $_POST['description'];

			if($investigation->save()) {
				$message = array('<p>Los datos fueron almacenados exitosamente!</p>', 'success');
			}
		}

		// Frames
		// ------
		if(isset($_POST['btn_frame'])) {
			$frame = new Frame($db, (isset($_POST['frame_id']) ? $_POST['frame_id'] : 0));
			unset($_POST['btn_frame']);
			foreach($_POST as $key => $value) {
				$frame->$key = ucfirst($value);
			}

			if($frame->save()) {
				$message = array('<p>Los datos fueron almacenados exitosamente!</p>', 'success');
			}
		}

		// Remission notes
		// ---------------
		if(isset($_POST['btn_nota'])) {
			$ok              = True; // Awful...
			$is_admin_update = False;

			if(isset($_POST['is_update'])) {
				$employee        = new Employee($db, $_POST['employee_id']);
				$is_admin_update = $employee->is_admin;
			}


			if(!isset($_POST['patient_id']) || !is_numeric($_POST['patient_id'])) {
				$message = array('<p><strong>Error:</strong> Hace falta eligír un paciente.</p>', 'error');
				$ok      = False;
			} else if(!isset($_POST['salesperson_id']) || !is_numeric($_POST['salesperson_id'])) {
				$message = array('<p><strong>Error:</strong> Hace falta eligír un vendedor.</p>', 'error');
				$ok      = False;
			} else if(isset($_POST['is_update']) && !$employee->checkPassword($_POST['employee_password'])) {
				$message = array('<p>Strong>Error:</strong> Usuario no autorizado.</p>', 'error');
				$ok      = False;
			}

			if($ok) {
				$rn = new RemissionNote($db, (isset($_POST['remission_note_id']) ? $_POST['remission_note_id'] : 0));
				unset($_POST['btn_nota']);
				foreach($_POST as $key => $value) {
					//echo nl2br(print_r([$key, $value], true));
					//if($key == 'item_name') { break; }
					$rn->$key = $value;
				}

				// Format the product arrays (first if just to avoid unnecessary processing during updates)
				if(isset($_POST['item_name'])) {// && !isset($_POST['is_update'])) {
					$products = array();
					for($i = 0; $i < sizeof($_POST['item_name']); $i++) {
						if(!array_key_exists($_POST['item_type'][$i], $products)) {
							$products[$_POST['item_type'][$i]] = array();
						}

						if($is_admin_update && $_POST['item_type'][$i] == 'frame') {
							$row = $db->query('SELECT remission_note_id FROM frame WHERE id = ' . $_POST['item_id'][$i])->fetch(PDO::FETCH_ASSOC);
							if($row['remission_note_id']) {
								// Already belongs to somebody (most likely us); skip
								continue;
							}
						}

						$products[$_POST['item_type'][$i]][] = array(
							'name' => $_POST['item_name'][$i],
							'id' => ($_POST['item_id'][$i] ? $_POST['item_id'][$i] : ''),
							'price' => $_POST['item_price'][$i]
						);
					}

					$rn->handle_products($products);
				}

				$new_id = $rn->save($is_admin_update);
				if($new_id) {
					$message = array('<p><strong>Éxito:</strong> Los datos fueron almacenados exitosamente!</p>', 'success');
					$_GET['id'] = $new_id; // Hack to properly load everything
				}
			}
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Óptica Horus</title>
		<link rel="stylesheet" type="text/css" href="style.css" media="screen">
		<link rel="stylesheet" type="text/css" href="resources/css/jquery-ui.css" media="screen">
		<link rel="stylesheet" type="text/css" href="resources/js/jqplot/jquery.jqplot.min.css" media="screen">
		<link rel="stylesheet" type="text/css" href="resources/js/jquery.fancybox.css" media="screen">
		<link rel="stylesheet" type="text/css" href="resources/js/chosen.min.css" media="screen">
		<script src="resources/js/jquery.min.js"></script>
		<script src="resources/js/jquery-ui.min.js"></script>
		<script src="resources/js/chosen.jquery.min.js"></script>
		<script src="resources/js/jquery.tablesorter.min.js"></script>
		<script src="resources/js/jquery.mousewheel.pack.js"></script>
		<script src="resources/js/jquery.fancybox.pack.js"></script>
		<script src="resources/js/jqplot/jquery.jqplot.min.js"></script>
		<script src="resources/js/jqplot/plugins/jqplot.barRenderer.min.js"></script>
		<script src="resources/js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
		<script src="resources/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
		<script src="resources/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
		<script src="resources/js/jqplot/plugins/jqplot.canvasOverlay.min.js"></script>
		<script src="resources/js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
		<script src="resources/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>

		<script>
			jQuery(document).ready(function() {
				jQuery('table.tablesorter').tablesorter();

				jQuery('.fancybox').fancybox({
					maxWidth:    800,
					maxHeight:   600,
					fitToView:   false,
					width:       '70%',
					height:      '70%',
					autoSize:    false,
					closeClick:  false,
					openEffect:  'none',
					closeEffect: 'none',
					mouseWheel:  true,
					helpers: {
						thumbs: {
							width: 50,
							height: 50
						}
					}
				});

				jQuery('.chosen-select').chosen();
			});
		</script>
	</head>
	<body>
		<div id="wrapper">
			<header>
				<nav>
					<ul id="mainmenu">
						<li><a href="?page=expedientes" accesskey="1">Expedientes</a></li>
						<li><a href="?page=enlistados">Enlistados</a></li>
						<li><a href="?page=armazones">Armazones</a></li>
						<li><a href="?page=notas">Notas</a></li>
						<li><a href="?page=estadisticas">Estadísticas</a></li>
						<li><a href="?page=investigaciones">Investigaciones</a></li>
						<!-- <li><a href="?page=personal">Personal</a></li> -->
						<li><a href="?page=herramientas">Herramientas</a></li>
					</ul>
				</nav>
				<?php
					if(isset($message)) {
						echo '<div class="notification ', $message[1], '">', $message[0], '</div>', PHP_EOL;
					}
				?>
			</header>

			<div id="cont">
				<div id="msgbox"></div>
				<?php
					require_once('pages/' . $page . '.php');
				?>
			</div>
		</div>
		<p class="small" style="text-align: center;"><strong>Óptica Horus</strong> Beatríz Mayoral<br> Actualizado: <?php echo exec('hg parent --template "{date(date, \'%d %b %Y\')}"'); ?> <br> Revisión: <?php echo strtoupper(exec('hg parent --template "{node|short}" | cut -c -4')); ?> </p>
	</body>
</html>
