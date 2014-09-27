<?php
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


	$result = $db->query('SELECT id, name, default_price FROM lens ORDER BY name');
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

	// Autocomplete for salesperson
	/* (!) Deprecated.
	$result = $db->query('SELECT DISTINCT salesperson FROM remission_note ORDER BY salesperson DESC');
	$salespeople = '';
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$salespeople .= '"' . $row['salesperson'] . '", ';
	}
	 */

	// Autocomplete for process
	$result = $db->query('SELECT DISTINCT process FROM remission_note ORDER BY process DESC');
	$process = '';
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$process .= '"' . $row['process'] . '", ';
	}
?>

<script>
	jQuery(function() {
		var available_lenses = [<?php echo substr($lenses, 0, -2); ?>];
		var available_frames = [<?php echo substr($frames, 0, -2); ?>];
		var available_salespeople = [<?php echo substr($salespeople, 0, -2); ?>];
		//var available_process = [<?php echo substr($process, 0, -2); ?>];
		var selected_frames = [];

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

		// Salesperson autocomplete
		//jQuery('#salesperson').autocomplete({ source: available_salespeople });

		// Process autocomplete
		//jQuery('#process').autocomplete({ source: available_process });

		// Product type change
		jQuery('select.item_type').change(function() {
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

						// If jQuery(next_elem).hasClass('error'), remove it
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
			jQuery('#commission').val('$' + commission.toFixed(2));
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
			<thead>
<?php
	if(isset($rn)) {
		echo '<tbody>', PHP_EOL;
		$products = $rn->get_product_list();
		$subtotal = 0;
		foreach($products as $product) {
			echo '<tr><td style="width: 80px;"></td><td>', $product['name'], '</td><td style="width: 220px;">$', $product['price'], '</td></tr>', PHP_EOL;
			$subtotal += $product['price'];
		}
?>
				<tr><td colspan="3"><br></td></tr>
			</tbody>
		</table>
<?php
	} else {
?>
			<tbody>
				<tr>
					<td><select name="item_type[]" class="item_type"><option selected disabled>&ndash;</option><option value="lens">Lente</option><option value="frame">Armazón</option><option value="other">Otro</option></select></td>
					<td class="iname"><input type="text" name="item_name[]" class="item_name" placeholder="Producto" style="width: 80%;" readonly="readonly"><input type="hidden" name="item_id[]" class="item_id" value=""></td>
					<td class="iprice"><input type="number" name="item_price[]" class="product_price" placeholder="Precio" step="any" readonly="readonly"></td>
					<td><a class="rem_product" title="Borrar renglón" style="display: inline-block; cursor: pointer; margin: 60% 0 0 0;"><img src="resources/img/icon-delete.png" height="16" width="16" alt="Borrar renglón"></a></td>
				</tr>
			</tbody>
		</table>
		<button type="button" name="add_row" id="add_row" onclick="add_product_row();" class="green"><strong>+</strong> Agregar renglón</button>
<?php } ?>

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
						<!--<input type="text" name="salesperson" id="salesperson" placeholder="Vendedor" style="width: 80%;"<?php if(isset($rn)) { echo ' value="', $rn->salesperson, '" disabled="disabled"'; } ?>>-->
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
						<input type="text" id="commission" name="commission" placeholder="Comisión" style="width: 78%;" disabled="disabled" value="<?php echo (isset($rn) ? $rn->commission : ''); ?>">
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
				<tr><td colspan="4" style="text-align: right;">
					<?php
						if(isset($rn)) {
							echo '<input type="hidden" name="remission_note_id" id="remission_note_id" value="', $rn->id, '">', PHP_EOL;
							echo '<input type="submit" id="btn_nota" name="btn_nota" value="Actualizar nota">', PHP_EOL;
						} else {
							echo '<input type="submit" id="btn_nota" name="btn_nota" value="Guardar nota de remisión">', PHP_EOL;
						}
					?>
				</td></tr>
			</tbody>
		</table>
	</fieldset>
</form>
