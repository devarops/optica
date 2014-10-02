<?php
	/* This part of the system has turned into a royal mess...
	 * In a future version, implement some form of versioning,
	 * e.g. creating a child note with the new changes.
	 *
	 * Should be using templates here, at the very least. :\
	 */

	if(isset($_GET['id'])) {
		$rn = new RemissionNote($db, $_GET['id']);
		if(!isset($rn->down_payment)) {
			$rn->down_payment = 0;
		}
		$_GET['patient_id'] = $rn->patient_id;
	}

	if(isset($_GET['patient_id'])) {
		$patient = new Patient($db, $_GET['patient_id']);
	}


	$result = $db->query('SELECT id, name, default_price FROM lens WHERE NOT ignored ORDER BY name');
	$lenses = '';
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		if(!empty($row['name'])) {
			$lenses .= '{"label": "' . $row['name'] . '", "id": "' . $row['id'] . '", "price": "' . $row['default_price'] . '"}, ';
		}
	}

	$result = $db->query('SELECT id, price FROM frame WHERE remission_note_id IS NULL');
	$frames = '';
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$frames .= '{"label": "' . $row['id'] . '", "id": "' . $row['id'] . '", "price": "' . $row['price'] . '"}, ';
	}

	// Autocomplete for process
	$result = $db->query('SELECT DISTINCT process FROM remission_note ORDER BY process DESC');
	$process = '';
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$process .= '"' . $row['process'] . '", ';
	}
?>

<script>
	jQuery(function() {
		var selected_frames       = [];
		var available_lenses      = [<?php echo substr($lenses, 0, -2); ?>];
		var available_frames      = [<?php echo substr($frames, 0, -2); ?>];
		var available_salespeople = [<?php echo substr($salespeople, 0, -2); ?>];

		// If no patient is selected, disable all fields...
		if(!jQuery('#patient_id').val()) {
			jQuery('#frn input, #frn select, #frn button').prop('disabled', true);
		}

		// Check that salesperson field is filled in
		jQuery('#btn_nota').click(function(event) {
			if(!jQuery('#salesperson').val()) {
				event.preventDefault();
				alert('Hace falta eligír un vendedor.');
			}
		});

		// Product row removal
		jQuery('#product_list tbody tr a.rem_product').click(function() {
			if(jQuery('#product_list tbody tr').length > 1) {
				if(jQuery(this).parent().parent().children().children('select').val() == 'frame') {
					selected_frames.pop(jQuery(this).parent().parent().children().children('input[type=hidden]').val());
				}
				jQuery(this).parent().parent().remove();
				jQuery('#down_payment').change();
			}
		});

		// Unlock buttons
		jQuery('#employee_id, #employee_password').change(function() {
			jQuery('#add_row, #btn_nota, #down_payment, #salesperson, #commission, #observations, .item_name, .product_price').prop('disabled', true);
			jQuery('.row_options').hide();

			var id = jQuery('#employee_id').val();
			var pw = jQuery('#employee_password').val();

			if(!id || !pw) { return; }

			jQuery.post('resources/ajax/auth_employee.php', { id: id, pw: pw }, function(response) {
				if(response.is_authed) {
					jQuery('#btn_nota').prop('disabled', false);
					jQuery('#auth_status').html('Credenciales correctos!').css('color', '#090');
				} else {
					jQuery('#auth_status').html('Credenciales incorrectos!').css('color', '#900');
				}

				if(response.is_admin) {
					jQuery('#add_row, #down_payment, #commission, #observations, .item_name, .product_price').prop('disabled', false);
					jQuery('.row_options').show();
				}
			}, 'json');
		});

		// Unlink frame
		jQuery('.unlink_frame').click(function(event) {
			event.preventDefault();

			if(!confirm('Este acción devolverá el armazón al inventario. Quedará disponible para venta en otra nota de remisión. ¿Continuar?')) {
				return;
			}

			var r_id = jQuery('#remission_note_id').val();
			var f_id = jQuery(this).attr('data-id');
			var e_id = jQuery('#employee_id').val();
			var e_pw = jQuery('#employee_password').val();

			if(!r_id || !e_id || !e_pw) { return; }

			jQuery.post('resources/ajax/unlink_frame.php', { f_id: f_id, e_id: e_id, e_pw: e_pw, r_id: r_id }, function(response) {
				if(response.status == 'success') {
					jQuery('#frame_' + f_id).remove();
				}

				jQuery('#msgbox').append('<p class="notification ' + response.status + '">' + response.msg + '</p>');
				jQuery('#observations').append("\n" + response.msg_obs);
			}, 'json');

			jQuery('#down_payment').trigger('change'); // Recalculate costs
		});

		// Mark frame as defective
		jQuery('.defective').click(function(event) {
			event.preventDefault();

			if(!confirm('Este acción marcará el armazón como defectuoso, quitándolo de la nota de remisión. ¿Continuar?')) {
				return;
			}

			var r_id = jQuery('#remission_note_id').val();
			var f_id = jQuery(this).attr('data-id');
			var e_id = jQuery('#employee_id').val();
			var e_pw = jQuery('#employee_password').val();

			if(!r_id || !e_id || !e_pw) { return; }

			jQuery.post('resources/ajax/mark_frame_defective.php', { f_id: f_id, e_id: e_id, e_pw: e_pw, r_id: r_id }, function(response) {
				if(response.status == 'success') {
					jQuery('#frame_' + f_id).remove();
				}

				jQuery('#msgbox').append('<p class="notification ' + response.status + '">' + response.msg + '</p>');
				jQuery('#observations').append("\n" + response.msg_obs);
			}, 'json');

			jQuery('#down_payment').trigger('change');
		});

		// Product type change
		//jQuery('select.item_type').change(function() {
		jQuery('body').on('change', '.item_type', function() {
			type = jQuery(this).val();
			next_elem = jQuery(':input:eq(' + (jQuery(':input').index(this) + 1) + ')');
			item_id_field = jQuery(next_elem).siblings('input[type=hidden]');

			jQuery(item_id_field).val('');
			jQuery(next_elem).removeClass('lens frame other');
			jQuery(next_elem).addClass(type);
			jQuery('.item_name').prop('readonly', false);
			jQuery('.product_price').prop('readonly', false);

			if(type == 'lens') {
				jQuery('.lens').autocomplete({ source: available_lenses, select: function(event, ui) {
					jQuery(this).val(ui.item.label);
					jQuery(this).siblings('input[type=hidden]').val(ui.item.id);
					jQuery(this).parent().siblings('.iprice').children('input[type=number]').val(ui.item.price).change();
					return false;
				}});
			} else if(type == 'frame') {
				jQuery('.frame').autocomplete({ source: available_frames, select: function(event, ui) {
					if(selected_frames.indexOf(ui.item.id) == -1) {
						selected_frames.push(ui.item.id);
						jQuery(this).val(ui.item.label);
						jQuery(this).siblings('input[type=hidden]').val(ui.item.id);
						jQuery(this).parent().siblings('.iprice').children('input[type=number]').val(ui.item.price).change();
					}
					return false;
				}});
			}
		});

		//jQuery('.item_name').change(function() {
		jQuery('body').on('change', '.item_name', function() {

			if(jQuery(this).hasClass('frame')) {
				var frame_id = jQuery(this).val();
				if(available_frames.indexOf(frame_id) == -1) {
					var errormsg = jQuery('<p class="small error">El armazón ' + jQuery(this).val() + ' no existe o ya fue vendido.</p>').delay('4000').fadeOut();
					jQuery(this).parent().append(errormsg);
					jQuery(this).val('');
					jQuery(this).parent().siblings('.iprice').children('input[type=number]').val('').change();
				}
			} else if(jQuery(this).hasClass('lens')) {
				var lens = jQuery(this).val();
				if(available_lenses.indexOf(lens) == -1) {
					var errormsg = jQuery('<p class="small error">El lente ' + lens + ' no existe.</p>').delay('4000').fadeOut();
					jQuery(this).parent().append(errormsg);
					jQuery(this).val('');
					jQuery(this).parent().siblings('.iprice').children('input[type=number]').val('').change();
				}
			}
		});

		// Cost calculation
		jQuery('.product_price, #down_payment').change(function() {
			var commission = 0;
			var subtotal   = 0;
			var abono      = 0;
			
			jQuery('#product_list .product_price').each(function(key, elem) {
				item_val = parseFloat(jQuery(elem).val());
				if(!isNaN(item_val)) {
					subtotal += item_val;
				}
			});

			abono = parseFloat(jQuery('#down_payment').val());
			if(isNaN(abono)) {
				abono = 0;
			}

			commission = subtotal * 0.03;

			jQuery('#subtotal').html(subtotal.toFixed(2));
			jQuery('#total').html((subtotal - abono).toFixed(2));
			jQuery('#total_hidden').val((subtotal - abono).toFixed(2));
			<?php if(!$rn) { // Only dynamically update commission if it's a NEW remission note ?>
			jQuery('#commission').val('$' + commission.toFixed(2));
			<?php } ?>
		});
	});

	function patient_search() {
		input = jQuery('#search_kwds').val();
		if(input.length % 3 == 0) {
			jQuery.post('resources/ajax/patient_search.php', { search_string: input }, function(data) {
				jQuery('#patient_search_result').html(data);
				jQuery('.tablesorter').tablesorter(); // Have to reinitialize the plugin after altering search results
			});
		} else if(input.length == 0) {
			jQuery('#patient_search_result').html('');
		}
	}

	function add_product_row() {
		new_row = jQuery('#product_list tbody > tr:last').clone(true);
		jQuery(new_row).children('.iname').children().remove();
		jQuery(new_row).children('.iname').append('<input type="text" name=item_name[]" class="item_name" placeholder="Producto" readonly="readonly" style="width: 80%;"><input type="hidden" name="item_id[]" class="item_id">');

		var first_td = jQuery(new_row).children('td:first-child');
		if(jQuery(first_td).hasClass('empty')) {
			jQuery(first_td).empty();
			jQuery(first_td).append('<select name="item_type[]" class="item_type"><option selected="" disabled="">–</option><option value="lens">Lente</option><option value="frame">Armazón</option><option value="other">Otro</option></select>');
		}

		new_row.insertAfter('#product_list tbody > tr:last');
		jQuery('#product_list tbody > tr:last input').val('');
	}

</script>


<h1><img src="resources/img/icon-bill.png" height="48" width="48" alt=""> Notas de remisión</h1>

<label for="search_kwds">Realizar búsqueda</label><br>
<input type="search" name="search_kwds" id="search_kwds" oninput="patient_search();" tabindex="1" results="10" placeholder="Nombre(s) y/o apellido(s)">
o
<form action="" method="get" style="display: inline-block; width: 47%;">
	<input type="hidden" name="page" value="notas">
	<input type="search" name="id" id="search_id" tabindex="2" results="1" placeholder="ID de nota" style="width: 75%;">
	<input type="submit" id="btn_submit" value="Buscar nota">
</form>
<div id="patient_search_result" class="search result"></div>

<form action="" method="post" id="frn">
	<fieldset id="nota_remision">
		<legend><?php echo (!isset($rn) ? 'Añadir nota de remisión' : '#' . $rn->id . ' &mdash; Nota de remisión, ' . substr($rn->add_date, 0, 10)); ?></legend>
		<table class="noeffects" style="width: 600px;">
			<tbody>
				<tr>
					<td>
						<label for="patient">Paciente</label><br>
						<p id="patient_name"><strong><?php echo (isset($patient) ? $patient->get_full_name() : '---' ); ?></strong></p>
						<input type="hidden" name="patient_id" id="patient_id" value="<?php echo (isset($patient) ? $patient->id : ''); ?>">
					</td>
					<td>
						<label for="add_date">Fecha</label><br>
						<input type="date" name="add_date" id="add_date" value="<?php echo (isset($rn) ? substr($rn->add_date, 0, 10) . '" disabled="disabled' : date('Y-m-d')); ?>">
					</td>
				</tr>
			</tbody>
		</table>

		<h2>Productos</h2>
		<table id="product_list" class="noeffects" style="width: 600px;">
			<thead>
				<tr><th><?php if(!isset($rn)) { echo 'Tipo'; } ?></th><th>Nombre / descripción / ID</th><th>Precio</th></tr>
			<thead><tbody>
<?php
	if(isset($rn)) {
		echo '<tbody>', PHP_EOL;
		$products = $rn->get_product_list();
		$subtotal = 0;

		foreach($products as $product) {
			echo (!empty($product['id']) ? '<tr id="frame_' . $product['id'] . '">' : '<tr>'),
				'<td class="empty"><input type="hidden" name="item_type[]" value="', (!empty($product['id']) ? 'frame' : ''), '"></td>',
				'<td class="iname"><input type="text" name="item_name[]" class="item_name" placeholder="', $product['name'], '" value="', $product['name'], '" style="width: 80%;" readonly="readonly">',
					'<input type="hidden" name="item_id[]" class="item_id" value="', (!empty($product['id']) ? $product['id'] : ''), '"></td>',
				'<td class="iprice"><input type="number" name="item_price[]" class="product_price" placeholder="', $product['price'], '" value="', $product['price'], '" step="any" readonly="readonly"></td>';
				if($rn->id != -1) {
					echo '<td class="row_options">',
						(empty($product['id']) ? '<a class="rem_product" title="Borrar renglón">' : '<a class="unlink_frame" title="Devolver armazón al inventorio" data-id="' . $product['id'] . '">'), '<img src="resources/img/icon-delete.png" height="16" width="16" alt="Borrar renglón"></a>',
						(!empty($product['id']) ? '<a data-id="' . $product['id'] . '" class="defective" title="Marcar como defectuoso">' .
							'<img src="resources/img/icon-trash.png" height="16" alt="Marcar como defectuoso"></a>' : ''),
					'</td></tr>';
				}
			//echo '<tr><td style="width: 80px;"></td><td>', $product['name'], '</td><td style="width: 220px;">$', $product['price'], '</td></tr>', PHP_EOL;
			$subtotal += $product['price'];
		}
?>
			</tbody>
		</table>
<?php
	} else {
?>
				<tr>
					<td><select name="item_type[]" class="item_type"><option selected disabled>&ndash;</option><option value="lens">Lente</option><option value="frame">Armazón</option><option value="other">Otro</option></select></td>
					<td class="iname"><input type="text" name="item_name[]" class="item_name" placeholder="Producto" style="width: 80%;" readonly="readonly"><input type="hidden" name="item_id[]" class="item_id" value=""></td>
					<td class="iprice"><input type="number" name="item_price[]" class="product_price" placeholder="Precio" step="any" readonly="readonly"></td>
					<td class="row_options"><a class="rem_product" title="Borrar renglón"><img src="resources/img/icon-delete.png" height="16" width="16" alt="Borrar renglón"></a></td>
				</tr>
			</tbody>
		</table>
<?php } ?>
		<button type="button" name="add_row" id="add_row" onclick="add_product_row();" class="green"<?php echo (isset($rn) ? 'disabled="disabled"' : ''); ?>><strong>+</strong> Agregar renglón</button>

		<table class="noeffects" style="width: 600px;">
			<tbody>
				<tr>
					<td colspan="2" style="text-align: right;"></td>
					<th style="width: 150px; text-align: right;">Total</th>
					<td style="text-align: right; width: 150px;">$<span id="subtotal"><?php echo (isset($rn) ? number_format($subtotal, 2) : '00.00'); ?></span></td>
				</tr>
				<tr>
					<th colspan="3" style="text-align: right; vertical-align: middle;"><label for="down_payment" style="font-size: 1.0em;">Abono</label></th>
					<td style="text-align: right; width: 150px;"><input type="number" name="down_payment" id="down_payment" min="0" step="any" placeholder="Abono" style="width: 100px; text-align: right;"<?php if(isset($rn)) { echo ' value="', ($rn->down_payment > 0 ? $rn->down_payment : 0), '" disabled="disabled"'; } ?>></td>
				</tr>
				<tr>
					<th colspan="3" style="text-align: right;">Saldo</th>
					<td style="text-align: right; width: 150px;">
						$<span id="total"><?php echo (isset($rn) ? number_format(($subtotal - $rn->down_payment), 2) : '00.00'); ?></span>
						<input type="hidden" id="total_hidden" name="total" value="<?php echo (isset($rn) ? ($subtotal - $rn->down_payment) : 0); ?>"></td>
				</tr>
				<tr><td><br></td></tr>

				<tr>
					<td colspan="2">
						<label for="salesperson">Vendedor</label><br>
						<?php 
							if(isset($rn)) {
								echo '<input type="hidden" name="salesperson_id" value="', $rn->salesperson_id, '">'; 
							}
						?>
						<select name="salesperson_id" id="salesperson" placeholder="Vendedor" style="width: 90%;" <?php if(isset($rn)) { echo ' disabled="disabled"'; } ?>>
						<option selected disabled>Vendedor</option>
						<?php
							foreach(Employee::getEmployees($db) as $employee) {
								echo '<option value="', $employee['id'], '"', (isset($rn) && $rn->salesperson_id == $employee['id'] ? ' selected' : '')  , '>' . $employee['name'] . '</option>' . PHP_EOL;
							}
						?>
						</select>
					</td>
					<td colspan="2">
						<label for="process">Proceso</label><br>
						<select name="process" id="process" style="width: 100%;">
							<option selected disabled>&ndash;</option>
							<?php
								foreach(['APARTADOS', 'VENDIDOS', 'ENTREGADOS', 'DONADOS'] as $opt) {
									echo '<option value="', $opt, '"', (isset($rn) && $rn->process == $opt ? ' selected' : ''), '>', $opt, '</option>', PHP_EOL;
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<label for="commission">Comisión</label><br>
						<input type="text" id="commission" name="commission" placeholder="Comisión" style="width: 78%;" disabled="disabled" value="<?php echo (isset($rn) ? number_format($rn->commission, 2) : ''); ?>">
					</td>
					<td colspan="2">
						<label for="observations">Observaciones</label><br>
						<textarea name="observations" id="observations" placeholder="Observaciones" style="width: 90%; height: 90px;"<?php if(isset($rn)) { echo ' disabled="disabled">', (!empty($rn->observations) ? $rn->observations : ' '); } else { echo '>'; } ?></textarea>
					</td>
				</tr>
				<tr>
					<td><br></td>
				</tr>

				<tr>
					<td colspan="2">
						<?php
							if(isset($rn) && $rn->comission_claimed > 0) {
								echo '<span style="color: #090;">Comisión reclamada ' . $rn->comission_claimed . '.</span>';
							}
						?>
					</td>
					<td colspan="2" style="text-align: right;">
						<?php if(isset($rn)) { ?>
							<a href="?page=print&amp;type=patient_remission&amp;id=<?php echo $rn->id; ?>" target="_blank">Vista previa de la versión del paciente &nbsp;<img src="resources/img/icon-print.png" height="16" width="16" alt="Print"></a><br>
							<a href="?page=print&amp;type=optician_remission&amp;id=<?php echo $rn->id; ?>" target="_blank">Vista previa de la versión de la óptica &nbsp;<img src="resources/img/icon-print.png" height="16" width="16" alt="Print"></a><br><br>
						<?php } ?>
					</td>
				</tr>
				<?php if(isset($rn)) {
					echo '<input type="hidden" name="is_update" value="true">';
					echo '<tr><td colspan="2">', PHP_EOL;
					echo '<select name="employee_id" id="employee_id"><option selected disabled>Empleado</option>', PHP_EOL;
					foreach(Employee::getEmployees($db) as $employee) {
						echo '<option value="', $employee['id'], '">' . $employee['name'] . '</option>' . PHP_EOL;
					}
					echo '</select>', PHP_EOL;
					echo '<input type="password" name="employee_password" id="employee_password" placeholder="Contraseña" style="width: 70px;">', PHP_EOL;
					echo '<br><span id="auth_status" class="small"></span>', PHP_EOL;
				?>
						
					</td><td colspan="2" style="text-align: right;">
						<input type="hidden" name="remission_note_id" id="remission_note_id" value="<?php echo $rn->id; ?>">
						<input type="submit" id="btn_nota" name="btn_nota" value="Actualizar nota" disabled>
					</td></tr>
					<?php } else { ?>
						<tr><td colspan="4" style="text-align: right;">
							<input type="submit" id="btn_nota" name="btn_nota" value="Guardar nota de remisión">
						</td></tr>
					<?php } ?>
				</td></tr>
			</tbody>
		</table>
	</fieldset>
</form>
