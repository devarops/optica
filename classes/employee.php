<?php
	class Employee {
		protected $db;

		public function __construct($db, $id = 0) {
			$this->db = $db;

			if($id > 0) {
				$this->id = $id;
				$stmt = $this->db->prepare('SELECT * FROM user WHERE id = :id');
				$stmt->bindParam(':id', $this->id);
				$stmt->execute();

				if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					foreach($row as $key => $value) {
						$this->$key = $value;
					}
				} else {
					die('<div class="notification error">El _ solicitado no existe.</div>');
				}
			} else {
				$this->id = 0;
			}
		}

		public function save() {
			$this->validate();

			if($this->id == 0) {
				$stmt = $this->db->prepare("INSERT INTO investigation");
			} else {
				$stmt = $this->db->prepare("UPDATE investigation SET");
				$stmt->bindParam(':id', $this->id);
			}

			$stmt->bindParam(':title', $this->title);
			// ...

			if($stmt->execute()) {
				if($this->id == 0) {
					$tis->id = $this->db->lastInsertId();
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

		public static function getEmployees($db) {
			$result    = $db->query("SELECT id, username, name FROM user");
			$employees = array();

			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$employees[] = $row;
			}

			return $employees;
		}
	}
?>
