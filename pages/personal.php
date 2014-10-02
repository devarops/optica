<?php

if(isset($_POST['btn_save_user'])) {
	$e = new Employee($db, $_POST['user_id']);
	$e->username  = $_POST['username'];
	$e->name      = $_POST['fullname'];
	$e->is_active = ($_POST['active'] ? 1 : 0);
	if(!empty($_POST['password'])) {
		$e->new_password = $_POST['password'];
	}
	$result = $e->save();
	if(is_array($result)) {
		echo '<p class="notification error"><strong>Error:</strong> ', $result[2], '</p>', PHP_EOL;
	} else {
		echo '<p class="notification success"><strong>Éxito:</strong> Los cambios han sido guardados!</p>', PHP_EOL;
	}
}

?>


<h1><img src="resources/img/icon-staff.png" height="48" width="48" alt=""> Personal</h1>

<h2>Empleados</h2>

<table style="width: 50%;">
	<thead>
		<tr><th>Nombre completo</th><th>Nombre de usuario</th><th>Creado</th><th>Activo</th><td></td></tr>
	</thead><tbody>
<?php
	$result = $db->query('SELECT * FROM user ORDER BY name');
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		if(isset($_GET['edit_user']) && $row['id'] == $_GET['edit_user']) {
			$user = $row;
		}
		echo '<tr><td>', $row['name'], '</td><td>', $row['username'], '</td><td>', substr($row['created'], 0, 10), '</td><td>', ($row['is_active'] ? 'Sí' : 'No'), '</td><td>',
			'<a href="?page=personal&edit_user=', $row['id'],'"><img src="resources/img/icon-edit.png" height="16 width="16" alt="Modificar cuenta"></a></td></tr>', PHP_EOL;
			//'<a href="?page=personal&rem_user=', $row['id'],'"><img src="resources/img/icon-delete.png" height="16 width="16" alt="Deshabilitar cuenta"></a></td></tr>', PHP_EOL;
	}
?>
	</tbody>
</table>

<fieldset style="width: 50%;">
	<legend>Crear/actualizar cuenta</legend>
	<form action="?page=personal" method="post">
		<table class="noeffects">
			<tr>
				<td><label for="fullname">Nombre completo</label><br><input type="text" name="fullname" id="fullname" value="<?php if(isset($user)) { echo $user['name']; } ?>"></td>
				<td><label for="username">Nombre de usuario</label><br><input type="text" name="username" id="username" value="<?php if(isset($user)) { echo $user['username']; } ?>"></td>
			</tr><tr>
				<td><label for="password">Contraseña</label><br><input type="password" name="password" id="password"></td>
				<td><br>
					<input type="checkbox" name="active" id="active" checked>
					<label for="active">Activo</label>
					<input type="submit" name="btn_save_user" value="Guardar">
					<input type="hidden" name="user_id" value="<?php echo (isset($user) ? $user['id'] : '0'); ?>">
				</td>
			</tr>
		</table>
	</form>
</fieldset>





<h2>Ventas y comisiones</h2>
<!--
<form id="sales_history" method="post">
	<table class="noeffects" style="width: auto;">
		<tbody>
			<tr><td>
				<label for="username">Nombre del vendedor</label><br>
				<select name="username" id="username" placeholder="Su nombre">
					<?php
						// Get real list here...
						$users = array(
							array('username' => 'beatriz', 'name' => 'Beatríz Mayoral')
						);

						foreach($users as $user) {
							printf('<option value="%s">%s</option>', $user['username'], $user['name']);
						}
					?>
				</select>
			</td><td>
				<label for="password">Contraseña</label><br>
				<input type="password" name="password" id="password" placeholder="password">
			</td><td>
				<br><input type="submit" name="btn_submit" value="Mostrar historial">
			</td></tr>
		</tbody>
	</table>
</form>
-->

<fieldset id="unclaimed_comissions">
	<legend>Comisiones no reclamados</legend>

	<table>
		<thead>
			<tr><th>Fecha</th><th>Paciente</th><th>Comisión</th><th>Reclamar</th></tr>
		</thead>
		<tbody>
			<tr><td>2013-09-11</td><td>Foo McBar</td><td>$45.00</td><td><input type="checkbox" value="sale_id"></td></tr>
			<tr><td>2013-09-11</td><td>Foo McBar</td><td>$45.00</td><td><input type="checkbox" value="sale_id"></td></tr>
			<tr><td>2013-09-11</td><td>Foo McBar</td><td>$45.00</td><td><input type="checkbox" value="sale_id"></td></tr>
		</tbody>
	</table>

	<div style="float: left; margin: 20px 0 0 0; font-weight: bold;">Cantidad total disponible: $135.00</div>
	<input type="submit" name="btn_submit" value="Marcar $135.00 como reclamado" style="float: right; margin: 10px 0 0 0;">

</fieldset>
