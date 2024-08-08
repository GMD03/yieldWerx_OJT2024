<?php
    require_once('libs/Database.php');

    class TableController {
        private $conn;

        private $filters = [];
        private $params = [];
        private $where_clause = '';
        private $headers = [];
        private $all_columns = [];


        public function __construct() {
            $database = new Database();
            $this->conn = $database->connect();
        }

        public function init() 
        {
            // Filters from selection_criteria.php
            $this->filters = [
                "l.Facility_ID" => isset($_GET['facility']) ? $_GET['facility'] : [],
                "l.work_center" => isset($_GET['work_center']) ? $_GET['work_center'] : [],
                "l.part_type" => isset($_GET['device_name']) ? $_GET['device_name'] : [],
                "l.program_name" => isset($_GET['test_program']) ? $_GET['test_program'] : [],
                "l.lot_ID" => isset($_GET['lot']) ? $_GET['lot'] : [],
                "w.wafer_ID" => isset($_GET['wafer']) ? $_GET['wafer'] : [],
                "tm.Column_Name" => isset($_GET['parameter']) ? $_GET['parameter'] : [],
                "p.probing_sequence" => isset($_GET['abbrev']) ? $_GET['abbrev'] : []
            ];

            // Prepare SQL filters
            $sql_filters = [];
            foreach ($this->filters as $key => $values) {
                if (!empty($values)) {
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $sql_filters[] = "$key IN ($placeholders)";
                    $this->params = array_merge($this->params, $values);
                }
            }

            // Create the WHERE clause if filters exist
            if (!empty($sql_filters)) {
                $this->where_clause = 'WHERE ' . implode(' AND ', $sql_filters);
            }
        }

        public function getCount() 
        {
            // Count total number of records with filters
            $query = "SELECT COUNT(*) AS total 
                        FROM DEVICE_1_CP1_V1_0_001 d1
                        JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
                        JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
                        JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                        JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
                        JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                        $this->where_clause";  // Append WHERE clause if it exists

            $count_stmt = sqlsrv_query($this->conn, $query, $this->params);
            if ($count_stmt === false) {
                die('Query failed: ' . print_r(sqlsrv_errors(), true));
            }
            $total_rows = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC)['total'];
            sqlsrv_free_stmt($count_stmt); // Free the count statement here

            return $total_rows;
        }

        public function writeTableHeaders()
        {
            $query = "SELECT tm.Column_Name, tm.Test_Name, Test_Number FROM TEST_PARAM_MAP tm
                     ORDER BY Test_Number ASC";

            $stmt = sqlsrv_query($this->conn, $query, $this->params);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            // Create an array to map Column_Name to Test_Name
            $column_to_test_name_map = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if (!empty($row['Column_Name']) && !empty($row['Test_Name'])) {
                    $column_to_test_name_map[$row['Column_Name']] = '[' . substr($row['Column_Name'], 1) . ']' . $row['Test_Name'];
                }
            }
            sqlsrv_free_stmt($stmt); // Free the statement here after fetching the mapping

            // static columns
            $columns = ['ID', 'Facility', 'Work Center', 'Device Name', 'Test Program', 'Lot ID', 'Lot Test Temprature', 'Wafer ID', 'Wafer Sequence', 'Wafer Start Time', 'Wafer Finish Time', 'Unit Number', 'Head Number', 'Site Number', 'Hard Bin No', 'Soft Bin No', 'Tests Executed', 'Test Time (ms)', 'Die Type', 'Home Die', 'Alignment Die', 'Include In Yield', 'Include In Die Count', 'Reticle Number', 'Reticle Position Row', 'Reticle Position Column', 'Reticle Active Sites Count', 'Reticle Site Position Row', 'Reticle Site Position Column', 'Part Number', 'Part Name', 'Die ID', 'Die Name', 'SINF', 'User Defined Attribute 1', 'User Defined Attribute 2', 'User Defined Attribute 3', 'Die Start Time', 'Die End Time'];

            // sql static + dynamic columns
            $this->all_columns = array_merge($columns, $this->filters['tm.Column_Name']);
            
            // table headers
            $this->headers = array_map(function($column) use ($column_to_test_name_map) {
                return isset($column_to_test_name_map[$column]) ? $column_to_test_name_map[$column] : $column;
            }, $this->all_columns);

            foreach ($this->headers as $header) {
                echo "<th class='px-6 py-3 whitespace-nowrap'>$header</th>";
            }
        }

        public function writeTableData()
        {
            // Dynamically construct the column part of the SQL query
            $column_list = !empty($this->filters['tm.Column_Name']) ? implode(', ', array_map(function($col) { return "d1.$col"; }, $this->filters['tm.Column_Name'])) : '*';

            // Retrieve all records with filters
            $query = "SELECT l.Facility_ID 'Facility', l.Work_Center 'Work Center', l.Part_Type 'Device Name', l.Program_Name 'Test Program', l.Lot_ID 'Lot ID', l.Test_Temprature 'Lot Test Temprature', w.Wafer_ID 'Wafer ID', w.Wafer_Sequence 'Wafer Sequence', w.Wafer_Start_Time 'Wafer Start Time', w.Wafer_Finish_Time 'Wafer Finish Time', d1.Unit_Number 'Unit Number', d1.X , d1.Y, d1.Head_Number 'Head Number', d1.Site_Number 'Site Number', d1.HBin_Number 'Hard Bin No', d1.SBin_Number 'Soft Bin No', d1.Tests_Executed 'Tests Executed', d1.Test_Time 'Test Time (ms)', d1.DieType_Sequence 'Die Type', d1.IsHomeDie 'Home Die', d1.IsAlignmentDie 'Alignment Die', d1.IsIncludeInYield 'Include In Yield', d1.IsIncludeInDieCount 'Include In Die Count', d1.ReticleNumber 'Reticle Number', d1.ReticlePositionRow 'Reticle Position Row', d1.ReticlePositionColumn 'Reticle Position Column', d1.ReticleActiveSitesCount 'Reticle Active Sites Count', d1.ReticleSitePositionRow 'Reticle Site Position Row', d1.ReticleSitePositionColumn 'Reticle Site Position Column', d1.PartNumber 'Part Number', d1.PartName 'Part Name', d1.DieID 'Die ID', d1.DieName 'Die Name', d1.SINF 'SINF', d1.UserDefinedAttribute1 'User Defined Attribute 1', d1.UserDefinedAttribute2 'User Defined Attribute 2', d1.UserDefinedAttribute3 'User Defined Attribute 3', d1.DieStartTime 'Die Start Time', d1.DieEndTime 'Die End Time', $column_list FROM DEVICE_1_CP1_V1_0_001 d1
                    JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
                    JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $this->where_clause
                    ORDER BY w.Wafer_ID";

            $i = 1;
            $stmt = sqlsrv_query($this->conn, $query, $this->params); // Re-execute query to fetch data for display
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                echo "<tr class='bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'>";
                foreach ($this->all_columns as $column) {
                    $value = isset($row[$column]) ? $row[$column] : '';
                    if ($column === "ID") {
                        $value = $i;
                    }
                    if ($value instanceof DateTime) {
                        $value = $value->format('Y-m-d H:i:s'); // Adjust format as needed
                    }
                    echo "<td class='px-6 py-3 whitespace-nowrap text-center'>$value</td>";
                }
                echo "</tr>";
                $i++;
            }
            sqlsrv_free_stmt($stmt); // Free the statement here after displaying data
        }

        public function exportCSV()
        {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=wafer_data.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, $this->headers);
            
            // Dynamically construct the column part of the SQL query
            $column_list = !empty($this->filters['tm.Column_Name']) ? implode(', ', array_map(function($col) { return "d1.$col"; }, $this->filters['tm.Column_Name'])) : '*';

            // Retrieve all records with filters
            $query = "SELECT l.Facility_ID 'Facility', l.Work_Center 'Work Center', l.Part_Type 'Device Name', l.Program_Name 'Test Program', l.Lot_ID 'Lot ID', l.Test_Temprature 'Lot Test Temprature', w.Wafer_ID 'Wafer ID', w.Wafer_Start_Time 'Wafer Start Time', w.Wafer_Finish_Time 'Wafer Finish Time', d1.Unit_Number 'Unit Number', d1.X , d1.Y, d1.Head_Number 'Head Number', d1.Site_Number 'Site Number', d1.HBin_Number 'Hard Bin No', d1.SBin_Number 'Soft Bin No', d1.Tests_Executed 'Tests Executed', d1.Test_Time 'Test Time (ms)', d1.DieType_Sequence 'Die Type', d1.IsHomeDie 'Home Die', d1.IsAlignmentDie 'Alignment Die', d1.IsIncludeInYield 'Include In Yield', d1.IsIncludeInDieCount 'Include In Die Count', d1.ReticleNumber 'Reticle Number', d1.ReticlePositionRow 'Reticle Position Row', d1.ReticlePositionColumn 'Reticle Position Column', d1.ReticleActiveSitesCount 'Reticle Active Sites Count', d1.ReticleSitePositionRow 'Reticle Site Position Row', d1.ReticleSitePositionColumn 'Reticle Site Position Column', d1.PartNumber 'Part Number', d1.PartName 'Part Name', d1.DieID 'Die ID', d1.DieName 'Die Name', d1.SINF 'SINF', d1.UserDefinedAttribute1 'User Defined Attribute 1', d1.UserDefinedAttribute2 'User Defined Attribute 2', d1.UserDefinedAttribute3 'User Defined Attribute 3', d1.DieStartTime 'Die Start Time', d1.DieEndTime 'Die End Time', $column_list FROM DEVICE_1_CP1_V1_0_001 d1
                    JOIN WAFER w ON w.Wafer_Sequence = d1.Wafer_Sequence
                    JOIN LOT l ON l.Lot_Sequence = w.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN DEVICE_1_CP1_V1_0_002 d2 ON d1.Die_Sequence = d2.Die_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $this->where_clause
                    ORDER BY w.Wafer_ID";

            // Re-execute query to fetch data for export
            $stmt = sqlsrv_query($this->conn, $query, $this->params);
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $csv_row = [];
                foreach ($this->all_columns as $column) {
                    $value = isset($row[$column]) ? $row[$column] : '';
                    if ($value instanceof DateTime) {
                        $csv_row[] = $value->format('Y-m-d H:i:s');
                    } else {
                        $csv_row[] = (string)$value;
                    }
                }
                fputcsv($output, $csv_row);
            }

            fclose($output);
            sqlsrv_free_stmt($stmt);
        }
    }