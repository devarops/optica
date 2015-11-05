<?php
	// Alter username and password, then save this file as db_connect.php
	$db_name = 'optica';
	$db_host = 'localhost';
	$db_user = 'optica';
	$db_pass = 'Horus';

	try {
		$db = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8', $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true, PDO::ERRMODE_EXCEPTION => true));
	} catch(PDOException $e) {
		header('HTTP/1.0 500 Internal Server Error');
		die('[ERROR] ' . $e->getMessage());
	}
?>
