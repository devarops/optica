<?php
	require_once('../../db_connect.php'); // For $db_ variables
	ob_start();

	$fn  = 'optica_' . date('Y-m-d') . '.sql';
	$cmd = '/usr/bin/mysqldump --add-drop-table --host=' . $db_host . ' --user=' . $db_user .
		' --password=' . $db_pwd . ' ' . $db_name;// . ' > ' . $fn;

	system($cmd);

	$dump = ob_get_contents();
	ob_end_clean();

	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=' . $fn);
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($fn));
	flush();
	echo $dump;
	exit;
?>
