<?php
	require_once('record.php');

	class Patient {
		protected $db;

		public function __construct($db, $id=0) {
			$this->db = $db;

			if($id > 0) {
				$this->id = $id;
				$stmt = $this->db->prepare('SELECT * FROM patient WHERE id = :id');
				$stmt->bindParam(':id', $this->id);
				if(!$stmt->execute()) {
					die('El paciente solicitado no existe.');
				}

				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				foreach($row as $key => $value) {
					$this->$key = $value;
				}

				$this->investigations = array();

				// Fill list of investigation IDs
				$result = $this->db->query('SELECT investigation_id FROM ct_patient_investigation WHERE patient_id = ' . $this->id);
				while($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$this->investigations[] = $row['investigation_id'];
				}
			} else {
				$this->id = 0;
			}
		}

		public function get_age() {
			if(isset($this->birthdate) && $this->birthdate > 0) {
				return date('Y') - $this->birthdate;
			}
			return False;
		}

		public function save() {
			$this->validate();

			if($this->id == 0) {
				$stmt = $this->db->prepare("INSERT INTO patient(firstname, lastname, gender, address, phone, birthdate, is_flagged, comments) VALUES(:firstname, :lastname, :gender, :address, :phone, :birthdate, :is_flagged, :comments)");
			} else {
				$stmt = $this->db->prepare("UPDATE patient SET firstname=:firstname, lastname=:lastname, gender=:gender, address=:address, phone=:phone, birthdate=:birthdate, is_flagged=:is_flagged, comments=:comments WHERE id=:id");
				$stmt->bindParam(':id', $this->id);
			}

			if(isset($this->birthdate) && $this->birthdate > 0) {
				$this->birthdate = date('Y') - $this->birthdate; // Somewhat confusing due to the name change. Really birthYear = currentYear - ageInInt
			} else {
				$this->birthdate = Null;
			}

			$stmt->bindParam(':firstname', $this->firstname);
			$stmt->bindParam(':lastname', $this->lastname);
			$stmt->bindParam(':gender', $this->gender);
			$stmt->bindParam(':address', $this->address);
			$stmt->bindParam(':phone', $this->phone);
			$stmt->bindParam(':birthdate', $this->birthdate);
			$stmt->bindParam(':is_flagged', $this->is_flagged);
			$stmt->bindParam(':comments', $this->comments);

			if($stmt->execute()) {
				if($this->id == 0) {
					$this->id = $this->db->lastInsertId();
				}
				return $this->id;
			} else {
				$error = $stmt->errorInfo();
				die('[ERROR] ' . $error[0]);
			}
		}

		public function validate() {
			return true;
		}

		public function get_full_name($mark_flag = false) {
			$name = $this->firstname . ' ' . $this->lastname;
			if($mark_flag && $this->is_flagged) {
				$name = '<span class="flagged">' . $name . '</span>';
			}
			return $name;
		}

		// Checks whether or not the patient is part of the given investigation
		public function is_participant($investigation_id) {
			return in_array($investigation_id, $this->investigations);
		}

		public function get_latest_record() {
			$result = $this->db->query('SELECT id FROM record WHERE patient_id = ' . $this->id . ' ORDER BY add_date DESC LIMIT 1');
			if(!$result->rowCount()) {
				return 0;
			}
			$row = $result->fetch(PDO::FETCH_ASSOC);
			return new Record($this->db, $row['id']);
		}

		public function get_record($record_id) {
			$record = new Record($this->db, $record_id);
			if(!$record || $record->patient_id != $this->id) {
				return false;
			}
			return $record;
		}

		public function get_record_list() {
			$result = $this->db->query('SELECT id FROM record WHERE patient_id = ' . $this->id . ' ORDER BY add_date DESC');
			if(!$result->rowCount()) {
				return false;
			}
			$records = array();
			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$records[] = new Record($this->db, $row['id']);
			}
			return $records;
		}

		public function get_remission_list() {
			$result = $this->db->query('SELECT id, add_date, total, salesperson, process, observations FROM remission_note WHERE patient_id = ' . $this->id . ' ORDER BY add_date DESC');
			if(!$result->rowCount()) {
				return false;
			}
			$records = array();
			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$records[] = $row;
			}
			return $records;
		}
	}
?>
