<?php
	// Remember to make uploads 0777 on new install!

	class Image {
		protected $db;
		protected $base_path = 'resources/uploads/images/';


		public function __construct($db, $id = 0) {
			$this->db = $db;
			$this->id = $id;

			if($id > 0) {
				$this->id = $id;
				$stmt = $this->db->prepare('SELECT * FROM image WHERE id = :id');
				$stmt->bindParam(':id', $this->id);
				$stmt->execute();

				if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					foreach($row as $key => $value) {
						$this->$key = $value;
					}
				} else {
					die('<div class="notification error"><strong>Error:</strong> La imagen solicitada no existe.</div>');
				}
			}

		}


		public function upload($patient_id, $file, $description, $tag_ids) {
			$target_path = $this->base_path . date('Y/m/') . $patient_id . '-' . basename($file['name']);

			if(!move_uploaded_file($file['tmp_name'], $target_path)) {
				return false;
			}

			$this->patient_id  = $patient_id;
			$this->path        = $target_path;
			$this->description = $description;

			$this->save();
			$this->setTags($tag_ids);

			return true;
		}


		public function update($description, $tag_ids) {

		}


		public function setTags($tag_ids) {

		}


		public function getTags() {
			return ['Some tag', 'Yet another tag'];
		}


		public function save() {
			if($this->id == 0) {
				$stmt = $this->db->prepare("INSERT INTO image(patient_id, path, description) VALUES(:patient_id, :path, :description)");
			} else {
				$stmt = $this->db->prepare("UPDATE image SET patient_id=:patient_id, path=:path, description=:description WHERE id=:id");
				$stmt->bindParam(':id', $this->id);
			}

			$stmt->bindParam(':path', $this->path);
			$stmt->bindParam(':patient_id', $this->patient_id);
			$stmt->bindParam(':description', $this->description);


			if($stmt->execute()) {
				if($this->id == 0) {
					$this->id = $this->db->lastInsertId();
				}
				return $this->id;
			} else {
				$error = $stmt->errorInfo();
				die('<div class="notification error"><strong>Error:</strong> ' . $error[2] . '</div>');
			}

		}
	}
?>
