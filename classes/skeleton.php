<?php
	class Investigation {
		protected $db;

		public function __construct($db, $id=0) {
			$this->db = $db;

			if($id > 0) {
				$this->id = $id;
				$stmt = $this->db->prepare('SELECT * FROM  WHERE id = :id');
				$stmt->bindParam(':id', $this->id);
				if(!$stmt->execute()) {
					die('El  solicitado no existe.');
				}

				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				foreach($row as $key => $value) {
					$this->$key = $value;
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

		public funciton validate() {
			return true;
		}
	}
?>
