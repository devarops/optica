<?php
	/* Helper class for providing statistical data and calculations */

	class Statistics {
		protected $db;

		public function __construct($db) {
			$this->db = $db;
		}

		public function getWpmData($age = null) {
			$result = $this->db->query("SELECT YEAR(r.add_date) - p.birthdate AS age, AVG(r.lectura) AS avg, STDDEV_SAMP(r.lectura) AS std, COUNT(r.lectura) AS sample_size FROM record AS r, patient AS p WHERE r.patient_id = p.id AND r.lectura > 0 GROUP BY age HAVING sample_size > 1");

			$output = array();

			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$std_err = $row['std'] / sqrt($row['sample_size']);

				$output[$row['age']] = array(
					'average'     => $row['avg'],
					'std_dev'     => $row['std'],
					'sample_size' => $row['sample_size'],
					'std_err'     => $std_err,
					'conf_int'    => array('lower' => $row['avg'] - ($std_err * 1.96), 'upper' => $row['avg'] + ($std_err * 1.96))
				);
			}

			if($age) {
				// Error check
				return $output[$age];
			}

			return $output;
		}

		// WPM within 2 STDDEV?
	}
?>
