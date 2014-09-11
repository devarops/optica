<h1><img src="resources/img/icon-staff.png" height="48" width="48" alt=""> Personal</h1>

<p>Historial de ventas, admin de cuentas, cambiar contrasena.</p>
<p>Popup soliciting password when taking action.</p>


<h2>Historial de ventas</h2>
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
<!--
			</td><td>
				<label for="password">Contraseña</label><br>
				<input type="password" name="password" id="password" placeholder="password">
-->
			</td><td>
				<br><input type="submit" name="btn_submit" value="Mostrar historial">
			</td></tr>
		</tbody>
	</table>
</form>

<fieldset id="unclaimed_comissions">
	<legend>Comisiones no reclamados</legend>

	<table>
		<tbody>
			<tr><th>Fecha</th><th>Paciente</th><th>Comisión</th><th>Reclamar</th></tr>
			<tr><td>2013-09-11</td><td>Foo McBar</td><td>$45.00</td><td><input type="checkbox" value="sale_id"></td></tr>
			<tr><td>2013-09-11</td><td>Foo McBar</td><td>$45.00</td><td><input type="checkbox" value="sale_id"></td></tr>
			<tr><td>2013-09-11</td><td>Foo McBar</td><td>$45.00</td><td><input type="checkbox" value="sale_id"></td></tr>
		</tbody>
	</table>

	<div style="float: left; margin: 20px 0 0 0; font-weight: bold;">Cantidad total disponible: $135.00</div>
	<input type="submit" name="btn_submit" value="Reclamar $135.00 ahora" style="float: right; margin: 10px 0 0 0;">

</fieldset>
