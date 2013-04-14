<?php
	require_once('../../db_connect.php');
	$input = explode(' ', $_POST['search_string']);
	//array_walk($input, function(&$value, $key) { $value = '%' . $value . '%'; }); // Add % (SQL wildcards) before and after each item
	array_walk($input, create_function('&$v, $k', '$v="%".$v."%";')); // The above function, just that we're on old PHP here on Lenny
	//create_function('&$v,$k', '$v = $v . "mango";')

	/* These are some rather naïve permutations,
	 * but they should cover the vast majority of search cases while limiting results sufficiently...
	 */
	if(sizeof($input) >= 4) {
		$first_two = $input[0] . $input[1];
		$last_two  = $input[2] . $input[3];
		$stmt = $db->prepare('SELECT id, firstname, lastname, gender, add_date FROM patient WHERE (firstname LIKE :first_two AND lastname LIKE :last_two) OR (firstname LIKE :last_two AND lastname LIKE :first_two)');
		$stmt->bindParam(':first_two', $first_two);
		$stmt->bindParam(':last_two', $last_two);
	} elseif(sizeof($input) == 3) {
		$first_two = $input[0] . $input[1];
		$last_two = $input[1] . $input[2];
		$stmt = $db->prepare('SELECT id, firstname, lastname, gender, add_date FROM patient WHERE (firstname LIKE :first_two AND lastname LIKE :last_single) OR (firstname LIKE :first_single AND lastname LIKE :last_two) OR (firstname LIKE :last_two AND lastname LIKE :first_single) OR (firstname LIKE :last_single AND lastname LIKE :first_two)');
		$stmt->bindParam(':first_single', $input[0]);
		$stmt->bindParam(':last_single', $input[2]);
		$stmt->bindParam(':first_two', $first_two);
		$stmt->bindParam(':last_two', $last_two);
	} elseif(sizeof($input) == 2) {
		$both = $input[0] . $input[1];
		$stmt = $db->prepare('SELECT id, firstname, lastname, gender, add_date FROM patient WHERE (firstname LIKE :first AND lastname LIKE :last) OR (firstname LIKE :last AND lastname LIKE :first) OR firstname LIKE :both OR lastname LIKE :both');
		$stmt->bindParam(':first', $input[0]);
		$stmt->bindParam(':last', $input[1]);
		$stmt->bindParam(':both', $both);
	} else {
		$stmt = $db->prepare('SELECT id, firstname, lastname, gender, add_date FROM patient WHERE firstname LIKE :input OR lastname LIKE :input');
		$stmt->bindParam(':input', $input[0]);
	}

	if($stmt->execute()) {
		$result = $stmt->fetchAll();
		$num_results = sizeof($result);
		if(!$num_results) {
			die('<p><em>La búsqueda terminó sin resultados.</em></p>');
		}
		echo '<p><em>La búsqueda terminó con ', ($num_results == 1 ? '1 resultado' : $num_results . ' resultados'), '.</em></p>', PHP_EOL;
		echo '<table class="tablesorter clickable_tr">', PHP_EOL;
		echo '<thead><tr><th>Nombre</th><th>Apellido</th><th>Género</th><th>Fecha</th><td></td></tr></thead>', PHP_EOL, '<tbody>', PHP_EOL;
		foreach($result as $row) {
			echo '<tr onclick="document.location = \'?page=expedientes&patient_id=', $row['id'], '\'"><td>', $row['firstname'], '</td><td>', $row['lastname'], '</td><td>', ($row['gender'] == 0 ? 'Hombre' : 'Mujer'), '</td><td>', substr($row['add_date'], 0, 10), '</td><td><a href="?page=notas&patient_id=', $row['id'], '" title="Agregar nota de remisión"><img src="resources/img/icon-add.png" height="16" width="16" alt="Agregar nota"></a></tr>', PHP_EOL;
		}
		echo '</tbody>', PHP_EOL, '</table>', PHP_EOL;
	} else {
		echo '<p class="notification error">An error occurred while searching for matching patients!</p>';
	}
?>
