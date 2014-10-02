<?php
	if(isset($_GET['id'])) {
		$frame = new Frame($db, $_GET['id']);
	}

	// Get distinct values for our autocomplete fields
	$fields = array('brand', 'provider', 'model', 'shape', 'colour', 'material', 'size');
	$ac_values = array();

	foreach($fields as $field) {
		$result = $db->query('SELECT DISTINCT ' . $field . ' FROM frame ORDER BY ' . $field);
		$ac_values[$field] = '';
		while($row = $result->fetch(PDO::FETCH_ASSOC)) {
			if(!empty($row[$field])) {
				$ac_values[$field] .= "'" . addslashes($row[$field]) . "', ";
			}
		}
	}
?>

<script>
	jQuery(function() {
		<?php
			foreach($fields as $field) {
				echo 'var available_', $field, ' = [', substr($ac_values[$field], 0, -2), '];', PHP_EOL;
				echo 'jQuery(\'#', $field, '\').autocomplete({ source: available_', $field, '});', PHP_EOL;
			}
		?>
	});
</script>

<h1><img src="resources/img/icon-glasses.png" height="48" width="48" alt=""> Armazones</h1>

<?php
	$result = $db->query('SELECT count(id) AS num FROM frame WHERE remission_note_id IS NULL');
	$row = $result->fetch(PDO::FETCH_ASSOC);
	echo '<p class="small">Se cuenta actualmente con ', number_format($row['num']), ' armazones disponibles.</p>', PHP_EOL;
?>

<label>Buscar armazon</label><br>
<form action="" method="get"> 
	<input type="hidden" name="page" value="armazones">
	<input type="search" name="id" id="armazon_id" tabindex="1" style="padding: 10px; width: 200px;" results="2" placeholder="ID del armazón">
	<input type="submit" id="btn_submit" value="Buscar armazón">
</form>

<div id="frame_search_result" class="search result"></div>

<form action="" method="post">
	<fieldset id="armazon">
		<legend><?php echo (isset($frame) ? $frame->model . ' (' . $frame->provider . ') #' . $frame->id: 'Añadir armazon'); ?></legend>
		<table class="noeffects">
			<tbody>
				<tr>
					<td><label for="brand">Marca</label><br><input type="text" name="brand" id="brand" value="<?php echo (isset($frame) ? $frame->brand : ''); ?>"></td>
					<td><label for="provider">Proveedor</label><br><input type="text" name="provider" id="provider" value="<?php echo (isset($frame) ? $frame->provider : ''); ?>"></td>
				</tr>
				<tr>
					<td><label for="model">Modelo</label><br><input type="text" name="model" id="model" value="<?php echo (isset($frame) ? $frame->model : ''); ?>"></td>
					<td><label for="shape">Forma</label><br><input type="text" name="shape" id="shape" value="<?php echo (isset($frame) ? $frame->shape : ''); ?>"></td>
				</tr>
				<tr>
					<td><label for="colour">Color</label><br><input type="text" name="colour" id="colour" value="<?php echo (isset($frame) ? $frame->colour : ''); ?>"></td>
					<td><label for="size">Tamaño</label><br><input type="text" name="size" id="size" value="<?php echo (isset($frame) ? $frame->size : ''); ?>"></td>
				</tr>
				<tr>
					<td><label for="material">Material</label><br><input type="text" name="material" id="material" value="<?php echo (isset($frame) ? $frame->material : ''); ?>"></td>
					<td><label for="price">Precio</label><br><input type="number" name="price" id="price" min="0" max="5000" value="<?php echo (isset($frame) ? $frame->price : ''); ?>"></td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<?php if(!isset($frame)) { ?>
				<tr>
					<td></td><td><input type="submit" name="btn_frame" id="btn_frame" value="Almacenar armazon"></td>
				</tr>
				<?php } else if(!empty($frame->remission_note_id)) { ?>
				<tr>
					<td colspan="2">Este armazón fue vendido. Para mayor información, por favor vea la <a href="?page=notas&id=<?php echo $frame->remission_note_id, '">nota #', $frame->remission_note_id, '</a>', PHP_EOL; ?>.<br><br></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</fieldset>
</form>
