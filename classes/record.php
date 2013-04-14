<?php
class Record {
	protected $db;

	public function __construct($db, $id = 0) {
		$this->db = $db;

		if($id > 0) {
			$stmt = $db->prepare('SELECT * FROM record WHERE id = :id');
			$stmt->bindParam(':id', $id);
			$stmt->execute();

			if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				foreach($row as $key => $value) {
					$this->$key = $value;
				}
			} else {
				die('<div class="notification error">El expediente solicitado no existe.</div>');
			}

			foreach(array('m_od', 'm_oi') as $item) {
				foreach(array('_sphere', '_cylinder') as $subitem) {
					$key = $item . $subitem;
					if(!empty($this->$key)) {
						if($this->$key > 0) {
							$this->$key = '+' . str_pad($this->$key, 3, '0', STR_PAD_LEFT);
						} else {
							$this->$key = '-' . str_pad(substr($this->$key, 1), 3, '0', STR_PAD_LEFT);
						}
					}
				}
				foreach(array('_axis', '_addition') as $subitem) {
					$key = $item . $subitem;
					if(!empty($this->$key)) {
						$this->$key = str_pad($this->$key, 3, '0', STR_PAD_LEFT);
					}
				}
			}

			$this->m_od = $this->m_od_sphere . '(' . $this->m_od_cylinder . '%' . $this->m_od_axis . '[' . $this->m_od_addition;
			$this->m_oi = $this->m_oi_sphere . '(' . $this->m_oi_cylinder . '%' . $this->m_oi_axis . '[' . $this->m_oi_addition;
		} else {
			$this->id = 0;
		}
	}

	// Splits the string of format ____(____%____[____ into an array of numeric values
	// 0: esfera, 1: cilindro, 2: eje, 3: adición
	public function ecea_split($string) {
		$orig_string = $string;
		$output = array(NULL, NULL, NULL, NULL);

		if(empty($string)) {
			return $output;
		}

		$delimiters = array('(', '%', '[');
		foreach($delimiters as $index => $delimiter) {
			$string = explode($delimiter, $string);
			if(!empty($string[0])) {
				$output[$index] = intval($string[0]);
			} if(isset($string[1])) {
				$string = trim($string[1]);
			}
		}

		if(!empty($string)) {
			$output[3] = intval($string);
		}

		return $output;
	}

	public function k_eval($key) {
		// K is of format nnn/mmm x kkk; We want abs(nnn - mmm)
		if(empty($this->$key)) {
			return false;
		}
		$str = explode('x', $this->$key);
		$str = explode('/', $str[0]);
		return '(' . abs($str[0] - $str[1]) . ')';
	}

	// Returns a list of all applicable conditions; used in résumés and statistical overviews
	public function diagnose() {
		$conditions = array(
			'emetropía' => False,
			'astigmatismo' => False,
			'miopía' => False,
			'hipermetropía' => False,
			'presbicia' => False,
			'anisometropía' => False,
			'astenopía' => False
		);

		//$ecea = $this->ecea_split($this->m_od);

		// Astigmatismo
		if(!empty($this->m_od_cylinder)) {
			$conditions['astigmatismo'] = True;
		}

		// Miopía / Hipermetropía
		if(!empty($this->m_od_sphere)) {
			if($this->m_od_sphere < 0) {
				$conditions['miopía'] = True;
			} else {
				$conditions['hipermetropía'] = True;
			}
		}

		// Presbicia
		if(!empty($this->m_od_addition)) {
			$conditions['presbicia'] = True;
		}

		// Anisometropía
		if(isset($this->m_od_sphere) || isset($this->m_od_cylinder)) {
			//$ecea_oi = $this->ecea_split($this->m_oi);
			if( (abs($this->m_od_sphere) - $this->m_oi_sphere >= 100) || (abs($this->m_od_cylinder) - $this->m_oi_cylinder >= 100) ) {
				$conditions['anisometropía'] = True;
			}
		}

		// If we have no conditions, we've got emetropía
		if(!in_array(True, $conditions)) {
			$conditions['emetropía'] = True;
		}

		// Astenopía
		
		/*
		echo 'M_OD :: ', print_r($ecea), '<br>';
		echo 'This patient has: <br>';
		foreach($conditions as $key => $val) {
			if($val) { echo $key, '<br>'; }
		}
		echo '<br><br>';
		 */

		return $conditions;
	}

	public function list_conditions() {
		$conditions = $this->diagnose();
		$output = '';

		foreach($conditions as $key => $value) {
			if($value) {
				$output .= ucfirst($key) . ', ';
			}
		}

		return substr($output, 0, -2);
	}

	public function resume() {
		$conditions = $this->diagnose();
		$output = '';

		$output = 'encontrándose agudeza visual de 20/' . ($this->od ? $this->od : '---') . ' en ojo derecho y 20/' . ($this->oi ? $this->oi : '---') . ' en ojo izquierdo' .
			(!empty($this->motive) ? ' y refiere ' . $this->motive : '') . '.' . PHP_EOL;

		$resultado = '';
		if($conditions['astigmatismo']) {
			$resultado = 'astigmatismo';
		} else {
			if($conditions['miopía']) {
				$resultado = 'miopía';
			} else if($conditions['hipermetropía']) {
				$resultado = 'hipermetropía';
			} else if($conditions['emetropía']) {
				$resultado = 'emetropía';
			}
		}

		$output .= 'Realizamos retinoscopía dando por resultado ' . $resultado;
		if($this->recomendaciones) {
			$output .= ' y se recomienda ' . $this->recomendaciones;
		} else {
			$output .= '.';
		}

		return $output;
	}

	public function save() {
		// First handle the regular booleans
		$keys = array('has_diabetes', 'has_hypertension', 'has_glaucoma_history', 'pos_mirada', 'print_resume');
		foreach($keys as $key) {
			if(!isset($this->$key)) {
				$this->$key = '0'; // Unset in the form equals false.
			}
		}

		// Now the "special" checkbox/radiobutton combination
		$keys = array('vision_cromatica', 'confrontacion', 'arco_senil', 'amsler', 'pupilas_redondas', 'pupilas_iguales', 'pupilas_luz', 'pupilas_acomodacion');
		foreach($keys as $key) {
			$field = 'has_' . $key;
			if(!isset($this->$field)) {
				$this->$field = '0'; // Unset in the form equals false.
			} else {
				$this->$field = $this->$key;
			}
		}

		if(!$this->validate()) {
			return false;
		}

		if($this->id == 0) {
			$stmt = $this->db->prepare('INSERT INTO record (
	patient_id, 
	reference, 
	occupation, 
	sport, 
	pc_usage, 
	health, 
	has_diabetes,
	has_hypertension,
	has_glaucoma_history,
	addictions,
	w_od, w_oi,
	motive,
	av,
	od, oi,
	estenop_od,
	estenop_oi,
	cover_test,
	flash_od_horiz,
	flash_oi_horiz,
	flash_od_vert,
	flash_oi_vert,
	pos_mirada,
	m_od_sphere,
	m_od_cylinder,
	m_od_axis,
	m_od_addition,
	m_oi_sphere,
	m_oi_cylinder,
	m_oi_axis,
	m_oi_addition,
	j, j_prim,
	cv_od, cv_oi,
	k_od, k_oi,
	print_resume,
	lectura,
	observaciones,
	recomendaciones,
	conversion_dv,
	tipo_lc,
	amplitud_acomodacion,
	acomodacion_relativa,
	retardo_acomodativo,
	ppc,
	tonometria_od,
	tonometria_oi,
	pccc,
	sensib_contraste,
	has_vision_cromatica,
	has_confrontacion,
	has_arco_senil,
	has_amsler,
	disco,
	opacidades,
	pantalla_tangente,
	pupilas_redondas,
	pupilas_iguales,
	pupilas_luz,
	pupilas_acomodacion
) VALUES (
	:patient_id, 
	:reference, 
	:occupation, 
	:sport, 
	:pc_usage, 
	:health, 
	:has_diabetes,
	:has_hypertension,
	:has_glaucoma_history,
	:addictions,
	:w_od, :w_oi,
	:motive,
	:av,
	:od, :oi,
	:estenop_od,
	:estenop_oi,
	:cover_test,
	:flash_od_horiz,
	:flash_oi_horiz,
	:flash_od_vert,
	:flash_oi_vert,
	:pos_mirada,
	:m_od_sphere,
	:m_od_cylinder,
	:m_od_axis,
	:m_od_addition,
	:m_oi_sphere,
	:m_oi_cylinder,
	:m_oi_axis,
	:m_oi_addition,
	:j, :j_prim,
	:cv_od, :cv_oi,
	:k_od, :k_oi,
	:print_resume,
	:lectura,
	:observaciones,
	:recomendaciones,
	:conversion_dv,
	:tipo_lc,
	:amplitud_acomodacion,
	:acomodacion_relativa,
	:retardo_acomodativo,
	:ppc,
	:tonometria_od,
	:tonometria_oi,
	:pccc,
	:sensib_contraste,
	:has_vision_cromatica,
	:has_confrontacion,
	:has_arco_senil,
	:has_amsler,
	:disco,
	:opacidades,
	:pantalla_tangente,
	:pupilas_redondas,
	:pupilas_iguales,
	:pupilas_luz,
	:pupilas_acomodacion
)');
		} else {
			echo 'There is currently no way to modify a record, so this should not really appear...';
			//$stmt = $this->db->prepare('UPDATE record SET   WHERE id = :id');
			//$stmt->bindParam(':id', $this->id);
		}
		
		$stmt->bindParam(':patient_id', $this->patient_id);
		$stmt->bindParam(':reference', $this->reference);
		$stmt->bindParam(':occupation', $this->occupation);
		$stmt->bindParam(':sport', $this->sport);
		$stmt->bindParam(':pc_usage', $this->pc_usage); 
		$stmt->bindParam(':health', $this->health);
		$stmt->bindParam(':has_diabetes', $this->has_diabetes);
		$stmt->bindParam(':has_hypertension', $this->has_hypertension);
		$stmt->bindParam(':has_glaucoma_history', $this->has_glaucoma_history);
		$stmt->bindParam(':addictions', $this->addictions);
		$stmt->bindParam(':w_od', $this->w_od);
		$stmt->bindParam(':w_oi', $this->w_oi);
		$stmt->bindParam(':motive', $this->motive);
		$stmt->bindParam(':av', $this->av);
		$stmt->bindParam(':od', $this->od);
		$stmt->bindParam(':oi', $this->oi);
		$stmt->bindParam(':estenop_od', $this->estenop_od);
		$stmt->bindParam(':estenop_oi', $this->estenop_oi);
		$stmt->bindParam(':cover_test', $this->cover_test);
		$stmt->bindParam(':flash_od_horiz', $this->flash_od_horiz);
		$stmt->bindParam(':flash_oi_horiz', $this->flash_oi_horiz);
		$stmt->bindParam(':flash_od_vert', $this->flash_od_vert);
		$stmt->bindParam(':flash_oi_vert', $this->flash_oi_vert);
		$stmt->bindParam(':pos_mirada', $this->pos_mirada);
		$stmt->bindParam(':m_od_sphere', $this->m_od_sphere);
		$stmt->bindParam(':m_od_cylinder', $this->m_od_cylinder);
		$stmt->bindParam(':m_od_axis', $this->m_od_axis);
		$stmt->bindParam(':m_od_addition', $this->m_od_addition);
		$stmt->bindParam(':m_oi_sphere', $this->m_oi_sphere);
		$stmt->bindParam(':m_oi_cylinder', $this->m_oi_cylinder);
		$stmt->bindParam(':m_oi_axis', $this->m_oi_axis);
		$stmt->bindParam(':m_oi_addition', $this->m_oi_addition);
		$stmt->bindParam(':j', $this->j);
		$stmt->bindParam(':j_prim', $this->j_prim);
		$stmt->bindParam(':cv_od', $this->cv_od);
		$stmt->bindParam(':cv_oi', $this->cv_oi);
		$stmt->bindParam(':k_od', $this->k_od);
		$stmt->bindParam(':k_oi', $this->k_oi);
		$stmt->bindParam(':print_resume', $this->print_resume);
		$stmt->bindParam(':lectura', $this->lectura);
		$stmt->bindParam(':observaciones', $this->observaciones);
		$stmt->bindParam(':recomendaciones', $this->recomendaciones);
		$stmt->bindParam(':conversion_dv', $this->conversion_dv);
		$stmt->bindParam(':tipo_lc', $this->tipo_lc);
		$stmt->bindParam(':amplitud_acomodacion', $this->amplitud_acomodacion);
		$stmt->bindParam(':acomodacion_relativa', $this->acomodacion_relativa);
		$stmt->bindParam(':retardo_acomodativo', $this->retardo_acomodativo);
		$stmt->bindParam(':ppc', $this->ppc);
		$stmt->bindParam(':tonometria_od', $this->tonometria_od);
		$stmt->bindParam(':tonometria_oi', $this->tonometria_oi);
		$stmt->bindParam(':pccc', $this->pccc);
		$stmt->bindParam(':sensib_contraste', $this->sensib_contraste);
		$stmt->bindParam(':has_vision_cromatica', $this->has_vision_cromatica);
		$stmt->bindParam(':has_confrontacion', $this->has_confrontacion);
		$stmt->bindParam(':has_arco_senil', $this->has_arco_senil);
		$stmt->bindParam(':has_amsler', $this->has_amsler);
		$stmt->bindParam(':disco', $this->disco);
		$stmt->bindParam(':opacidades', $this->opacidades);
		$stmt->bindParam(':pantalla_tangente', $this->pantalla_tangente);
		$stmt->bindParam(':pupilas_redondas', $this->pupilas_redondas);
		$stmt->bindParam(':pupilas_iguales', $this->pupilas_iguales);
		$stmt->bindParam(':pupilas_luz', $this->pupilas_luz);
		$stmt->bindParam(':pupilas_acomodacion', $this->pupilas_acomodacion);

		if($stmt->execute()) {
			if($this->id == 0) {
				$this->id = $this->db->lastInsertId();
			}
			return $this->id;
		}
		$error = $stmt->errorInfo();
		die('[ERROR] ' . $error[0]);
	}

	private function validate() {
		// Check if there are duplicates among the patient's records
		return true;
		if($this->is_duplicate()) {
			return false;
		}

		return true;
	}

	// This algorithm could do with some improvement...
	private function is_duplicate() {
		$result = $this->db->query('SELECT id FROM record WHERE patient_id = ' . $this->patient_id);
		if(!$result->rowCount()) {
			return false;
		}

		$old_records = array();
		while($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$r = new Record($this->db, $row['id']);
			unset($r->db);
			unset($r->id);
			unset($r->add_date);
			$old_records[] = $r;
		}

		$clone_this = $this;
		unset($clone_this->db);
		unset($clone_this->id);

		foreach($old_records as $old) {
			echo 'Comp: ', ($old == $clone_this ? 'SAME' : 'DIFFERENT'), '<br><br>';
			//foreach($old as $key => $value) {
			foreach($clone_this as $key => $value) {
				if(!is_int($key)) {
					echo 'Comparing ', $key, ': ', $value, ' vs. ', $old->$key, '... ';
					if($value === $old->$key) {
						echo 'The same! Old is ', gettype($old->$key), ' and new is ', gettype($value), '<br>';
					} else {
						echo '<strong>DIFFERENT</strong> Old is ', gettype($old->$key), ' and new is ', gettype($value), '<br>';
					}
				}
			}
		}

/*
		echo 'Comparing ', sizeof($old_records), ' records.';
		echo nl2br(print_r($clone_this, true));
		echo nl2br(print_r($old_records[0], true));
		if(in_array($clone_this, $old_records)) {
			echo 'A duplicate exists!';
		} else {
			echo 'All OK!';
		}
		// tmp
		return true;
 */
		/*
			$old_record->id = $this->id;
			$old_record->add_date = $this->add_date; // This will always differ, so let's just discard it for now

			// This is a bit intricate, but for now let's go with it as a decent one-to-one comparison
			$old = get_object_vars($old_record);
			$new = get_object_vars($this);
			foreach($old as $key => $value) {
				if( !is_int($key) && ($value != $new[$key]) ) {
					if($value == 0 && empty($new[$key])) {
					} else {
						$is_duplicate = false;
						break;
					}
				}
			}
		}
		return $is_duplicate;
		 */
	}
}
