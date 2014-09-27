<?php
	class Investigation {
		protected $db;

		public function __construct($db, $id=0) {
			$this->db = $db;

			if($id > 0) {
				$this->id = $id;
				$result = $this->db->query('SELECT * FROM investigation WHERE id=' . $this->id);
				if(!$result->rowCount()) {
					die('<div class="notification error">La investigación solicitada no existe.</div>');
				}
				$row = $result->fetch(PDO::FETCH_ASSOC);
				$this->id = $row['id'];
				$this->title = $row['title'];
				$this->description = $row['description'];
				$this->add_date = $row['add_date'];
			} else {
				$this->id = 0;
			}
		}

		public function save() {
			$this->validate();

			if($this->id == 0) {
				$stmt = $this->db->prepare("INSERT INTO investigation (title, description) VALUES (:title, :description)");
			} else {
				$stmt = $this->db->prepare("UPDATE investigation SET title=:title, description=:description WHERE id=:id");
				$stmt->bindParam(':id', $this->id);
			}

			$stmt->bindParam(':title', $this->title);
			$stmt->bindParam(':description', $this->description);

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

		public function delete() {
			if($this->db->query("DELETE FROM investigation WHERE id = " . $this->id)) {
				return array('La investigación <em>' . $this->title . '</em> fue borrada exitosamente.', 'success');
			}
			return array('No se podría borrar la investigación.', 'error');
		}

		public function validate() {
			return true;
		}

		public function get_participants($id_only = False) {
			$participants = array();
			$result = $this->db->query("SELECT p.id, p.firstname, p.lastname, p.gender, p.birthdate FROM patient AS p, ct_patient_investigation AS ct WHERE p.id=ct.patient_id AND ct.investigation_id=" . $this->id);
			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				if($id_only) {
					$participants[] = $row['id'];
				} else {
					$participants[] = $row;
				}
			}
			return $participants;
		}

		public static function get_all_investigations($db) {
			$investigations = array();
			$result = $db->query("SELECT id FROM investigation ORDER BY add_date DESC");
			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$investigations[$row['id']] = new Investigation($db, $row['id']);
			}
			return $investigations;
		}
	}
?>
