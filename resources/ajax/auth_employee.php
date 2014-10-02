<?php
	require_once('../../db_connect.php');
	require_once('../../classes/employee.php');

	$output   = ['is_authed' => False, 'is_admin' => False];
	$employee = new Employee($db, $_POST['id']);

	if($employee->checkPassword($_POST['pw'])) {
		$output['is_authed'] = True;
		$output['is_admin']  = (bool) $employee->is_admin;
	}

	echo json_encode($output);
?>
