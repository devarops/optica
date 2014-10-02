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
				$stmt = $this->db->prepare("INSERT INTO user (username, password, name, created, is_active) VALUES (:username, :password, :name, :created, :is_active)");
				$stmt->bindParam(':created', date('Y-m-d H:i:s'));
			} else {
				$stmt = $this->db->prepare("UPDATE user SET username = :username, password = :password, name = :name, is_active = :is_active WHERE id = :id");
				$stmt->bindParam(':id', $this->id);
			}

			$stmt->bindParam(':name',      $this->name);
			$stmt->bindParam(':username',  $this->username);
			$stmt->bindParam(':is_active', $this->is_active);

			$pw = (isset($this->new_password) ? md5($this->new_password) : $this->password);
			$stmt->bindParam(':password', $pw);

			if($stmt->execute()) {
				if($this->id == 0) {
					$this->id = $this->db->lastInsertId();
				}
				return $this->id;
			} else {
				return $stmt->errorInfo();
				//die('[ERROR] ' . $error[0]);
			}
		}

		public function validate() {
			return true;
		}

		public function checkPassword($candidate) {
			$result = $this->db->query("SELECT id FROM user WHERE password=md5('" . $candidate . "') AND id = " . $this->id);
			if($result->fetchColumn() > 0) {
				return True;
			}
			return False;
		}

		public static function getEmployees($db) {
			$result    = $db->query("SELECT id, username, name FROM user WHERE is_active = 1");
			$employees = array();

			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$employees[] = $row;
			}

			return $employees;
		}
	}
?>
