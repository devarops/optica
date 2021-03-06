<?php
	$statistics = new Statistics($db);
	$data       = $statistics->getWpmData();
	$ages       = array_keys($data);

	//echo nl2br(print_r($data, true));

	$avg = '';
	$err = '';


	foreach($data as $age => $entry) {
		$avg .= '[' . $age . ', ' . $entry['average'] . '], ';
		$err .= '[' . ($entry['average'] - 2 * $entry['std_dev']) . ', ' . ($entry['average'] + 2 * $entry['std_dev'])  . '], '; // Standard deviation
		//$err .= '[' . $entry['conf_int']['lower'] . ', ' . $entry['conf_int']['upper']  . '], '; // Confidence interval
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
				shadow:     false,
				markerOptions: {
					show:   true,
					shadow: false,
					color:  'rgba(128, 128, 128, 0.8)',
					size:   7,
				}
			},
			axes: {
				xaxis: {
					label: 'Edad (años)',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
					ticks: <?php echo json_encode($ages); ?>,
					tickOptions: {
						formatString: '%d'
					}
				},
				yaxis: {
					label: 'Lectura (ppm)',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
					pad: 0,
					tickOptions: {
						formatString: '%.2f'
					}
				},
			},
			highlighter: {
				show: true,
				tooltipAxes: 'y',
				sizeAdjust: 12.5,
			},
		};

		// http://www.jqplot.com/deploy/dist/examples/bandedLine.html
		var plot1 = $.jqplot('avg_wpm_chart', [avg_wpm], $.extend(true, {}, theme, {
			title:'Promedio de palabras por minuto',
	        series: [{
	            rendererOptions: {
	                bandData: err,
					smooth:   true,
					bands: {
						fillColor: 'rgba(200, 180, 100, 0.35)',
						//showLines: true,
					}
	            }
	        }]
	    }));

<?php
	$data = $statistics->getTonometryHistogram(10);

	echo 'var data_od = [', join(', ', $data['od']), '];', PHP_EOL;
	echo 'var data_oi = [', join(', ', $data['oi']), '];', PHP_EOL;
?>

		var tplot = jQuery.jqplot('tonometry_chart', [data_od, data_oi], {
			title: 'Histograma de tonometría',
		
			grid: {
				drawBorder: false,
				shadow:     false,
				background: 'rgba(255, 255, 255, 0.0)'
			},

			seriesDefaults: {
				shadow: false,
				renderer: jQuery.jqplot.BarRenderer,
				rendererOptions: {
					barWidth: 12,
					barPadding: 0,
				},
			},

			series: [
				{ label: 'Ojo derecho' },
				{ label: 'Ojo izquierdo' },
			],

			axes: {
				xaxis: {
					label: 'Presión intraocular (agrupado)',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
					ticks: [<?php echo join(', ', array_map('round', $data['ticks'])); ?>],
					renderer: jQuery.jqplot.CategoryAxisRenderer,
					/*
					ticks: [<?php //echo join(', ', $data['ticks']); ?>],
					tickOptions: {
						formatString: '%.1f',
					},
					 */
				},
				yaxis: {
					label: 'Número de observaciones',
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
				}
			},

			legend: {
				show: true,
				location: 'ne',
			}
		});
	});
</script>

<h1><img src="resources/img/icon-statistics.png" height="48" width="48" alt=""> Estadísticas</h1>

<div class="fb-grid">
	<div id="general-stats" class="box">
		<h3>Datos generales</h3>
		<?php $data = $statistics->getBasicStats(); ?>
		<table class="noeffects" style="width: 90%;"> <!--; position: absolute; top: 10px; right: 10px;">-->
			<tr><th rowspan="2">Totales</th><td># Pacientes</td><td><?php echo number_format($data['tot_patients']); ?></td></tr>
			<tr><td># Expedientes</td><td><?php echo number_format($data['tot_records']); ?></td></tr>
			<tr><td colspan="3">&nbsp;</td></tr>
			<tr><th rowspan="3">Promedios</th><td>Expedientes por paciente</td><td><?php printf('%.2f', $data['avg_records_per_patient']); ?></td></tr>
			<tr><td>Nuevos pacientes por día</td><td><?php printf('%.2f', $data['avg_patients_per_day']); ?></td></tr>
			<tr><td>Expedientes registrados por día</td><td><?php printf('%.2f', $data['avg_records_per_day']); ?></td></tr>
		</table>
	</div>

	<div class="box">
		<div id="avg_wpm_chart" class="chart" style="height: 300px;"></div>
	</div>
</div>
<div class="fb-grid">
	<div class="box">
		<div id="tonometry_chart" class="chart" style="height: 300px;"></div>
	</div>

	<div class="box">
		<h3>Estadísticas de tonometría por investigación</h3>
		<table class="noeffects">
			<tbody>
			<?php
				foreach(Investigation::get_all_investigations($db) as $investigation) {
					$participants = $investigation->get_participants(True);
					$stats = $statistics->getTonometryStats($participants);
					echo '<tr><th colspan="2" style="font-size: 0.95em;">', $investigation->title, '</th></tr>', PHP_EOL;
					echo '<tr><td>Participantes</td><td>', sizeof($participants), '</td></tr>', PHP_EOL;
					echo '<tr><td>Promedio</td><td>', number_format($stats['avg'], 2), '</td></tr>', PHP_EOL;
					echo '<tr><td>Intervalo normal</td><td>', number_format($stats['avg'] - 2 * $stats['stdev'], 2), '&mdash;', number_format($stats['avg'] + 2 * $stats['stdev'], 2), '</td></tr>', PHP_EOL;
				}
			?>
			</tbody>
		</table>
	</div>
</div>
