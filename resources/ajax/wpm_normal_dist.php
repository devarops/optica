<?php
	/* Calculate the normal distribution of WPM for the given age */
	require_once('../../db_connect.php');
	require_once('../../classes/statistics.php');
	$statistics = new Statistics($db);

	$age = $_POST['age'];
	$wpm = intval($_POST['wpm']);

	$data = $statistics->getWpmData($age);

	if(!$data) {
		die('false');
	}

	$avg     = (float) $data['average'];
	$std_dev = (float) $data['std_dev'];

	$points = [
//		$avg - ($std_dev * 4),
		$avg - ($std_dev * 3),
		$avg - ($std_dev * 2),
		$avg - $std_dev,
		$avg,
		$avg + $std_dev,
		$avg + ($std_dev * 2),
		$avg + ($std_dev * 3),
//		$avg + ($std_dev * 4),
	];

	function f($x, $u, $o) {
		return (1 / ($o * sqrt(2 * M_PI))) * exp((-1 * pow(($x - $u), 2)) / (2 * pow($o, 2)));
	}

	$y = array();
	foreach($points as $x) {
		//echo 'f(' . $x . ') = ' . f($x, $avg, $std_dev) . PHP_EOL;
		$y[] = f($x, $avg, $std_dev);
	}

	echo json_encode(array('zipped' => array_map(null, $points, $y), 'patient' => [$wpm, f($wpm, $avg, $std_dev)], 'x' => $points, 'y' => $y));
?>
