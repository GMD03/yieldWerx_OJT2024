<?php
    require_once('libs/Database.php');

    class TableController {
        private $conn;

        private $filters = [];
        private $params = [];
        private $where_clause = '';
        private $join_table_clause = '';
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

            // get the corresponding table names
            $query = "SELECT distinct tm.Table_Name FROM LOT l
                      JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                      JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                      $this->where_clause";
            
            $stmt = sqlsrv_query($this->conn, $query, $this->params);
            if ($stmt === false) { die('Query failed: ' . print_r(sqlsrv_errors(), true)); }
            $tables = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $tables[] = $row['Table_Name']; }
            sqlsrv_free_stmt($stmt); // Free the count statement here    

            $joins = [];
            for ($i = 0; $i < count($tables); $i++) {
                if ($i === 0) {
                    // For the first table, use a base join
                    $joins[] = "JOIN " . $tables[$i] . " ON " . $tables[$i] . ".Wafer_Sequence = w.Wafer_Sequence";
                } else {
                    // For subsequent tables, join with the previous table
                    $joins[] = "JOIN " . $tables[$i] . " ON " . $tables[$i] . ".Die_Sequence = " . $tables[$i - 1] . ".Die_Sequence";
                }
            }

            if (!empty($joins)) {
                $this->join_table_clause = implode("\n", $joins);
            }
        }

        public function getCount() 
        {
            // Count total number of records with filters
            $query = "SELECT COUNT(*) AS total FROM LOT l
                        JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                        JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                        JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                        $this->join_table_clause
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
            $columns = ['ID', 'Facility', 'Work Center', 'Device Name', 'Test Program', 'Lot ID', 'Lot Test Temprature', 'Wafer ID', 'Wafer Start Time', 'Wafer Finish Time', 'Unit Number', 'Head Number', 'Site Number', 'Hard Bin No', 'Soft Bin No', 'Tests Executed', 'Test Time (ms)', 'Die Type', 'Home Die', 'Alignment Die', 'Include In Yield', 'Include In Die Count', 'Reticle Number', 'Reticle Position Row', 'Reticle Position Column', 'Reticle Active Sites Count', 'Reticle Site Position Row', 'Reticle Site Position Column', 'Part Number', 'Part Name', 'Die ID', 'Die Name', 'SINF', 'User Defined Attribute 1', 'User Defined Attribute 2', 'User Defined Attribute 3', 'Die Start Time', 'Die End Time'];

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
            $column_list = !empty($this->filters['tm.Column_Name']) ? implode(', ',  $this->filters['tm.Column_Name']) : '*';

            // Retrieve all records with filters
            $query = "SELECT l.Facility_ID 'Facility', l.Work_Center 'Work Center', l.Part_Type 'Device Name', l.Program_Name 'Test Program', l.Lot_ID 'Lot ID', l.Test_Temprature 'Lot Test Temprature', w.Wafer_ID 'Wafer ID', w.Wafer_Start_Time 'Wafer Start Time', w.Wafer_Finish_Time 'Wafer Finish Time', Unit_Number 'Unit Number', X , Y, Head_Number 'Head Number', Site_Number 'Site Number', HBin_Number 'Hard Bin No', SBin_Number 'Soft Bin No', Tests_Executed 'Tests Executed', Test_Time 'Test Time (ms)', DieType_Sequence 'Die Type', IsHomeDie 'Home Die', IsAlignmentDie 'Alignment Die', IsIncludeInYield 'Include In Yield', IsIncludeInDieCount 'Include In Die Count', ReticleNumber 'Reticle Number', ReticlePositionRow 'Reticle Position Row', ReticlePositionColumn 'Reticle Position Column', ReticleActiveSitesCount 'Reticle Active Sites Count', ReticleSitePositionRow 'Reticle Site Position Row', ReticleSitePositionColumn 'Reticle Site Position Column', PartNumber 'Part Number', PartName 'Part Name', DieID 'Die ID', DieName 'Die Name', SINF 'SINF', UserDefinedAttribute1 'User Defined Attribute 1', UserDefinedAttribute2 'User Defined Attribute 2', UserDefinedAttribute3 'User Defined Attribute 3', DieStartTime 'Die Start Time', DieEndTime 'Die End Time', $column_list FROM LOT l
                    JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $this->join_table_clause
                    $this->where_clause
                    ORDER BY l.Lot_ID, w.Wafer_ID";

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
            $column_list = !empty($this->filters['tm.Column_Name']) ? implode(', ',  $this->filters['tm.Column_Name']) : '*';

            // Retrieve all records with filters
            $query = "SELECT l.Facility_ID 'Facility', l.Work_Center 'Work Center', l.Part_Type 'Device Name', l.Program_Name 'Test Program', l.Lot_ID 'Lot ID', l.Test_Temprature 'Lot Test Temprature', w.Wafer_ID 'Wafer ID', w.Wafer_Start_Time 'Wafer Start Time', w.Wafer_Finish_Time 'Wafer Finish Time', Unit_Number 'Unit Number', X , Y, Head_Number 'Head Number', Site_Number 'Site Number', HBin_Number 'Hard Bin No', SBin_Number 'Soft Bin No', Tests_Executed 'Tests Executed', Test_Time 'Test Time (ms)', DieType_Sequence 'Die Type', IsHomeDie 'Home Die', IsAlignmentDie 'Alignment Die', IsIncludeInYield 'Include In Yield', IsIncludeInDieCount 'Include In Die Count', ReticleNumber 'Reticle Number', ReticlePositionRow 'Reticle Position Row', ReticlePositionColumn 'Reticle Position Column', ReticleActiveSitesCount 'Reticle Active Sites Count', ReticleSitePositionRow 'Reticle Site Position Row', ReticleSitePositionColumn 'Reticle Site Position Column', PartNumber 'Part Number', PartName 'Part Name', DieID 'Die ID', DieName 'Die Name', SINF 'SINF', UserDefinedAttribute1 'User Defined Attribute 1', UserDefinedAttribute2 'User Defined Attribute 2', UserDefinedAttribute3 'User Defined Attribute 3', DieStartTime 'Die Start Time', DieEndTime 'Die End Time', $column_list FROM LOT l
                    JOIN WAFER w ON w.Lot_Sequence = l.Lot_Sequence
                    JOIN TEST_PARAM_MAP tm ON tm.Lot_Sequence = l.Lot_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = w.probing_sequence
                    $this->join_table_clause
                    $this->where_clause
                    ORDER BY l.Lot_ID, w.Wafer_ID";

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