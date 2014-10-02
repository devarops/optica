<?php
	require_once('../../db_connect.php');
	require_once('../../classes/employee.php');

	$employee = new Employee($db, $_POST['e_id']);

	if(!$employee->checkPassword($_POST['e_pw']) || !$employee->is_admin) {
		echo json_encode(['status' => 'error', 'msg' => 'No estas autorizado para realizar este acción']);
		return;
	}

	$msg_obs = sprintf('[%s] Armazón #%d marcado como defectuoso.', date('Y-m-d'), $_POST['f_id']);
	if($db->query('UPDATE frame SET remission_note_id=-1 WHERE id=' . $_POST['f_id'])) {
		$db->query("UPDATE remission_note SET observations = CONCAT(COALESCE(observations, ''), '" .
			sprintf('[%s] Armazón #%d marcado como defectuoso. (Nota original: #%s)\n', date('Y-m-d'), $_POST['f_id'], $_POST['r_id']) . "') WHERE id=-1");
		$db->query("UPDATE remission_note SET observations = CONCAT(COALESCE(observations, ''), '\n" . $msg_obs . "\n') WHERE id=" . $_POST['r_id']);

		echo json_encode(['status' => 'success', 'msg' => '<strong>Éxito:</strong> El armazón fue marcado como defectuoso.', 'msg_obs' => $msg_obs]);
		return;
	}

	echo json_encode(['status' => 'error', 'msg' => '<strong>Error:</strong> No se pudo actualizar el estado del armazón.']);
?>
