<?php
	class RemissionNote {
		protected $db;

		public function __construct($db, $id = 0) {
			$this->db = $db;
			$this->misc_products_prices = '';
			$this->commission_rate = 0.03;

			if($id !== 0) {
				$this->id = $id;
				$stmt = $this->db->prepare('SELECT * FROM remission_note WHERE id = :id');
				$stmt->bindParam(':id', $this->id);
				$stmt->execute();
				if(!$stmt->rowCount()) {
					die('<div class="notification error"><p>La nota de remisión solicitada no existe.</p></div>');
				}

				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				foreach($row as $key => $value) {
					$this->$key = $value;
				}
			} else {
				$this->id = 0;
			}
		}

		public function handle_products($products) {
			// Products in nested arrays, array( 'type' => array(product1, product2, ...), ...)
			$this->misc_products_prices = ''; // Reset...
			foreach($products as $key => $category) {
				if($key != 'frame') {
					foreach($products[$key] as $item) {
						$this->misc_products_prices .= $item['name'] . '@' . $item['price'] . '|'; // No lens validity check atm
					}
				}
			}
			$this->misc_products_prices = substr($this->misc_products_prices, 0, -1);
			if(isset($products['frame'])) {
				$this->frames = $products['frame'];
			}
		}

		public function get_product_list() {
			$products = array();

			//$result = $this->db->query('SELECT CONCAT(brand, \' \', model, \' \', shape, \' \', colour) AS description, price FROM frame WHERE remission_note_id = ' . $this->id);
			$result = $this->db->query('SELECT CONCAT(\'Armazón #\', id, \' (\', brand, \')\') AS description, price, id FROM frame WHERE remission_note_id = ' . $this->id);
			while($row = $result->fetch(PDO::FETCH_ASSOC)) {
				$products[] = array('name' => $row['description'], 'price' => $row['price'], 'id' => $row['id']);
			}

			if(!empty($this->misc_products_prices)) {
				$misc = explode('|', $this->misc_products_prices);
				foreach($misc as $item) {
					$data = explode('@', $item);
					$products[] = array('name' => $data[0], 'price' => $data[1]);
				}
			}

			return $products;
		}

		public function save($admin_update = False) {
			// Turned AWFUL with the whole "admin update" thing...
			$this->validate();

			if($this->id == 0) {
				$stmt = $this->db->prepare("INSERT INTO remission_note (add_date, patient_id, down_payment, process, salesperson_id, observations, total, misc_products_prices, commission) VALUES (:add_date, :patient_id, :down_payment, :process, :salesperson_id, :observations, :total, :misc_products_prices, :commission)");
				$stmt->bindParam(':add_date', $this->add_date);
				$stmt->bindParam(':patient_id', $this->patient_id);
				$stmt->bindParam(':salesperson_id', $this->salesperson_id);
				// Important note, total is actually the REAL total MINUS the down payment... Bad
				// In a future update of the remission note system (including proper connection tables for lenses and other products), this should be attended
				$commission = ($this->total + $this->down_payment) * $this->commission_rate; // Can't calculate this within bindParam for some reason
				$stmt->bindParam(':commission', $commission);
			} else {

				if($admin_update) {
					$stmt = $this->db->prepare("UPDATE remission_note SET process = :process, down_payment = :down_payment, observations = :observations, total = :total, commission = :commission, misc_products_prices = :misc_products_prices WHERE id = :id");
					$stmt->bindParam(':commission', $this->commission);
				} else {
					$stmt = $this->db->prepare("UPDATE remission_note SET process = :process WHERE id = :id");
				}

				$stmt->bindParam(':id', $this->id);
			}

			$this->process = strtoupper($this->process);
			$stmt->bindParam(':process', $this->process);

			if($this->id == 0 || $admin_update) {
				$stmt->bindParam(':down_payment', $this->down_payment);
				$stmt->bindParam(':observations', $this->observations);
				$stmt->bindParam(':total', $this->total);
				$stmt->bindParam(':misc_products_prices', $this->misc_products_prices);
			}
			
			if($stmt->execute()) {
				if($this->id == 0) {
					$this->id = $this->db->lastInsertId();
				}

				// Handle frame insertions
				if(isset($this->frames)) {
					foreach($this->frames as $frame) {
						$this->db->query('UPDATE frame SET remission_note_id = ' . $this->id . ', price = ' . $frame['price'] . ' WHERE id = ' . $frame['id']);
					}
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
