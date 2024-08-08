<?php
    require_once('libs/Database.php');
    
    $database = new Database();
    $conn = $database->connect();

// Filters from URL parameters
$filters = [
    "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
    "l.work_center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
    "l.part_type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
    "l.program_name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
    "l.lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
    "w.wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
    "tm.Column_Name" => isset($_GET['parameter']) ? $_GET['parameter'] : []
];

$group = [
    "x" => isset($_GET["group-x"]) ? $_GET["group-x"][0] : '',
    "y" => isset($_GET["group-y"]) ? $_GET["group-y"][0] : ''
];

// Prepare SQL filters
$sql_filters = [];
$params = [];
foreach ($filters as $key => $values) {
    if (!empty($values)) {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $sql_filters[] = "$key IN ($placeholders)";
        $params = array_merge($params, $values);
    }
}

// Create the WHERE clause if filters exist
$where_clause = '';
if (!empty($sql_filters)) {
    $where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
}

$dataArray = [];
$xGroupValues = [];
$yGroupValues = [];
$ticks = ['x' => ['min' => null, 'max' => null], 'y' => ['min' => null, 'max' => null]];
$index = 0;

if ($group['x'] != '' && $group['y'] != '') {

    $xquery = "SELECT distinct w.". $group['x'] . " FROM WAFER w
             JOIN DEVICE_1_CP1_V1_0_001 d1 ON w.Wafer_Sequence = d1.Wafer_Sequence
             JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
             JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
             JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
             $where_clause
             ORDER BY w.". $group['x'];

    $stmt = sqlsrv_query($conn, $xquery, $params);
    if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){ $xGroupValues[] = $row[$group['x']]; }
    sqlsrv_free_stmt($stmt);

    $yquery = "SELECT distinct w.". $group['y'] . " FROM WAFER w
             JOIN DEVICE_1_CP1_V1_0_001 d1 ON w.Wafer_Sequence = d1.Wafer_Sequence
             JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
             JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
             JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
             $where_clause
             ORDER BY w.". $group['y'];
    
    $stmt = sqlsrv_query($conn, $yquery, $params);
    if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){ $yGroupValues[] = $row[$group['y']]; }
    sqlsrv_free_stmt($stmt);

    foreach ($yGroupValues as $i => $yGroupValue) {
        foreach ($xGroupValues as $j => $xGroupValue) {
            $dataArray[] = ['groupX' => $xGroupValue, 'groupY' => $yGroupValue, 'data' => []];

            $group_where_clause = $where_clause ? 
                $where_clause . "AND w.{$group['x']} = {$xGroupValue} AND w.{$group['y']} = {$yGroupValue}" :
                "WHERE w.{$group['x']} = {$xGroupValue} AND w.{$group['y']} = {$yGroupValue}";
            
            // Retrieve all records with filters
            $query = "SELECT l.Facility_ID 'Facility', l.Work_Center 'Work Center', l.Part_Type 'Device Name', l.Program_Name 'Test Program', l.Lot_ID 'Lot ID', l.Test_Temprature 'Lot Test Temprature', w.Wafer_ID 'Wafer ID', w.Wafer_Start_Time 'Wafer Start Time', w.Wafer_Finish_Time 'Wafer Finish Time', d1.Unit_Number 'Unit Number', d1.X , d1.Y, d1.Head_Number 'Head Number', d1.Site_Number 'Site Number', d1.HBin_Number 'Hard Bin No', d1.SBin_Number 'Soft Bin No', d1.Tests_Executed 'Tests Executed', d1.Test_Time 'Test Time (ms)', d1.DieType_Sequence 'Die Type', d1.IsHomeDie 'Home Die', d1.IsAlignmentDie 'Alignment Die', d1.IsIncludeInYield 'Include In Yield', d1.IsIncludeInDieCount 'Include In Die Count', d1.ReticleNumber 'Reticle Number', d1.ReticlePositionRow 'Reticle Position Row', d1.ReticlePositionColumn 'Reticle Position Column', d1.ReticleActiveSitesCount 'Reticle Active Sites Count', d1.ReticleSitePositionRow 'Reticle Site Position Row', d1.ReticleSitePositionColumn 'Reticle Site Position Column', d1.PartNumber 'Part Number', d1.PartName 'Part Name', d1.DieID 'Die ID', d1.DieName 'Die Name', d1.SINF 'SINF', d1.UserDefinedAttribute1 'User Defined Attribute 1', d1.UserDefinedAttribute2 'User Defined Attribute 2', d1.UserDefinedAttribute3 'User Defined Attribute 3', d1.DieStartTime 'Die Start Time', d1.DieEndTime 'Die End Time', d1.{$filters['tm.Column_Name'][0]}, d1.{$filters['tm.Column_Name'][1]} FROM DEVICE_1_CP1_V1_0_001 d1
                    JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
                    JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $group_where_clause
                    ORDER BY w.Wafer_ID";
                    
            $stmt = sqlsrv_query($conn, $query, $params);
            if ($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
                $xValue = $row[$filters['tm.Column_Name'][0]];
                $yValue = $row[$filters['tm.Column_Name'][1]];
                if ($xValue != '' && $yValue != '') {
                    $dataArray[$index]['data'][] = ['x' => $xValue, 'y' => $yValue];
                
                    if ($ticks['x']['min'] == null || $xValue < $ticks['x']['min']){
                        $ticks['x']['min'] = $xValue;
                    }
                    if ($ticks['x']['max'] == null || $xValue > $ticks['x']['max']){
                        $ticks['x']['max'] = $xValue;
                    }

                    if ($ticks['y']['min'] == null || $yValue < $ticks['y']['min']){
                        $ticks['y']['min'] = $yValue;
                    }
                    if ($ticks['y']['max'] == null || $yValue > $ticks['y']['max']){
                        $ticks['y']['max'] = $yValue;
                    }
                }
            
            }
            sqlsrv_free_stmt($stmt);
            $index++;
        }
    }

}

$dataArrayJson = json_encode($dataArray);
$ticksJson = json_encode($ticks);

?>

<h1 class="text-center text-4xl font-semibold mb-4">XY Scatter Plots</h1>
<div id="chartsContainer"></div>

<script>
    const dataArray = <?= $dataArrayJson; ?>;
    const ticks = <?= $ticksJson; ?>;
    const chartsContainer = document.getElementById('chartsContainer');
    console.log(dataArray);
    
    // const numParams = Math.sqrt(dataSets.length);
    chartsContainer.style.gridTemplateColumns = `repeat(<?= count($xGroupValues); ?>, 1fr)`;

    dataArray.forEach((array, index) => {
        const div = document.createElement('div');
        div.className = 'chart-container';
        const canvas = document.createElement('canvas');
        canvas.id = `chart-${index}`;
        div.appendChild(canvas);
        chartsContainer.appendChild(div);

        new Chart(canvas, {
            type: 'scatter',
            data: {
                datasets: [{
                    data: array.data,
                    backgroundColor: 'rgba(115, 33, 98, 0.6)',
                    borderColor: 'rgba(82, 16, 69, 1)',
                    pointRadius: 2,
                    showLine: false
                }]
            },
            options: {
                maintainAspectRatio: true,
                plugins:{
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        position: 'bottom',
                        title: {
                            display: true,
                            text: array.groupX
                        },
                        min: ticks.x.min,
                        max: ticks.x.max
                    },
                    y: {
                        title: {
                            display: true,
                            text: array.groupY
                        },
                        min: ticks.y.min,
                        max: ticks.y.max
                    }
                }
            }
        });
    });
</script>
<?php
sqlsrv_close($conn);
?>