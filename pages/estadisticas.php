<?php
	$statistics = new Statistics($db);
	$data       = $statistics->getWpmData();

	echo nl2br(print_r($data, true));

	$avg = '';
	$err = '';

	foreach($data as $age => $entry) {
		$avg .= '[' . $age . ', ' . $entry['average'] . '], ';
		//$err .= '[' . ($entry['average'] - $entry['std_dev']) . ', ' . ($entry['average'] + $entry['std_dev'])  . '], '; // Standard deviation
		$err .= '[' . $entry['conf_int']['lower'] . ', ' . $entry['conf_int']['upper']  . '], '; // Confidence interval
	}
?>

<script>
	jQuery(document).ready(function() {
		var avg_wpm = [<?php echo substr($avg, 0, -2); ?>];
		var err     = [<?php echo substr($err, 0, -2); ?>];

		var theme = {
			grid: {
				drawBorder: false,
				shadow:     false,
				background: 'rgba(255, 255, 255, 0.0)'
			},
			seriesDefaults: {
				shadow:     true,
				showMarker: true
			},
			axes: {
				xaxis: {
					label: 'Edad (años)',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
					pad: 0,
					tickInterval: '1',
				},
				yaxis: {
					label: 'Lectura (ppm)',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
					pad: 0,
					tickOptions:{
						formatString:'%.2f'
					}
				},
			},
			highlighter: {
				show: true,
				tooltipAxes: 'y',
				sizeAdjust: 15.5,
			},
		};

		// http://www.jqplot.com/deploy/dist/examples/bandedLine.html
		plot1 = $.jqplot('avg_wpm_chart', [avg_wpm], $.extend(true, {}, theme, {
			title:'Promedio de palabras por minuto',
	        series: [{
	            rendererOptions: {
	                bandData: err,
					smooth:   true,
					bands: {
						fillColor: 'rgba(200, 180, 100, 0.35)',
					}
	            }
	        }]
	    }));

<?php
	/*
		$('#avg_wpm_chart').jqplot([avg_wpm], {
			title:'Promedio de palabras por minuto',
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				rendererOptions: {
					varyBarColor: false,
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
	*/
?>
	});
</script>

<h1>Estadísticas</h1>
<div id="avg_wpm_chart" class="chart" style="width: 800px; height: 400px;"></div>
