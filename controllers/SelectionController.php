<?php
    require_once('libs/Database.php');

    class SelectionController {
        private $conn;

        public function __construct() {
            $database = new Database();
            $this->conn = $database->connect();
        }

        public function getFacilities() 
        {
            $query = "SELECT Facility_ID FROM LOT";

            $facilities = [];
            $stmt = sqlsrv_query($this->conn, $query);
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $facilities[] = $row['Facility_ID'];
            }
            sqlsrv_free_stmt($stmt);

            return $facilities;
        }

        public function getProbingFilter() 
        {
            $filterQuery = "SELECT abbrev, probing_sequence FROM ProbingSequenceOrder";
            
            $abbrev = [];
            $filterStmt = sqlsrv_query($this->conn, $filterQuery);
            while ($row = sqlsrv_fetch_array($filterStmt, SQLSRV_FETCH_ASSOC)) {
                $abbrev[] = ['abbrev' => $row['abbrev'], 'probing_sequence' => $row['probing_sequence']];
            }
            sqlsrv_free_stmt($filterStmt);

            return $abbrev;
        }

        public function getOptions($type, $value)
        {
            switch ($type) {
                case 'work_center':
                    $query = "SELECT Work_Center FROM lot WHERE Facility_ID IN ('" . implode("','", $value) . "')";
                    break;
                case 'device_name':
                    $query = "SELECT Part_Type FROM lot WHERE Work_Center IN ('" . implode("','", $value) . "')";
                    break;
                case 'test_program':
                    $query = "SELECT Program_Name FROM lot WHERE Part_Type IN ('" . implode("','", $value) . "')";
                    break;
                case 'lot':
                    $query = "SELECT Lot_ID FROM lot WHERE Program_Name IN ('" . implode("','", $value) . "')";
                    break;
                case 'wafer':
                    $query = "SELECT wafer.Wafer_ID FROM wafer
                    JOIN lot ON lot.Lot_Sequence = wafer.Lot_Sequence
                    JOIN ProbingSequenceOrder p on p.probing_sequence = wafer.probing_sequence
                    WHERE lot.Lot_ID IN ('" . implode("','", $value) . "')
                    GROUP BY wafer.Wafer_ID
                    ORDER BY wafer.wafer_ID ";
                    break;
                case 'parameter':
                    $query = "SELECT DISTINCT tm.Column_Name, tm.Test_Name, Test_Number
                              FROM TEST_PARAM_MAP tm 
                              JOIN wafer ON wafer.Lot_Sequence = tm.Lot_Sequence 
                              WHERE wafer.Wafer_ID IN ('" . implode("','", $value) . "') 
                              AND tm.Column_Name LIKE 'T%' 
                              AND CAST(SUBSTRING(tm.Column_Name, 2, LEN(tm.Column_Name) - 1) AS INT) BETWEEN 1 AND 1000
                              ORDER BY Test_Number ASC";
                    break;
                default:
                    $query = "";
            }
            
            $options = [];
            if ($query) {
                $stmt = sqlsrv_query($this->conn, $query);
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    if ($type == 'parameter') {
                        $options[] = ['value' => $row['Column_Name'], 'display' => $row['Test_Name']];
                    } else {
                        $options[] = array_values($row)[0];
                    }
                }
                sqlsrv_free_stmt($stmt);
            }
            
            return $options;
        }
    }