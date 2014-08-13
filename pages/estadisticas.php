<?php
	$statistics = new Statistics($db);
	$data       = $statistics->getWpmData();

	$avg = '';
	$std = '';

	foreach($data as $age => $entry) {
		$avg .= '[' . $age . ', ' . $entry['average'] . '], ';
		$std .= '{ min: 0.1, max: 0.2 }, '; // Testing
	}
?>

<script>
	jQuery(document).ready(function() {
		var avg_wpm = [<?php echo substr($avg, 0, -2); ?>];
		var std_dev = [<?php echo substr($std, 0, -2); ?>];

		$('#avg_wpm_chart').jqplot([avg_wpm], {
			title:'Promedio de palabras por minuto',
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				rendererOptions: {
					varyBarColor: false,
					errorBarWidth: 2,
					errorData: std_dev,
					errorTextData: [["foo"]],
					barDirection: "vertical",
				},
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
