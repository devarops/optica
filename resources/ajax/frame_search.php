<?php
	// Turns out we won't be using this after all. Sigh.
	require_once('../../db_connect.php');

	$input = explode(',', $_POST['search_string']);
	//array_walk($input, function(&$value, $key) { $value = '%' . trim($value) . '%'; }); // Add % (SQL wildcards) before and after each item
	array_walk($input, create_function('&$v, $k', '$v="%".trim($v)."%";')); // The above function, just that we're on old PHP here on Lenny

	$price = explode('-', $_POST['search_pricerange']);

	$price_query = '';
	if(!empty($price[0])) {
		if(!empty($price[1])) {
			$price_query = ' AND price BETWEEN :min AND :max';
		} else {
			$price_query = ' AND price <= :min'; 
		}
	}

	// Permutations
	if(sizeof($input) >= 3) {
		$stmt = $db->prepare('SELECT * FROM frame WHERE
			(brand LIKE :first AND provider LIKE :second AND model LIKE :third) OR
			(brand LIKE :first AND provider LIKE :third AND model LIKE :second) OR
			(brand LIKE :second AND provider LIKE :first AND model LIKE :third) OR
			(brand LIKE :second AND provider LIKE :third AND model LIKE :first) OR
			(brand LIKE :third AND provider LIKE :first AND model LIKE :second) OR
			(brand LIKE :third AND provider LIKE :second AND model LIKE :first)'
			. $price_query);
		$stmt->bindParam(':first', $input[0]);
		$stmt->bindParam(':second', $input[1]);
		$stmt->bindParam(':third', $input[2]);
	} else if(sizeof($input) == 2) {
		$stmt = $db->prepare('SELECT * FROM frame WHERE
			(brand LIKE :first AND provider LIKE :second) OR
			(brand LIKE :first AND model LIKE :second) OR
			(brand LIKE :second AND provider LIKE :first) OR
			(brand LIKE :second AND model LIKE :first) OR
			(provider LIKE :first AND model LIKE :second) OR
			(provider LIKE :second AND model LIKE :first)'
			. $price_query);
		$stmt->bindParam(':first', $input[0]);
		$stmt->bindParam(':second', $input[1]);
	} else if(strlen($input[0]) > 2) {
		$stmt = $db->prepare('SELECT * FROM frame WHERE
			brand LIKE :first OR
			provider LIKE :first OR
			model LIKE :first'
			. $price_query);
		$stmt->bindParam(':first', $input[0]);
	} else { // Price only (strlen($input[0]) == 2 == '%%'
		$stmt = $db->prepare('SELECT * FROM frame WHERE ' . substr($price_query, 5));
	}
	

	if(!empty($price[0])) {
		if(!empty($price[1])) {
			$stmt->bindParam(':max', $price[1]);
		}
		$stmt->bindParam(':min', $price[0]);
	}

	if($stmt->execute()) {
		$result = $stmt->fetchAll();
		$num_results = sizeof($result);

		if(!$num_results) {
			die('<p><em>La búsqueda terminó sin resultados.</em></p>');
		}
		echo '<p><em>La búsqueda terminó con ', ($num_results == 1 ? '1 resultado' : $num_results . ' resultados'), '.</em></p>', PHP_EOL;
		echo '<table class="tablesorter clickable_tr">', PHP_EOL;
		echo '<thead><tr><th>Marca</th><th>Proveedor</th><th>Modelo</th><th>Forma</th><th>Color</th><th>Tamaño</th><th>Material</th><th>Precio</th></tr></thead>', PHP_EOL, '<tbody>', PHP_EOL;
		foreach($result as $row) {
			echo '<tr', (!empty($row['remission_note_id']) ? ' class="unavailable"' : ''), ' onclick="document.location = \'?page=armazones&id=', $row['id'], '\'"><td>', $row['brand'], '</td><td>', $row['provider'], '</td><td>', $row['model'], '</td><td>', $row['shape'], '</td><td>', $row['colour'], '</td><td>', $row['size'], '</td><td>', $row['material'], '</td><td>$ ', $row['price'], '</td></tr>', PHP_EOL;
		}
		echo '</tbody>', PHP_EOL, '</table>', PHP_EOL;

	} else {
		echo '<p class="notification error">Se occurió un error durante la ejecución de la búsqueda.</p>', PHP_EOL;
	}
?>
