<?php
	class Frame {
		protected $db;

		public function __construct($db, $id=0) {
			$this->db = $db;

			if($id > 0) {
				$this->id = $id;
				$stmt = $this->db->prepare('SELECT * FROM frame WHERE id = :id');
				$stmt->bindParam(':id', $this->id);
				$stmt->execute();
				
				if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					foreach($row as $key => $value) {
						$this->$key = $value;
					}
				} else {
					die('El armazón solicitado no existe.');
				}

			} else {
				$this->id = 0;
				$this->add_date = date('Y-m-d H:i:s');
			}
		}

		public function save() {
			$this->validate();

			if($this->id == 0) {
				$stmt = $this->db->prepare("INSERT INTO frame (add_date, brand, provider, model, shape, colour, size, material, price) VALUES (:add_date, :brand, :provider, :model, :shape, :colour, :size, :material, :price)");
				$stmt->bindParam(':add_date', $this->add_date);
			} else {
				$stmt = $this->db->prepare("UPDATE frame SET brand=:brand, provider=:provider, model=:model, shape=:shape, colour=:colour, size=:size, material=:material, price=:price WHERE id=:id");
				$stmt->bindParam(':id', $this->id);
			}

			$stmt->bindParam(':brand', $this->brand);
			$stmt->bindParam(':provider', $this->provider);
			$stmt->bindParam(':model', $this->model);
			$stmt->bindParam(':shape', $this->shape);
			$stmt->bindParam(':colour', $this->colour);
			$stmt->bindParam(':size', $this->size);
			$stmt->bindParam(':material', $this->material);
			$stmt->bindParam(':price', $this->price);

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
	}
?>
