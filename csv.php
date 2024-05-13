<?php


function parse_csv($filename) {
    $data = []; 
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $header = fgetcsv($handle); 
        while (($row = fgetcsv($handle)) !== FALSE) {
            $record = []; 
            foreach ($row as $index => $value) {
                if (strpos($value, "\n") !== FALSE) {
                    $lines = explode("\n", $value);
                    foreach ($lines as &$line) {
                        $line = trim($line);
                    }
                    $value = implode("\n", $lines);
                }
                $record[$header[$index]] = $value;
            }
            $data[] = $record;
        }
        fclose($handle); 
    }
    return $data; 
}







?>