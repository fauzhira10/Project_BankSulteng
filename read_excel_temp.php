<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = __DIR__ . '/public/KELUAHAN NASABAH START 24 AGST 26 (1).xlsx';
if (!file_exists($inputFile)) {
    echo "File not found: " . $inputFile;
    exit;
}

try {
    $spreadsheet = IOFactory::load($inputFile);
    echo "Sheets available:\n";
    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        echo "- " . $sheetName . "\n";
    }

    $targetSheet = 'LOKAL';
    $sheet = $spreadsheet->getSheetByName($targetSheet);

    if (!$sheet) {
        echo "Sheet '$targetSheet' not found.\n";
        exit;
    }

    echo "\n--- Isi dari tab '$targetSheet' ---\n";
    $highestRow = $sheet->getHighestRow();
    // Kita batasi baca sampai 100 baris agar tidak membebani
    $maxRow = min(100, $highestRow); 
    
    // Asumsi kolom tidak lebih dari Z (A-Z) untuk template standar
    $highestColumn = $sheet->getHighestColumn();
    // Jika highestColumn lebih panjang dari Z (misal AA), kita pakai iterasi indeks
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    for ($row = 1; $row <= $maxRow; $row++) {
        $rowHasData = false;
        $rowData = [];
        for ($colIndex = 1; $colIndex <= $highestColumnIndex; $colIndex++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $cellValue = $sheet->getCell($colLetter . $row)->getCalculatedValue();
            $cellValue = (string)$cellValue;
            
            if (trim($cellValue) !== '') {
                // Bersihkan baris baru
                $cleanVal = str_replace(["\r", "\n"], " ", trim($cellValue));
                $rowData[] = "$colLetter$row: " . $cleanVal;
                $rowHasData = true;
            }
        }
        if ($rowHasData) {
            echo "Baris $row => " . implode(" | ", $rowData) . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error reading file: " . $e->getMessage();
}
