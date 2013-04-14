<?php
	$result = $db->query("SELECT DATE_FORMAT(DATE_SUB(r.add_date, INTERVAL p.birthdate YEAR), '%y') AS age, AVG(r.lectura) AS avg_wpm FROM record AS r, patient AS p WHERE r.patient_id = p.id AND lectura > 0 GROUP BY age");
	$output = '';
	while($row = $result->fetch(PDO::FETCH_ASSOC)) {
		if($row['age'] >= 5 && $row['age'] <= 15) {
			$output .= '[' . $row['age'] . ', ' . $row['avg_wpm'] . '], ';
		}
	}
?>

<script>
	jQuery(document).ready(function() {
		var avg_wpm = [<?php echo substr($output, 0, -2); ?>];

		console.log(avg_wpm);

		$('#avg_wpm_chart').jqplot([avg_wpm], {
			title:'Promedio de palabras por minuto',
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				rendererOptions: { varyBarColor: false },
			},
			axes:{
				xaxis: {
					renderer: $.jqplot.CategoryAxisRenderer,
					label: 'Edad (años)',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				},
				yaxis: {
					label: 'Lectura (ppm)',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
					tickOptions:{
						formatString:'%.2f'
					}
				}
			},
			highlighter: {
				show: true,
				tooltipAxes: 'y',
				sizeAdjust: 15.5,
			},
		});
	});
</script>

<h1>Estadísticas</h1>
<div id="avg_wpm_chart" class="chart" style="width: 800px; height: 400px;"></div>
