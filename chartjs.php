<?php

/*

Sample script for source chartjs using own PHP wrapper class ChartJS
extracted from: https://github.com/Ejdamm/chart.js-php/blob/master/js/Chart.min.js

*/

require_once("classes/ChartJS.php");

$values = [
        [28, 48, 40, 19, 86, 27, 90],
        [65, 59, 80, 81, 56, 55, 40]
];

$data = [
        'labels' => ["January", "February", "March", "April", "May", "June", "July"],
        'datasets' => [] //You can add datasets directly here or add them later with addDataset()
];

$colors = [
    //['backgroundColor' => 'rgba(28,116,190,.8)', 'borderColor' => 'blue'],
        ['backgroundColor' => 'lightblue', 'borderColor' => 'blue'],
        ['backgroundColor' => '#f2b21a', 'borderColor' => '#e5801d'],
        ['backgroundColor' => ['blue', 'purple', 'red', 'black', 'brown', 'pink', 'green']]
];

//There is a bug in Chart.js that ignores canvas width/height if responsive is not set to false
$options = ['responsive' => true, indexAxis => 'y', 'radius' => 8, 'hoverRadius' => 14,];
//'scales' => ['yAxes' => ['min' => 1], ['yAxes' => ['max' => 5]]]	,];

//html attributes fot the canvas element
//$attributes = ['id' => 'example', 'width' => 500, 'height' => 500, 'style' => 'display:inline;'];
$gattributes = ['style' => 'display:inline;'];

$datasets = [
    //['data' => $values[0], 'label' => "Legend1"] + $colors[0],
    //['data' => $values[0], 'label' => "Legend1", 'radius' => 8,'hoverRadius' => 14]+ $colors[0],
        ['data' => $values[0], 'label' => "Legend1"] + $colors[0],
        ['data' => $values[1], 'label' => "Legend2"] + $colors[1],
        ['data' => $values[0], 'label' => "Legend1"] + $colors[1],
        ['data' => $values[1], 'label' => "Legend2"] + $colors[2],
        ['data' => $values[0]] + $colors[2],
];

/*
 * Create charts
 *
 */

$attributes = $gattributes; $attributes['id'] = 'example_line';
$Line = new ChartJS('line', $data, $options + array('lineTension' => 0.3), $attributes);
$Line->addDataset($datasets[0]);
$Line->addDataset($datasets[1]);

$attributes = $gattributes;  $attributes['id'] = 'example_bar';
$Bar = new ChartJS('bar', $data, $options, $attributes);
$Bar->addDataset($datasets[2]);
$Bar->addDataset($datasets[3]);

/*
$attributes = $gattributes;  $attributes['id'] = 'example_radar';
$Radar = new ChartJS('radar', $data, $options, $attributes);
$Radar->addDataset($datasets[0]);
$Radar->addDataset($datasets[1]);

$attributes = $gattributes;  $attributes['id'] = 'example_polarArea';
$PolarArea = new ChartJS('polarArea', $data, $options, $attributes);
$PolarArea->addDataset($datasets[4]);

$attributes = $gattributes;  $attributes['id'] = 'example_pie';
$Pie = new ChartJS('pie', $data, $options, $attributes);
$Pie->addDataset($datasets[4]);

$attributes = $gattributes; $attributes['id'] = 'example_doughnut';
$Doughnut = new ChartJS('doughnut', $data, $options, $attributes);
$Doughnut->addDataset($datasets[4]);
*/

/*
 * Print charts
 *
 */

?><!DOCTYPE html>
<html>
<head>
    <title>Chart.js-PHP</title>

    <script src="js/chart/chart.min.js"></script>
    <script src="js/chart/driver.js"></script>
</head>
<body>
<h1>Line</h1>
<?php
echo $Line;
?>
<h1>Bar</h1>
<?php
echo $Bar;
?>
<h1>Radar</h1>
<?php
echo $Radar;
?>
<h1>Polar Area</h1>
<?php
echo $PolarArea;
?>
<h1>Pie & Doughnut</h1>
<?php
echo $Pie . $Doughnut;
?>

<script src="js/chart/chart.min.js"></script>
<script src="js/chart/driver.js"></script>
<script>
    (function () {
        loadChartJsPhp();
    })();
</script>
<?php
$printWidth = "110vw";
require_once("print.js.php");
?>
</body>
</html>


<?php

/*

// Snippet

// Use Moodle Chartjs for ev_compare_results
if ( $ChartAxis == "line")
{	$chart = new \core\chart_line(); 
	$chart->set_smooth(true); 
}
elseif ( $ChartAxis == "pie")
{	$chart = new \core\chart_pie();	}
elseif ( $ChartAxis == "doughnut")
{	$chart = new \core\chart_pie();
	$chart->set_doughnut(true);
}
// draw stacked bar chart
elseif ( $ChartAxis == "stacked")
{	$chart = new core\chart_bar();
	$chart->set_stacked(true);
}
// draw bar chart - DEFAULT
else  //if ( $ChartAxis == "bar")
{	$chart = new \core\chart_bar();
	$chart->set_horizontal(true);
}

//$chart = new \core\chart_line();
$chart->set_title($evaluation->name);
$series = new \core\chart_series(format_string("Alle Abgaben"), $data['average']);
$series->set_labels($data['average_labels']);
$chart->add_series($series);
$chart->set_labels($data['labels']);
if ( $ChartAxis !== "pie" AND $ChartAxis !== "doughnut" )
{	$yaxis = $chart->get_yaxis(0, true);
	$yaxis->set_stepsize(1);
	$yaxis->set_min(1);
	$yaxis->set_max( $maxval ); 
	$chart->get_yaxis(1, true)->set_labels($label2);
	$xaxis = $chart->get_xaxis(0, true);
	$xaxis->set_stepsize(1);
}
$count = 0;
if ( $allKey AND count($allIDs) <= $maxCharts )
{	//foreach ( $allIDs AS $allID => $all )
	foreach ( $allIDs AS $key => $value )
	{ 	$series = new \core\chart_series( $allValues[$key], $data['average_'.$value] );	
		//$series->set_type(\core\chart_series::TYPE_LINE); 
		$series->set_labels( $data['labels_'.$value] );
		$chart->add_series($series);
	}
}
elseif ( $filter )
{	$series = new \core\chart_series( implode(", ",$fTitle), $data["averageF"]);	
	$series->set_labels($data['averageF_labels']);
	$chart->add_series($series);
	}
//$xaxis = $chart->get_xaxis(0, true);
//$chart->get_xaxis(1, true)->set_labels($data['labelsX']);
echo $OUTPUT->render($chart);

// show data table
echo '<script src="js/jquery.min.js"></script><script>
jQuery.("[aria-controls^=chart-table-data-]").text("");
jQuery.("[aria-controls^=chart-table-data-]").attr("aria-expanded", true);
jQuery.("[aria-controls^=chart-table-data-]").show();
</script>';
*/		
	
