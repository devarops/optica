<?php
	/* Helper class for providing statistical data and calculations */

	class Statistics {
		protected $db;

		public function __construct($db) {
			$this->db = $db;
		}

		public function getWpmData($age = null) {
			$result = $this->db->query("SELECT YEAR(r.add_date) - p.birthdate AS age, AVG(r.lectura) AS avg, STD(r.lectura) AS std, COUNT(r.lectura) AS sample_size FROM record AS r, patient AS p WHERE r.patient_id = p.id AND r.lectura > 0 group by age");

			$output = array();

			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$output[$row['age']] = array(
					'average'     => $row['avg'],
					'std_dev'     => $row['std'],
					'sample_size' => $row['sample_size']
				);
			}

			if($age) {
				// Error check
				return $output[$age];
			}

			return $output;
		}
	}
?>
