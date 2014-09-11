<?php
	$investigations = Investigation::get_all_investigations($db);

	// Handle investigation removal -- UGLY, this should go with the rest. Flask is much nicer.
	if(isset($_GET['delete'])) {
		if(array_key_exists($_GET['delete'], $investigations)) {
			$result = $investigations[$_GET['delete']]->delete();
			echo '<div class="notification ', $result[1], '"><p>', $result[0], '</p></div>', PHP_EOL;
			if($result[1] == 'success') {
				unset($investigations[$_GET['delete']]);
			}
		} else {
			echo '<div class="notification warning"><p>Un error occurió mientras intentando borrar una investigación.</p></div>', PHP_EOL;
		}
	}
?>

<h1><img src="resources/img/icon-research.png" height="48" width="48" alt=""> Investigaciones</h1>

<?php
	if(empty($investigations)) {
		echo '<p>Por el momento no existe investigaciones.</p>', PHP_EOL;
	}
?>

<script>
	function edit_investigation(investigation_id) {
		title_val = jQuery('#investigation_' + investigation_id + ' > legend > a').html();
		desc_val = jQuery('#investigation_' + investigation_id + ' > .description').html();

		title_field = jQuery('<input>').attr({type: 'text', id: 'title', name: 'title', size: '40', value: title_val});
		jQuery('#investigation_' + investigation_id + ' > legend').html(title_field);

		desc_field = jQuery('<textarea>').attr({id: 'description', name: 'description', style: 'width: 95.5%; height: 150px;'});
		desc_field.html(desc_val.replace(/<br>/g, "\r"));
		jQuery('#investigation_' + investigation_id + ' > .description').html(desc_field);

		btn = jQuery('<input>').attr({type: 'submit', id: 'btn_investigation', name: 'btn_investigation', value: 'Actualizar investigación', class: 'floatright'});
		jQuery('#investigation_' + investigation_id + ' > .description').after(btn);
	}

	function confirm_delete() {
		if(!confirm("Estás seguro de que quieres borrar la investigación?")) {
			event.preventDefault();
		}
	}
</script>

<?php
	if(!empty($investigations)) {
		echo '<h4>Índice de investigaciones</h4>', PHP_EOL;
		echo '<ul>', PHP_EOL;
		foreach($investigations as $investigation) {
			echo '<li><a href="#investigation_', $investigation->id, '">', $investigation->title, '</a></li>', PHP_EOL;
		}
		echo '</ul>', PHP_EOL;
	}
?>

<button id="btn_new_investigation" onclick="jQuery('#new_investigation').fadeToggle(); jQuery('#btn_new_investigation').remove();">Crear nueva investigación</button>
<form id="new_investigation" action="" method="post" style="display: none;">
	<fieldset class="investigation">
		<legend><input type="text" name="title" id="title" placeholder="Título de la investigación"></legend>
		<textarea name="description" id="description" placeholder="Una descripción de la investigación." style="width: 95.5%; height: 150px;"></textarea> 
		<input type="submit" name="btn_investigation" id="btn_investigation" value="Agregar investigación nueva" class="floatright">
	</fieldset>
</form>

<?php
	foreach($investigations as $investigation) {
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="investigation_id" value="', $investigation->id, '">';
		echo '<fieldset class="investigation" id="investigation_', $investigation->id, '"><legend><a name="investigation_', $investigation->id, '">', $investigation->title, '</a></legend>';
		echo '<a href="?page=investigaciones&amp;delete=', $investigation->id, '" onclick="confirm_delete();" class="floatright" title="Borrar investigación"><img src="resources/img/icon-delete.png" height="16" width="16" alt="Borrar"></a>';
		echo '<a href="#" onclick="edit_investigation(', $investigation->id, ');" class="floatright" style="margin: 0 5px 0 0;" title="Modificar investigación"><img src="resources/img/icon-edit.png" height="16" width="16" alt="Modificar"></a>';
		echo '<p class="meta">La investigación fue creada ', $investigation->add_date, '</p>';
		echo '<div class="description">', nl2br($investigation->description), '</div>';
		$participants = $investigation->get_participants();
		echo '<h4 onclick="jQuery(\'#participants_', $investigation->id, '\').fadeToggle();" style="cursor: pointer;">Listado de participantes <img src="resources/img/icon-arrow-down.gif" alt="Click" height="16" width="16"></h4>';
		if(empty($participants)) {
			echo '<p>Por el momento la investigación no cuenta con participantes.<br>Se puede añadir por medio de los expedientes.</p>';
		} else {
			echo '<table class="participants tablesorter clickable_tr" id="participants_', $investigation->id, '" style="display: none;">';
			echo '<thead><tr><th>Nombre</th><th>Apellido</th><th>Género</th><th>Edad</th></tr></thead><tbody>';
			foreach($participants as $participant) {
				echo '<tr onclick="document.location = \'?page=expedientes&amp;patient_id=', $participant['id'], '\'"><td>', $participant['firstname'], '</td><td>', $participant['lastname'], '</td><td>', ($participant['gender'] == 0 ? 'Hombre' : 'Mujer'), '</td><td>', $participant['birthdate'], '</td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</fieldset></form>';
	}
?>
