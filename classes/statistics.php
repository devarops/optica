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


		public function getTonometryHistogram($bars = 10) {

			// Find the smallest (nonzero) and largest tonometry value, from either OD or OI; use these values to set the step value based on $bars
			$result  = $this->db->query('SELECT LEAST(t.min_od, t.min_oi) AS min, GREATEST(t.max_od, t.max_oi) AS max FROM (SELECT MIN(NULLIF(tonometria_od, 0)) AS min_od, MIN(NULLIF(tonometria_oi, 0)) AS min_oi, MAX(tonometria_od) AS max_od, MAX(tonometria_oi) AS max_oi FROM record) AS t');
			$row     = $result->fetch(PDO::FETCH_ASSOC);
			$step    = ($row['max'] - $row['min']) / $bars;

			$output  = [
				'od'    => array_fill(0, $bars, 0),
				'oi'    => array_fill(0, $bars, 0),
				'min'   => $row['min'],
				'max'   => $row['max'],
				'ticks' => range($row['min'], $row['max'], $step),
			];

			$queries = [
				'od' => 'SELECT tonometria_od AS t_od, COUNT(tonometria_od) AS num FROM record WHERE tonometria_od > 0 GROUP BY ROUND(tonometria_od / ' . $step . ')',
				'oi' => 'SELECT tonometria_oi AS t_oi, COUNT(tonometria_oi) AS num FROM record WHERE tonometria_oi > 0 GROUP BY ROUND(tonometria_oi / ' . $step . ')'
			];

			foreach($queries as $key => $query) {
				$result = $this->db->query($query);
				while($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$idx = round(($row['t_' . $key] - $output['min']) / $step);
					$output[$key][$idx] = $row['num'];
				}
			}

			return $output;
		}


		public function getTonometryStats($pop_ids) {
			// Should we use population or sample stddev? (STD or STDDEV_SAMP)
			$qt       = '(SELECT %s AS tonometria FROM record WHERE %s > 0 AND patient_id IN (%s))';
			$pl       = join(',', $pop_ids);
			$subquery = sprintf($qt, 'tonometria_od', 'tonometria_od', $pl) . ' UNION ALL ' . sprintf($qt, 'tonometria_oi', 'tonometria_oi', $pl);
			$row      = $this->db->query('SELECT AVG(tonometria) AS avg, STD(tonometria) AS stdev FROM (' . $subquery . ') AS t')->fetch(PDO::FETCH_ASSOC);

			return ['avg' => $row['avg'], 'stdev' => $row['stdev']];
		}
	}
?>
