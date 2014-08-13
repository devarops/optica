<?php
	if(isset($_GET['patient_id'])) {
		$patient = new Patient($db, $_GET['patient_id']);

		if(isset($_GET['record_id'])) {
			$record = $patient->get_record($_GET['record_id']);
		} else {
			$record = $patient->get_latest_record();
		}
	}

	// Get distinct values for expediente->disco
	$result = $db->query('SELECT DISTINCT disco FROM record ORDER BY disco');
	$discos = '';
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		if(!empty($row['disco'])) {
			$discos .= "'" . $row['disco'] . "', ";
		}
	}
?>

<script>
	function patient_search() {
		input = jQuery('#search_kwds').val();
		if(input.length % 3 == 0 && input.length != 0) {
			jQuery.post('resources/ajax/patient_search.php', { search_string: input }, function(data) {
				jQuery('#patient_search_result').html(data);
				jQuery('.tablesorter').tablesorter(); // Have to reinitialize the plugin after altering search results
			});
		} else if(input.length == 0) {
			jQuery('#patient_search_result').html('');
		}
	}

	function format_odoi(identifier) {
		var str = jQuery(identifier).val();
		blankspace_index = str.indexOf(' ');
		if(blankspace_index !== -1) {
			if(str.indexOf('%') !== -1) {
				str = str.replace(' ', '[');
			} else if(str.indexOf('(') !== -1) {
				str = str.replace(' ', '%');
			} else {
				str = str.replace(' ', '(');
			}
			jQuery(identifier).val(str);
		}
	}

	function format_k(identifier) {
		var str = jQuery(identifier).val();
		blankspace_index = str.indexOf(' ');
		if(blankspace_index !== -1) {
			if(str.indexOf('/') !== -1) {
				str = str.replace(' ', 'x');
			} else {
				str = str.replace(' ', '/');
			}
			jQuery(identifier).val(str);
		}
	}

	function format_twonum(identifier) {
		var str = jQuery(identifier).val();
		blankspace_index = str.indexOf(' ');
		if(blankspace_index !== -1) {
			if(str.indexOf('/') == -1) {
				str = str.replace(' ', '/');
			}
			jQuery(identifier).val(str);
		}

	}

	function unlock_new_record() {
		jQuery('#record').find('input').removeAttr('disabled');
		jQuery('#record').find('textarea').removeAttr('disabled');
		jQuery('#record').find('select').removeAttr('disabled');
		jQuery('#btn_unlock').remove();
		jQuery('#btn_submit').show();
	}

	// Radiobutton toggle
	jQuery(function() {
		jQuery('.ocular').click(function(event) {
			jQuery('#radio_' + event.target.id).toggle(); // > '.occular_options').toggle();				
		});
	});

	// Disco autocomplete
	jQuery(function() {
		var available_discos = [<?php echo substr($discos, 0, -2); ?>];
		jQuery('#disco').autocomplete({ source: available_discos });
	});
</script>

<h1>Expedientes</h1>

<label for="search_kwds">Realizar búsqueda</label><br>
<input type="search" name="search_kwds" id="search_kwds" oninput="patient_search();" tabindex="1" results="10" placeholder="Nombre(s) y/o apellido(s)">
<div id="patient_search_result" class="search result"></div>

<fieldset>
	<legend><?php echo (isset($patient) ? $patient->get_full_name(true) : 'Expediente nuevo') ?></legend>
	<form name="expediente" action="?page=expedientes" method="post">
		<table class="noeffects">
			<tr>
				<th colspan="3">Datos generales</th>
				<td style="text-align: right;"><input type="checkbox" name="patient[is_flagged]" id="is_flagged" title="Bandera" value="1" <?php if(isset($patient->is_flagged) && $patient->is_flagged) { echo 'checked'; } ?>></td>
			</tr>
			<tr>
				<td>
					<label for="firstname">Nombre *</label><br>
					<input type="text" name="patient[firstname]" id="firstname" placeholder="Nombre" required="required" tabindex="2" value="<?php if(isset($patient->firstname)) { echo $patient->firstname; } ?>">
				</td>
				<td>
					<label for="lastname">Apellido *</label><br>
					<input type="text" name="patient[lastname]" id="lastname" placeholder="Apellido" required="required" tabindex="3" value="<?php if(isset($patient->lastname)) { echo $patient->lastname; } ?>">
				</td>
				<td>
					<label for="gender">Género</label><br>
					<select name="patient[gender]" id="gender" tabindex="4">
						<option <?php if(isset($patient->gender) && $patient->gender == '0') { echo 'selected'; } ?> value="0">Hombre</option>
						<option <?php if(isset($patient->gender) && $patient->gender == '1') { echo 'selected'; } ?> value="1">Mujer</option>
					</select>
				</td>
				<td>
					<label for="birthdate">Edad</label><br>
					<input type="number" name="patient[birthdate]" id="birthdate" size="5" tabindex="5" value="<?php if(isset($patient->birthdate)) { echo $patient->get_age(); } ?>">
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<label for="address">Dirección</label><br>
					<input type="text" name="patient[address]" id="address" placeholder="Dirección" style="width: 88%;" tabindex="6" value="<?php if(isset($patient->address)) { echo $patient->address; } ?>">
				</td>
				<td>
					<label for="phone">Teléfono</label><br>
					<input type="tel" name="patient[phone]" id="phone" placeholder="Teléfono" tabindex="7" value="<?php if(isset($patient->phone)) { echo $patient->phone; } ?>">
				</td>
<?php if(isset($patient->add_date)) { ?>
				<td style="vertical-align: middle;">
					<span><em>Paciente desde: <?php echo substr($patient->add_date, 0, 10); ?></em>.</span>
				</td>
<?php } ?>
			</tr>
			<tr>
				<td colspan="2">
					<label for="comments">Comentarios</label><br>
					<input type="text" name="patient[comments]" id="comments" style="width: 88%;" tabindex="8" placeholder="Notas sobre el paciente" value="<?php if(isset($patient->comments)) { echo $patient->comments; } ?>">
				</td>
				<td colspan="2">
					<label for="investigations">Investigaciones</label>
					<?php
						$investigations = Investigation::get_all_investigations($db);
						if(empty($investigations)) {
							echo '<p class="small">Por el momento no hay investigaciones disponibles.</p>', PHP_EOL;
						} else {
							echo '<select multiple name="investigations[]" id="investigations" style="width: 92%; height: 100px;" tabindex="9">', PHP_EOL;
							foreach($investigations as $investigation) {
								echo '<option', (isset($patient) && $patient->is_participant($investigation->id) ? ' selected' : ''), ' value="', $investigation->id, '">', $investigation->title, '</option>', PHP_EOL;
							}
							echo '</select>', PHP_EOL;
							echo '<p class="small"><em>Se puede elegír múltiples opciones con Ctrl.</em></p>', PHP_EOL;
						}
					?>
				</td>
			</tr>
			<tr>
				<td colspan="4">
			<?php if(isset($patient)) { ?>
				<input type="hidden" name="patient[patient_id]" id="patient_id" value="<?php echo $patient->id; ?>">
				<button style="margin-right: 3px;" type="button" id="btn_unlock" class="floatright" onclick="unlock_new_record();">Nueva examinación</button>
				<a href="?page=notas&amp;patient_id=<?php echo $patient->id; ?>"><button type="button" id="btn_nota" class="green floatright">Agregar nota de remisión</button></a>
			<?php } ?>
				</td>
			</tr>
		</table>
			<!-- PATIENT ends here -->
		<table id="record" class="noeffects">
			<input type="hidden" name="new_record" value="1">
			<tr>
				<td colspan="2">
					<label for="reference">Referencia</label><br>
					<input type="text" name="reference" id="reference" placeholder="Referencia" style="width: 88%;" tabindex="10" value="<?php if(isset($record->reference)) { echo $record->reference; } ?>">
				</td>
				<td colspan="2">
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<label for="motive">Motivo</label><br>
					<textarea name="motive" id="motive" style="width: 88%; height: 50px;" placeholder="Motivo" tabindex="11"><?php if(isset($record->motive)) { echo $record->motive; } ?></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<label for="occupation">Ocupación</label><br>
					<input type="text" name="occupation" id="occupation" placeholder="Ocupación" tabindex="12" value="<?php if(isset($record->occupation)) { echo $record->occupation; } ?>">
				</td>
				<td>
					<label for="sport">Deporte</label><br>
					<input type="text" name="sport" id="sport" placeholder="Deporte" tabindex="13" value="<?php if(isset($record->sport)) { echo $record->sport; } ?>">
				</td>
				<td colspan="2">
					<label for="pc_usage">Uso de PC <em>(hrs/día)</em></label><br>
					<input type="number" name="pc_usage" id="pc_usage" placeholder="Horas" style="width: 80px;" min="0" max="24" tabindex="13" value="<?php if(isset($record->pc_usage)) { echo $record->pc_usage; } ?>">
				</td>
			</tr>
			<tr>
				<td>
					<label for="health">Estado de salud</label><br>
					<input type="text" name="health" id="health" placeholder="Estado de salud" tabindex="14" value="<?php if(isset($record->health)) { echo $record->health; } ?>">
				</td>
				<td colspan="3">
					<br>
					<input type="checkbox" name="has_diabetes" id="has_diabetes" value="1" tabindex="15" <?php if(isset($record->has_diabetes) && $record->has_diabetes) { echo 'checked'; } ?>><label for="has_diabetes">Diabetes</label><br>
					<input type="checkbox" name="has_hypertension" id="has_hypertension" value="1" tabindex="16" <?php if(isset($record->has_hypertension) && $record->has_hypertension) { echo 'checked'; } ?>><label for="has_hypertension">Hipertensión arteral</label><br>
					<input type="checkbox" name="has_glaucoma_history" id="has_glaucoma_history" value="1" tabindex="17" <?php if(isset($record->has_glaucoma_history) && $record->has_glaucoma_history) { echo 'checked'; } ?>><label for="has_glaucoma_history">Antecedentes familiares de glaucoma</label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="addictions">Adicciones</label><br>
					<input type="text" name="addictions" id="addictions" placeholder="Adicciones" tabindex="18" value="<?php if(isset($record->addictions)) { echo $record->addictions; } ?>">
				</td>
				<td>
					<label for="w_od">W:OD</label><br>
					<input type="text" name="w_od" id="w_od" placeholder="       (       %       [       " tabindex="19" value="<?php if(isset($record->w_od)) { echo $record->w_od; } ?>" oninput="format_odoi('#w_od');">
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<label for="w_oi">W:OI</label><br>
					<input type="text" name="w_oi" id="w_oi" placeholder="       (       %       [       " tabindex="20" value="<?php if(isset($record->w_oi)) { echo $record->w_oi; } ?>" oninput="format_odoi('#w_oi');">
				</td>
			</tr>

			<tr>
				<th colspan="4">Examen optométrico</th>
			</tr>
			<tr>
				<td>
					<label for="av">AV</label><br>
					<select name="av" id="av" tabindex="21">
						<option <?php if(!isset($record->av) || (isset($record->av) && !$record->av)) { echo 'selected'; } ?> value="0">Sin corrección</option>
						<option <?php if(isset($record->av) && $record->av) { echo 'selected'; } ?> value="1">Con corrección</option>
					</select>
				</td>
				<td><label for="od">AV OD</label><br><span style="font-size: 1.5em;">20/</span><input type="text" name="od" id="od" size="4" placeholder="" value="<?php if(isset($record->od)) { echo $record->od; } ?>" tabindex="23"></td>
				<td><label for="estenop_od">Estenop OD</label><br><span style="font-size: 1.5em;">20/</span><input type="text" name="estenop_od" id="estenop_od" size="4" placeholder="" value="<?php if(isset($record->estenop_od)) { echo $record->estenop_od; } ?>" tabindex="25"></td>
			</tr>
			<tr>
				<td>
					<label for="cover_test">Cover test</label><br><input type="text" name="cover_test" id="cover_test" placeholder="Cover test" value="<?php if(isset($record->cover_test)) { echo $record->cover_test; } ?>" tabindex="22">
					<br><br><input type="checkbox" name="pos_mirada" id="pos_mirada" tabindex="26" value="1" <?php if(isset($record->pos_mirada) && $record->pos_mirada) { echo 'checked'; } ?>><label for="pos_mirada">Posiciones mirada</label>
				</td>
				<td><label for="oi">AV OI</label><br><span style="font-size: 1.5em;">20/</span><input type="text" name="oi" id="oi" size="4" placeholder="" value="<?php if(isset($record->oi)) { echo $record->oi; } ?>" tabindex="24"></td>
				<td><label for="estenop_oi">Estenop OI</label><br><span style="font-size: 1.5em;">20/</span><input type="text" name="estenop_oi" id="estenop_oi" size="4" placeholder="" value="<?php if(isset($record->estenop_oi)) { echo $record->estenop_oi; } ?>" tabindex="25"></td>
				</tr>
			<tr>
				<td>
					<label for="m_od">M:OD</label><br>
					<input type="text" name="m_od" id="m_od" placeholder="       (       %       [       " value="<?php if(isset($record->m_od)) { echo $record->m_od; } ?>" oninput="format_odoi('#m_od');" tabindex="27">
				</td>
				<td>
					<label for="j">J</label><br>
					<input type="text" name="j" id="j" size="1" placeholder="" value="<?php if(isset($record->j)) { echo $record->j; } ?>" tabindex="29">
				</td>
				<td rowspan="2">
					<label for="flashes">Flashes</label><br>
					<table>
						<tr class="small"><td colspan="2" style="text-align: center;">OD</td><td colspan="2" style="text-align: center;">OI</td><td></td></tr>
						<tr>
							<td colspan="2"><input type="text" name="flash_od_vert" id="flash_od_vert" size="3" value="<?php if(isset($record->flash_od_vert)) { echo $record->flash_od_vert; } ?>" tabindex="31"></td>
							<td colspan="2"><input type="text" name="flash_oi_vert" id="flash_oi_vert" size="3" value="<?php if(isset($record->flash_oi_vert)) { echo $record->flash_oi_vert; } ?>" tabindex="33"></td>
							<td class="small" style="vertical-align: middle;">Vert</td>
						</tr>
						<tr>
							<td style="padding-left: 10px; vertical-align: middle; text-align: center;"><img src="resources/img/flashes-separator.png" height="20" width="20"></td>
							<td><input type="text" name="flash_od_horiz" id="flash_od_horiz" size="3" value="<?php if(isset($record->flash_od_horiz)) { echo $record->flash_od_horiz; } ?>" tabindex="32"></td>
							<td style="padding-left: 10px; vertical-align: middle; text-align: center;"><img src="resources/img/flashes-separator.png" height="20" width="20"></td>
							<td><input type="text" name="flash_oi_horiz" id="flash_oi_horiz" size="3" value="<?php if(isset($record->flash_oi_horiz)) { echo $record->flash_oi_horiz; } ?>" tabindex="34"></td>
							<td class="small" style="vertical-align: middle;">Horiz</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<label for="m_oi">M:OI</label><br>
					<input type="text" name="m_oi" id="m_oi" placeholder="       (       %       [       " value="<?php if(isset($record->m_oi)) { echo $record->m_oi; } ?>" oninput="format_odoi('#m_oi');" tabindex="28">
				</td>
				<td>
					<label for="j_prim">J'</label><br>
					<input type="text" name="j_prim" id="j_prim" size="1" placeholder="" value="<?php if(isset($record->j_prim)) { echo $record->j_prim; } ?>" tabindex="30">
				</td>
			</tr>
			<tr>
				<td>
					<label for="cv_od">CV:OD</label><br>
					<span style="font-size: 1.5em;">20/</span><input type="text" name="cv_od" id="cv_od" size="4" placeholder="" value="<?php if(isset($record->cv_od)) { echo $record->cv_od; } ?>" tabindex="35">
				</td>
				<td>
					<label for="k_od">K:OD</label><br>
					<input type="text" name="k_od" id="k_od" size="15" placeholder="        /        x        " value="<?php if(isset($record->k_od)) { echo $record->k_od; } ?>" oninput="format_k('#k_od');" tabindex="37">
					<span><?php if(isset($record->k_od)) { echo $record->k_eval('k_od'); } ?></span>
				</td>
			</tr>
			<tr>
				<td>
					<label for="m_oi">CV:OI</label><br>
					<span style="font-size: 1.5em;">20/</span><input type="text" name="cv_oi" id="cv_oi" size="4" placeholder="" value="<?php if(isset($record->cv_oi)) { echo $record->cv_oi; } ?>" tabindex="36">
				</td>
				<td>
					<label for="k_od">K:OI</label><br>
					<input type="text" name="k_oi" id="k_oi" size="15" placeholder="        /        x        " value="<?php if(isset($record->k_oi)) { echo $record->k_oi; } ?>" oninput="format_k('#k_oi');" tabindex="38">
					<span><?php if(isset($record->k_oi)) { echo $record->k_eval('k_oi'); } ?></span>
				</td>
				<td>
					<label for="lectura">Lectura (Palabras por minuto)</label><br>
					<input type="text" name="lectura" id="lectura" placeholder="Lectura" value="<?php if(isset($record->lectura)) { echo $record->lectura; } ?>" tabindex="40">
				</td>
				<td>
					<?php
						// Should probably be called via AJAX, as to allow for usage on new patients. Testing algorithm here though.
						// Ref val PPM
						// * wpm, age interval +/- 1 year, avg. and std. dev. color indication for good/bad, show sample size in title text.
						// * on entering WPM, add dropdown with "grado escolar"

						if(isset($patient->birthdate) && isset($record)) {
							$statistics = new Statistics($db);
							$data       = $statistics->getWpmData($record->add_date - $patient->birthdate);

							printf('Avg: %.2f<br>σ: %.2f<br>pop size: %d', $data['average'], $data['std_dev'], $data['sample_size']);
						}
					?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<label for="observaciones">Observaciones</label><br>
					<textarea name="observaciones" id="observaciones" style="width: 88%; height: 78px;" tabindex="41" placeholder="Notas sobre la visita"><?php if(isset($record->observaciones)) { echo $record->observaciones; } ?></textarea>
				</td>
				<td colspan="2">
					<label for="recomendaciones">Recomendaciones</label><br>
					<textarea name="recomendaciones" id="recomendaciones" style="width: 88%; height: 78px;" tabindex="42" placeholder="Se recomienda..."><?php if(isset($record->recomendaciones)) { echo $record->recomendaciones; } ?></textarea>
				</td>
			</tr>
			<tr>
				<th colspan="3">Lentes de contacto</th>
			</tr>
			<tr>
				<td>
					<label for="conversion_dv">Conversion DV</label><br>
					<input type="text" name="conversion_dv" id="conversion_dv" placeholder="          /          " value="<?php if(isset($record->conversion_dv)) { echo $record->conversion_dv; } ?>" oninput="format_twonum('#conversion_dv');" tabindex="43">
				</td>
				<td>
					<label for="tipo_lc">Tipo LC</label><br>
					<input type="text" name="tipo_lc" id="tipo_lc" placeholder="Tipo LC" value="<?php if(isset($record->tipo_lc)) { echo $record->tipo_lc; } ?>" tabindex="44">
				</td>
			</tr>
			<tr>
				<th colspan="3">Acomodación</th>
			</tr>
			<tr>
				<td>
					<label for="amplitud_acomodacion">Amplitud de acomodación</label><br>
					<input type="text" name="amplitud_acomodacion" id="amplitud_acomodacion" placeholder="Amplitud de acomodación" value="<?php if(isset($record->amplitud_acomodacion)) { echo $record->amplitud_acomodacion; } ?>" tabindex="45"><br>
					<span class="small">Referencia: monoc / (100/cm) / 15-.25 (edad)</span>
				</td>
				<td>
					<label for="acomodacion_relativa">Acomodación relativa</label><br>
					<input type="text" name="acomodacion_relativa" id="acomodacion_relativa" placeholder="          /          " value="<?php if(isset($record->acomodacion_relativa)) { echo $record->acomodacion_relativa; } ?>" oninput="format_twonum('#acomodacion_relativa');" tabindex="46"><br>
					<span class="small">Referencia: (+250 / -350)</span>
				</td>
				<td>
					<label for="retardo_acomodativo">Retardo acomodativo</label><br>
					<input type="text" name="retardo_acomodativo" id="retardo_acomodativo" placeholder="Retardo acomodativo" value="<?php if(isset($record->retardo_acomodativo)) { echo $record->retardo_acomodativo; } ?>" tabindex="47"><br>
					<span class="small">Referencia: cerca +050 &gt; lejos</span>
				</td>
				<td>
					<label for="ppc">PPC</label><br>
					<input type="number" name="ppc" id="ppc" placeholder="PPC" value="<?php if(isset($record->ppc)) { echo $record->ppc; } ?>" tabindex="48"><br>
					<span class="small">Referencia: (6 &ndash; 10) cm</span>
				</td>
			</tr>
			<tr>
				<th colspan="3">Salud ocular</th>
			</tr>
			<tr>
				<td>
					<label for="tonometria_od">Tonometría OD</label><br>
					<input type="number" name="tonometria_od" id="tonometria_od" placeholder="Tonometría OD" tabindex="49" value="<?php if(isset($record->tonometria_od)) { echo $record->tonometria_od; } ?>" step="any">
				</td>
				<td rowspan="2">
					<label for="pccc">Párpados, córnea, conjuntiva, y cristalino</label><br>
					<textarea name="pccc" id="pccc" placeholder="Párpados, córnea, conjuntiva, y cristalino" tabindex="51" style="width: 220px; height: 88px;"><?php if(isset($record->pccc)) { echo $record->pccc; } ?></textarea>
				</td>
				<td rowspan="4">
					<label for="sensib_contraste">Sensibilidad de contraste</label><br>
					<input type="text" name="sensib_contraste" id="sensib_contraste" placeholder="           /" value="<?php if(isset($record->sensib_contraste)) { echo $record->sensib_contraste; } ?>" size="7" tabindex="51"><br>
					<input type="checkbox" class="ocular" name="has_vision_cromatica" id="has_vision_cromatica" value="1" tabindex="55" <?php if(isset($record->has_vision_cromatica) && $record->has_vision_cromatica) { echo 'checked'; } ?>><label for="has_vision_cromatica">Visión cromática</label><br>
					<div class="occular_options" id="radio_has_vision_cromatica" <?php if(isset($record->has_vision_cromatica) && $record->has_vision_cromatica) { echo 'style="display: block;"'; } ?>>
						<input type="radio" name="vision_cromatica" id="vision_cromatica_yes" value="1" tabindex="56" <?php if(isset($record->has_vision_cromatica) && $record->has_vision_cromatica == 1) { echo 'checked="checked"'; } ?>><label for="vision_cromatica_yes">Si</label>
						<input type="radio" name="vision_cromatica" id="vision_cromatica_no" value="2" tabindex="57" <?php if(isset($record->has_vision_cromatica) && $record->has_vision_cromatica == 2) { echo 'checked="checked"'; } ?>><label for="vision_cromatica_no">No</label>
					</div>
					<input type="checkbox" class="ocular" name="has_confrontacion" id="has_confrontacion" value="1" tabindex="58" <?php if(isset($record->has_confrontacion) && $record->has_confrontacion) { echo 'checked'; } ?>><label for="has_confrontacion">Confrontación</label><br>
					<div class="occular_options" id="radio_has_confrontacion" <?php if(isset($record->has_confrontacion) && $record->has_confrontacion) { echo 'style="display: block;"'; } ?>>
						<input type="radio" name="confrontacion" id="confrontacion_yes" value="1" tabindex="59" <?php if(isset($record->has_confrontacion) && $record->has_confrontacion == 1) { echo 'checked="checked"'; } ?>><label for="confrontacion_yes">Si</label>
						<input type="radio" name="confrontacion" id="confrontacion_no" value="2" tabindex="60" <?php if(isset($record->has_confrontacion) && $record->has_confrontacion == 2) { echo 'checked="checked"'; } ?>><label for="confrontacion_yes">No</label>
					</div>
					<input type="checkbox" class="ocular" name="has_arco_senil" id="has_arco_senil" value="1" tabindex="61" <?php if(isset($record->has_arco_senil) && $record->has_arco_senil) { echo 'checked'; } ?>><label for="has_arco_senil">Arco senil</label><br>
					<div class="occular_options" id="radio_has_arco_senil" <?php if(isset($record->has_arco_senil) && $record->has_arco_senil) { echo 'style="display: block;"'; } ?>>
						<input type="radio" name="arco_senil" id="arco_senil_yes" value="1" tabindex="62" <?php if(isset($record->has_arco_senil) && $record->has_arco_senil == 1) { echo 'checked="checked"'; } ?>><label for="arco_senil_yes">Si</label>
						<input type="radio" name="arco_senil" id="arco_senil_no" value="2" tabindex="63" <?php if(isset($record->has_arco_senil) && $record->has_arco_senil == 2) { echo 'checked="checked"'; } ?>><label for="arco_senil_no">No</label>
					</div>
					<input type="checkbox" class="ocular" name="has_amsler" id="has_amsler" value="1" tabindex="64" <?php if(isset($record->has_amsler) && $record->has_amsler) { echo 'checked'; } ?>><label for="has_amsler" >Amsler</label><br>
					<div class="occular_options" id="radio_has_amsler" <?php if(isset($record->has_amsler) && $record->has_amsler) { echo 'style="display: block;"'; } ?>>
						<input type="radio" name="amsler" id="amsler_yes" value="1" tabindex="65" <?php if(isset($record->has_amsler) && $record->has_amsler == 1) { echo 'checked="checked"'; } ?>><label for="amsler_yes">Si</label>
						<input type="radio" name="amsler" id="amsler_no" value="2" tabindex="66" <?php if(isset($record->has_amsler) && $record->has_amsler == 2) { echo 'checked="checked"'; } ?>><label for="amsler_no">No</label>
					</div>
				</td>
				<td>
					<label for="disco">Disco</label><br>
					<input type="text" name="disco" id="disco" placeholder="Disco" value="<?php if(isset($record->disco)) { echo $record->disco; } ?>" tabindex="67"><br>
				</td>
			</tr>
			<tr>
				<td>
					<label for="tonometria_oi">Tonometría OI</label><br>
					<input type="number" step="any" name="tonometria_oi" id="tonometria_oi" placeholder="Tonometría OI" tabindex="50" value="<?php if(isset($record->tonometria_oi)) { echo $record->tonometria_oi; } ?>">
				</td>
				<td>
					<label for="opacidades">Opacidades</label><br>
					<input type="text" name="opacidades" id="opacidades" placeholder="Opacidades" value="<?php if(isset($record->opacidades)) { echo $record->opacidades; } ?>" tabindex="68">
				</td>
			</tr>
			<tr>
				<td colspan="3"></td>
				<td>
					<label for="pantalla_tangente">Pantalla tangente</label><br>
					<input type="number" name="pantalla_tangente" id="pantalla_tangente" placholder="Pantalla tangente" value="<?php if(isset($record->pantalla_tangente)) { echo $record->pantalla_tangente; } ?>" tabindex="69">
				</td>
			</tr>
			<tr>
				<td colspan="3"></td>
				<td>
					<input type="checkbox" class="ocular" name="pupilas_selector" id="pupilas_selector" <?php if(isset($record->pupilas_redondas) && $record->pupilas_redondas) { echo 'checked'; } ?> tabindex="70">
					<label for="pupilas_selector">Pupilas (RILA)</label><br>
					<div class="occular_options" id="radio_pupilas_selector" <?php if(isset($record->pupilas_redondas) && $record->pupilas_redondas) { echo ' style="display: block;"'; } ?>>
								<label>Redondas</label><br>
								<input type="radio" name="pupilas_redondas" id="pupilas_redondas_yes" value="1" tabindex="71" <?php if(isset($record->pupilas_redondas) && $record->pupilas_redondas == 1) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_redondas_yes">Si</label>
						<input type="radio" name="pupilas_redondas" id="pupilas_redondas_no" value="2" tabindex="72" <?php if(isset($record->pupilas_redondas) && $record->pupilas_redondas == 2) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_redondas_no">No</label>
						<br>


						<label>Iguales</label><br>
						<input type="radio" name="pupilas_iguales" id="pupilas_iguales_yes" value="1" tabindex="74" <?php if(isset($record->pupilas_iguales) && $record->pupilas_iguales == 1) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_iguales_yes">Si</label>
						<input type="radio" name="pupilas_iguales" id="pupilas_iguales_no" value="2" tabindex="75" <?php if(isset($record->pupilas_iguales) && $record->pupilas_iguales == 2) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_iguales_no">No</label>
						<br>
	
						<label>Responden a la luz</label><br>
						<input type="radio" name="pupilas_luz" id="pupilas_luz_yes" value="1" tabindex="77" <?php if(isset($record->pupilas_luz) && $record->pupilas_luz == 1) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_luz_yes">Si</label>
						<input type="radio" name="pupilas_luz" id="pupilas_luz_no" value="2" tabindex="78" <?php if(isset($record->pupilas_luz) && $record->pupilas_luz == 2) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_luz_no">No</label>
						<br>
	
						<label>Responden a la acomodación</label><br>
						<input type="radio" name="pupilas_acomodacion" id="pupilas_acomodacion_yes" value="1" tabindex="80" <?php if(isset($record->pupilas_acomodacion) && $record->pupilas_acomodacion == 1) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_acomodacion_no">Si</label>
						<input type="radio" name="pupilas_acomodacion" id="pupilas_acomodacion_no" value="2" tabindex="81" <?php if(isset($record->pupilas_acomodacion) && $record->pupilas_acomodacion == 2) { echo 'checked="checked"'; } ?>>
						<label for="pupilas_acomodacion_no">No</label>
					</div>
				</td>
			</tr>
		</table>
		<br>
		<input type="submit" name="btn_submit" id="btn_submit" value="Almacenar datos" class="floatright" tabindex="82">
		<?php if(isset($patient)) { ?>
			<a href="?page=print&amp;type=resume&amp;id=<?php echo $record->id; ?>" target="_blank"><button type="button" id="btn_print_resume" class="floatright green" style="padding: 12px;">Imprimir resumen</button></a>
		<?php } ?>
	</form>
</fieldset>

<?php if(isset($patient)) { ?>

<script>
	jQuery('#record').find('input').attr('disabled', 'disabled');
	jQuery('#record').find('textarea').attr('disabled', 'disabled');
	jQuery('#record').find('select').attr('disabled', 'disabled');
</script>

<fieldset>
	<legend>Historial</legend>
	<?php
		$records = $patient->get_record_list();
		if($records) {
			echo '<h4>Examinaciones</h4>', PHP_EOL;
			echo '<table class="clickable_tr"><thead><tr><th>Fecha de visita</th><th>M:OD</th><th>M:OI</th><th>Observaciones</th></tr></thead><tbody>', PHP_EOL;
			foreach($records as $record) {
				echo '<tr onclick="document.location = \'?page=expedientes&patient_id=', $patient->id, '&record_id=', $record->id, '\'"><td>',
					substr($record->add_date, 0, 10),
					'</td><td>',
					(strlen($record->m_od) > 3 ? $record->m_od : '<em>Sin datos</em>'),
					'</td><td>',
					(strlen($record->m_oi) > 3 ? $record->m_oi : '<em>Sin datos</em>'),
					'</td><td>',
					(strlen($record->observaciones) > 50 ? substr($record->observaciones, 0, 50) . '...' : $record->observaciones),
					'</td></tr>', PHP_EOL;
			}
			echo '</tbody></table>', PHP_EOL;
		} else {
			echo '<p><em>El paciente no se cuenta con expedientes anteriores.</em></p>', PHP_EOL;
		}

		$notas = $patient->get_remission_list();
		if($notas) {
			echo '<h4>Notas de remision</h4>', PHP_EOL;
			echo '<table class="clickable_tr"><thead><tr><th>Fecha</th><th>Vendedor</th><th>Proceso</th><th>Total</th><th>Observaciones</th></thead><tbody>', PHP_EOL;
			foreach($notas as $nota) {
				echo '<tr onclick="document.location = \'?page=notas&id=', $nota['id'], '\'">',
					'<td>', substr($nota['add_date'], 0, 10), '</td>',
					'<td>', $nota['salesperson'], '</td>',
					'<td>', $nota['process'], '</td>',
					'<td>$', number_format($nota['total'], 2), '</td>',
					'<td>', (strlen($nota['observations']) > 50 ? substr($nota['observations'], 0, 50) . '...' : $nota['observations']), '</td>',
					'</tr>', PHP_EOL;
			}
			echo '</tbody></table>', PHP_EOL;
		}
	?>
</fieldset>
<?php } ?>
