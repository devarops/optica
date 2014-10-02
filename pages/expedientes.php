<?php
	$glaucoma_study_id = 4;

	if(isset($_GET['patient_id'])) {
		$patient = new Patient($db, $_GET['patient_id']);

		if(isset($_GET['record_id'])) {
			$record = $patient->get_record($_GET['record_id']);
		} else {
			$record = $patient->get_latest_record();
		}


		// Handle image uploads
		if(isset($_POST['btn_upload_image'])) {
			$img  = new Image($db);
			$desc = $_POST['description'];
			$tags = $_POST['tags'];

			if($img->upload($patient->id, $_FILES['userfile'], $desc, $tags)) {
				echo '<p class="notification success"><strong>Éxito:</strong> La imagen he sido guardada.</p>';
			} else {
				echo '<p class="notification error"><strong>Error:</strong> No se pudo guardar la imagen.</p>';
			}
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
		jQuery('#record').find('textarea').prop('disabled', false);
		jQuery('#record').find('select').prop('disabled', false);
		jQuery('#record').find('input').prop('disabled', false);
		jQuery('#record .input-group').prop('disabled', false);
		jQuery('#in_glaucoma_study').prop('disabled', true);
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

	jQuery(document).ready(function() {

		/* OD/OI reflection */
		jQuery('.od_oi_reflection').change(function() {
			var origin = jQuery(this).data('origin');
			var target = jQuery(this).data('target');

			if(this.checked) {
				jQuery(target).val(jQuery(origin).val());
				jQuery(target).prop('readonly', true);
			} else {
				jQuery(target).prop('readonly', false);
			}
		});

		/* WPM chart */
		jQuery('#lectura').bind('change', function(event) {
			event.preventDefault();

			var wpm = jQuery('#lectura').val();
			var age = jQuery('#birthdate').val();
			if(jQuery('#age_at_visit').length) { 
				age = jQuery('#age_at_visit').val();
			}

			if(wpm && !isNaN(wpm) && wpm > 0) {
				if(!age || isNaN(age)) {
					// We need the patient's age to get the right wpm normal distribution
					jQuery('#lectura-error').html('Por favor ingrese edad.').show();
					jQuery('#birthdate').bind('change', function() { jQuery('#lectura').trigger('change'); });
					return;
				}

				jQuery.post('resources/ajax/wpm_normal_dist.php', { age: age, wpm: wpm }, function(data) {
					jQuery('#wpm_chart').empty();
					var values   = JSON.parse(data);

					if(!values) {
						jQuery('#lectura-error').html('Actualmente no hay datos suficientes para este edad.').show();
						return;
					}

					var wpm_plot = jQuery.jqplot('wpm_chart', [values.zipped, [values.patient]], {
						title: {
							text: 'Promedio ' + age + ' años: ' + Math.round(values.x[3]) +' PPM',
							fontSize: '10pt',
						},
						series: [
							{
								lineWidth: 1,
								rendererOptions: { smooth: true },
								markerOptions: {
									size: 4,
								}
							},
							{ 
								showLine: false,
								markerOptions: {
									size:  8,
									style: 'diamond'
								}
							}
						],
						axes: {
							xaxis: {
								ticks: [Math.max(0,values.x[0])].concat(values.x.slice(1)),
								tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
								tickOptions: {
									//angle: -35,
									formatString: '%d',
									fontSize: '8pt',
									/*formatter: function(format, value) {
										var label = values.x.indexOf(value) - 3;
										return label.toString();
									}*/
								}
							},
							yaxis: {
								min: 0,
								showTicks: false,
								tickOptions: {
									/*
									formatter: function(format, value) {
										return (value * 100).toFixed(2) + '%';
									}
									*/
								}
							}
						},
						canvasOverlay: {
							show: true,
							objects: [
								{ dashedVerticalLine: {
									name: '-2std',
									x: values.x[1],
									lineWidth: 1,
									dashPattern: [3, 3],
									yOffset: '0',
									color: 'rgb(0, 0, 0)',
									shadow: false
								}},	
								{ line: {
									name: 'background',
									start: [values.x[1], 0],
									stop: [values.x[5], 0],
									lineWidth: 300,
									lineCap: 'butt',
									color: 'rgba(220, 235, 255, 0.25)',
									shadow: false
								}},	
								{ dashedVerticalLine: {
									name: '+2std',
									x: values.x[5],
									lineWidth: 1,
									dashPattern: [3, 3],
									yOffset: '0',
									color: 'rgb(0, 0, 0)',
									shadow: false
								}}

							],
						}
					});
				});

				jQuery('#lectura-error').hide();
			}

			/* Save a chart by clicking on it */
			/*
			jQuery('.chart').on('click', function() {
				jQuery(this).jqplotSaveImage();
			});
			*/

		});

		<?php
			if($patient->merged_with) {
				echo "jQuery('button, input[type=submit]').remove();", PHP_EOL;
				echo "jQuery('input, select, textarea').prop('disabled', true);", PHP_EOL;
				echo "jQuery('#search_kwds').prop('disabled', false);", PHP_EOL;
				echo "alert('Este paciente he sido fusionado!');", PHP_EOL; 
			}
		?>


		<?php if(isset($record->lectura)) { echo "jQuery('#lectura').trigger('change');"; } ?>
	});
</script>

<h1><img src="resources/img/icon-dossier.png" height="48" width="48" alt=""> Expedientes</h1>

<label for="search_kwds">Realizar búsqueda</label><br>
<input type="search" name="search_kwds" id="search_kwds" oninput="patient_search();" tabindex="1" results="10" placeholder="Nombre(s) y/o apellido(s)">
<div id="patient_search_result" class="search result"></div>

<fieldset>
	<legend><?php
		echo (isset($patient) ? $patient->get_full_name(true) : 'Expediente nuevo');
		echo ($patient->merged_with ? ' &ndash; Fusionado con #<a href="?page=expedientes&patient_id=' . $patient->merged_with . '">' . $patient->merged_with . '</a>' : '');
	?></legend>
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
					<label for="birthdate">Edad actual</label><br>
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
					<textarea name="patient[comments]" id="comments" style="width: 88%; height: 100px;" tabindex="8" placeholder="Notas sobre el paciente"><?php if(isset($patient->comments)) { echo $patient->comments; } ?></textarea>
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
				<a href="?page=herramientas&merge_id=<?php echo $patient->id; ?>"><button type="button" class="yellow floatright">Marcar para fusión</button></a>
				<button style="margin-right: 3px;" type="button" id="btn_unlock" class="floatright" onclick="unlock_new_record();">Nueva examinación</button>
				<a href="?page=notas&amp;patient_id=<?php echo $patient->id; ?>"><button type="button" id="btn_nota" class="green floatright">Agregar nota de remisión</button></a>
			<?php } ?>
				</td>
			</tr>
		</table>

		<!-- PATIENT ends here -->

		<table id="record" class="noeffects">
			<input type="hidden" name="new_record" value="1">
			<?php
				if(isset($record) && is_numeric($patient->birthdate)) {
					echo '<input type="hidden" id="age_at_visit" value="' . ($record->add_date - $patient->birthdate) . '">' . PHP_EOL;
				}
			?>
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
					<input type="checkbox" name="has_glaucoma_history" id="has_glaucoma_history" value="1" tabindex="17" <?php if(isset($record->has_glaucoma_history) && $record->has_glaucoma_history) { echo 'checked'; } ?>><label for="has_glaucoma_history">Antecedentes familiares de glaucoma</label><br>
					<input type="checkbox" id="in_glaucoma_study" disabled <?php echo (isset($patient) && $patient->is_participant($glaucoma_study_id) ? ' checked' : ''); ?>><label for="in_glaucoma_study">Diagnosticado con glaucoma</label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="addictions">Adicciones</label><br>
					<input type="text" name="addictions" id="addictions" placeholder="Adicciones" tabindex="18" value="<?php if(isset($record->addictions)) { echo $record->addictions; } ?>">
				</td>
				<td>
					<div class="input-group">
						<label for="w_od">W:OD</label><br>
						<input type="text" name="w_od" id="w_od" placeholder="       (       %       [       " tabindex="19" value="<?php if(isset($record->w_od)) { echo $record->w_od; } ?>" oninput="format_odoi('#w_od');">
						<div class="input-addon">
							<input type="checkbox" id="w_reflect" tabindex="19" class="od_oi_reflection" data-origin="#w_od" data-target="#w_oi">
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<label for="w_oi">W:OI</label><br>
					<input type="text" name="w_oi" id="w_oi" placeholder="       (       %       [       " tabindex="20" value="<?php if(isset($record->w_oi)) { echo $record->w_oi; } ?>" oninput="format_odoi('#w_oi');" style="width: 82%;">
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
					<div class="input-group">
						<label for="m_od">M:OD</label><br>
						<input type="text" name="m_od" id="m_od" placeholder="       (       %       [       " value="<?php if(isset($record->m_od)) { echo $record->m_od; } ?>" oninput="format_odoi('#m_od');" tabindex="27">
						<div class="input-addon">
							<input type="checkbox" id="m_reflect" tabindex="27" class="od_oi_reflection" data-origin="#m_od" data-target="#m_oi">
						</div>
					</div>
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
					<div class="input-group">
						<label for="k_od">K:OD</label><br>
						<input type="text" name="k_od" id="k_od" size="15" placeholder="        /        x        " value="<?php if(isset($record->k_od)) { echo $record->k_od; } ?>" oninput="format_k('#k_od');" tabindex="37" style="width: 43%;">
						<div class="input-addon">
							<input type="checkbox" id="k_reflect" tabindex="37" class="od_oi_reflection" data-origin="#k_od" data-target="#k_oi">
						</div>
						<span><?php if(isset($record->k_od)) { echo $record->k_eval('k_od'); } ?></span>
					</div>
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
					<span id="lectura-error" style="display: none; color: #900;"></span>
					<div id="wpm_chart" class="chart" style="width: 230px; height: 150px; margin: -40px 0 0 -30px;"></div>
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
	jQuery('#record .input-group').addClass('disabled');
</script>


<fieldset>
	<legend>Imágenes</legend>
	<div id="thumbnails">
		<?php
			$images = $patient->get_image_list();
			if($images) {
				foreach($images as $idx => $img) {
					$taglist = array();
					foreach($img->getTags() as $tag) {
						$taglist[] = $tag;
					}
					sort($taglist);
					echo '<div class="thumbnail fancybox" data-fancybox-group="image-gallery" data-fancybox-href="#gallery-item-' . $idx . '" style="background-image: url(\'' . $img->path . '\');">
						<div class="tags">';
					foreach($taglist as $tag) {
						echo '<span class="tag">' . $tag . '</span>';
					}
					echo '</div></div>' . PHP_EOL;

					echo '<div class="gallery-item" id="gallery-item-' . $idx . '" style="display: none;">';
					echo '<img src="' . $img->path . '">';
					echo '<table class="noeffects"><tbody><tr><td colspan="2" style="text-align: center;"><h3>Información</h3></td></tr>';
					echo '<tr><th>Paciente</th><td>' . $patient->get_full_name() . '</td></tr>';
					echo '<tr><th>Fecha</th><td>' . $img->created_at . '</td></tr>';
					echo '<tr><td colspan="2">&nbsp;</td></tr>';
					echo '<tr><th colspan="2">Descripción</th></tr>';
					echo '<tr><td colspan="2">' . $img->description . '</td></tr>';
					echo '<tr><th colspan="2">Etiquetas</th></tr>';
					echo '<tr><td colspan="2"><ul>';
					foreach($taglist as $tag) {
						echo '<li>' . $tag . '</li>';
					}
					echo '</ul></td></tr>';
					echo '</tbody></table>';
					echo '</div>';
				}
			} else{
				echo '<p><em>El paciente no cuenta con imágenes.</em></p>';
			}
		?>
	</div>
	<div class="clearfloat"></div>

	<hr>

	<h4>Subir nueva imagen</h4>
	<form action="?page=expedientes&patient_id=<?php echo $patient->id; ?>" method="post" enctype="multipart/form-data">
		<table class="noeffects">
			<tbody>
				<tr><td>
					<label for="userfile">Imagen a subir</label><br>
					<input type="file" name="userfile" id="userfile" style="border: none; box-shadow: none;">
				</td><td>
					<label for="description">Descripción</label><br>
					<textarea name="description" id="description" placeholder="Notas misceláneas&hellip;"></textarea>
				</td><td>
					<label for="tags">Etiquetas</label><br>
					<select id="tags" name="tags[]" data-placeholder="Elige etiquetas" multiple class="chosen-select">
						<option value></option>
						<?php
							$tags = Image::getTagOptions($db);
							foreach($tags as $id => $tag) {
								echo '<option value="' . $id . '">' . $tag . '</option>';
							}
						?>
				</td><td>
					<input type="hidden" name="MAX_FILE_SIZE" value="5000000">
					<input type="submit" name="btn_upload_image" id="btn_upload_image" class="floatright" value="Subir imagen">
				</td></tr>
			</tbody>
		</table>
	</form>
</fieldset>



<fieldset>
	<legend>Historial</legend>
	<div class="history" style="width: 55%; float: left; border-right: 1px solid #ccc;">
	<?php
		$records = $patient->get_record_list();
		$pio     = [];
		if($records) {
			//echo '<div class="history"', ($num_records >= 6 ? ' style="width: 65%; float: left; border-right: 1px solid #ccc;' : ''), '">';
			echo '<h4>Examinaciones</h4>', PHP_EOL;
			echo '<table class="clickable_tr" style="width: 98%;"><thead><tr><th>Fecha de visita</th><th>M:OD</th><th>M:OI</th><th>Observaciones</th></tr></thead><tbody>', PHP_EOL;
			foreach($records as $record) {
				// Log any nonzero tonometry value
				if($record->tonometria_od > 0 || $record->tonometria_oi > 0) {
					$pio[] = ['od' => $record->tonometria_od, 'oi' => $record->tonometria_oi, 'ts' => $record->add_date, 'id' => $record->id];
				}

				echo '<tr onclick="document.location = \'?page=expedientes&patient_id=', $patient->id, '&record_id=', $record->id, '\'"><td>',
					substr($record->add_date, 0, 10),
					'</td><td>',
					(strlen($record->m_od) > 3 ? $record->m_od : '<em>Sin datos</em>'),
					'</td><td>',
					(strlen($record->m_oi) > 3 ? $record->m_oi : '<em>Sin datos</em>'),
					'</td><td>',
					(strlen($record->observaciones) > 25 ? substr($record->observaciones, 0, 25) . '&hellip;' : $record->observaciones),
					'</td></tr>', PHP_EOL;
			}
			echo '</tbody></table>', PHP_EOL;
		} else {
			echo '<p><em>El paciente no se cuenta con expedientes anteriores.</em></p>', PHP_EOL;
		}

		$notas = $patient->get_remission_list();
		if($notas) {
			echo '<h4>Notas de remision</h4>', PHP_EOL;
			echo '<table class="clickable_tr" style="width: 98%;"><thead><tr><th>Fecha de visita</th><th>Vendedor</th><th>Proceso</th><th>Total</th><th>Observaciones</th></thead><tbody>', PHP_EOL;
			foreach($notas as $nota) {
				echo '<tr onclick="document.location = \'?page=notas&id=', $nota['id'], '\'">',
					'<td>', substr($nota['add_date'], 0, 10), '</td>',
					'<td>', $nota['salesperson'], '</td>',
					'<td>', $nota['process'], '</td>',
					'<td>$', number_format($nota['total'], 2), '</td>',
					'<td>', (strlen($nota['observations']) > 25 ? substr($nota['observations'], 0, 25) . '&hellip;' : $nota['observations']), '</td>',
					'</tr>', PHP_EOL;
			}
			echo '</tbody></table>', PHP_EOL;
		}
	?>
	</div>
	
	<?php if(sizeof($pio) >= 6) {
		usort($pio, function($a, $b) { return $a['id'] - $b['id']; });
	?>
	<div class="control-iop" style="border-left: 1px solid #ccc; width: 43%; float: left; margin-left: -1px; padding-left: 10px;">
		<h4 style="text-align: center;">Control de presión intraocular</h4>

		<div id="pio_od" class="chart" style="width: 49%; height: 150px; float: left;"></div>
		<div id="pio_oi" class="chart" style="width: 49%; height: 150px; float: right;"></div>

		<table style="width: 100%; float: left; margin-top: 10px;">
			<thead>
				<tr><th>#</th><th>Fecha y hora</th><th>Presión OD</th><th>Presión OI</th></tr>
			</thead>
			<tbody>
			<?php
				$series_od = [];
				$series_oi = [];
				$count     = 0;

				foreach($pio as $m) {
					printf('<tr><th>%d</th><td>%s</td><td>%.2f</td><td>%.2f</td>', $count + 1, $m['ts'], $m['od'], $m['oi']);

					$series_od[] = $m['od'];
					$series_oi[] = $m['oi'];
					$count++;
				}	

				$avg_od = array_sum($series_od) / $count;
				$avg_oi = array_sum($series_oi) / $count;
			?>
			</tbody>
		</table>

		<script>
			function piochart(target, title, data) {
				var chart = jQuery.jqplot(target, data, {
					title: {
						text: title,
						fontSize: '10pt'
					},
					series: [
						{ // Average line
							lineWidth: 1,
							markerOptions: { show: false },
						},
						{ // Measurements line
							lineWidth: 1,
							markerOptions: { show: true, style: 'filledCircle', lineWidth: 1, size: 5 },
						},
					],
					axes: {
						xaxis: {
							pad: 0,
							label: '# Visita',
							labelOptions: { fontSize: '12px' },
							//tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
							ticks: [<?php echo join(', ', range(1, $count)); ?>],
							tickOptions: {
								formatString: '%d',
							}
						},
						yaxis: {
							min: <?php $min = min(min($series_od), min($series_oi)); echo round($min *= 0.8, 1); ?>,
							max: <?php $max = max(max($series_od), max($series_oi)); echo round($max *= 1.2, 1); ?>,
							label: 'Presión (mmHg)',
							labelOptions: { fontSize: '12px' },
							labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
							tickOptions: {
								formatString: '%.2f'
							}
						}
					}
				});
			}

			piochart('pio_od', '<?php printf("Ojo derecho, λ=%.2f", $avg_od); ?>',   [[<?php echo join(', ', array_fill(0, $count, $avg_od)); ?>], [<?php echo join(', ', $series_od); ?>]]);
			piochart('pio_oi', '<?php printf("Ojo izquierdo, λ=%.2f", $avg_oi); ?>', [[<?php echo join(', ', array_fill(0, $count, $avg_oi)); ?>], [<?php echo join(', ', $series_oi); ?>]]);

			//var img_pio_od = jQuery('#pio_od').jqplotToImageStr({});
			//jQuery('#pio_od').jqplotSaveImage({});
			//jQuery('#dl_pio_od').attr('href', jQuery('#pio_od').jqplotToImageStr({}));
			//jQuery('#dl_pio_oi').attr('href', jQuery('#pio_oi').jqplotToImageStr({}));
		</script>


		<?php //echo nl2br(print_r($pio, true)); ?>
	</div>
</fieldset>

<?php } } ?>
