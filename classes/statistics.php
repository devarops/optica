<?php
	/* Helper class for providing statistical data and calculations */

	class Statistics {
		protected $db;

		public function __construct($db) {
			$this->db = $db;
		}

		public function getWpmData($age = null) {
			$query = "SELECT YEAR(r.add_date) - p.birthdate AS age, AVG(r.lectura) AS avg, STDDEV_SAMP(r.lectura) AS std, COUNT(r.lectura) AS sample_size FROM record AS r, patient AS p WHERE r.patient_id = p.id AND r.lectura > 0 GROUP BY age HAVING sample_size > 1";
			if($age && is_int($age)) {
				$query .= " AND age = " . $age;
			}

			$result = $this->db->query($query);
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

		public function getBasicStats() {
			$output  = [];
			$queries = [
				'tot_patients'            => 'SELECT COUNT(id) AS tot_patients FROM patient',
				'tot_records'             => 'SELECT COUNT(id) AS tot_records  FROM record',
				'avg_patients_per_day'    => 'SELECT AVG(data.num_patients) AS avg_patients_per_day FROM (SELECT COUNT(id) AS num_patients FROM patient GROUP BY DATE(add_date)) AS data',
				'avg_records_per_day'     => 'SELECT AVG(data.num_records) AS avg_records_per_day FROM (SELECT COUNT(id) AS num_records FROM record GROUP BY DATE(add_date)) AS data',
				'avg_records_per_patient' => 'SELECT AVG(data.num_records) AS avg_records_per_patient FROM (SELECT COUNT(r.id) AS num_records FROM patient AS p, record AS r WHERE r.patient_id = p.id GROUP BY p.id) AS data',
			];

			foreach($queries as $key => $query) {
				$result       = $this->db->query($query);
				$row          = $result->fetch(PDO::FETCH_ASSOC);
				$output[$key] = $row[$key];
			}

			return $output;
		}
	}
?>
