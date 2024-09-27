<?php
// Enable error reporting for all types of errors
error_reporting(E_ALL);

// Initialize arrays to hold different parts of the SQL file
$structure = [];
$data = [];
$finals = [];
$game_transactions_cache = false;

// Open the SQL file for reading
$file = new SplFileObject("data/dbfull.sql");

// Set the initial status to 'structure'
$status = 'structure'; // Possible values: structure, insert, finalize

// Loop until the end of the file is reached
while (!$file->eof()) {
  // Read one line from the file
  $line = $file->fgets();

  // Skip empty lines or lines that are comments
  if (strlen(trim($line)) == 0 or strpos(trim($line), '--') === 0) {
    continue;
  }

  // Handle lines based on the current status
  if ($status == 'finalize') {
    // If in finalize status, add the line to finals array
    $finals[] = $line;
  } else {
    // Check if the line indicates the start of an INSERT or ALTER TABLE statement
    if (strpos(trim($line), 'INSERT INTO') === 0) {
      $status = 'insert';
    } elseif (strpos(trim($line), 'ALTER TABLE') === 0) {
      $status = 'finalize';
    }

    // Add lines to the appropriate array based on the current status
    if ($status == 'structure') {
      // Remove DEFINER clause from CREATE PROCEDURE/FUNCTION statements
      $line = preg_replace('/CREATE (DEFINER=`.*`) (PROCEDURE|FUNCTION)/', 'CREATE $2', $line);
      $structure[] = $line;
    } elseif ($status == 'insert') {
      $data[] = $line;
    } elseif ($status == 'finalize') {
      $finals[] = $line;
    }

    // If in insert status, check if the current line ends an INSERT statement
    if ($status == 'insert') {
      $endchars = substr(trim($line), -2);
      if ($endchars ==  ');') {
        // If the line ends an INSERT statement, switch back to structure status
        $status = 'structure';
      }
    }
  }
}

// Close the file by unsetting the file object
$file = null;

// Open files for writing the separated SQL parts
$structure_file = fopen('data/db1structure.sql', 'w');
$data_file = fopen('data/db2data.sql', 'w');

// Write the structure part to the structure file
fputs($structure_file, "-- Structure starts here \n\n");
foreach ($structure as $line) {
  fputs($structure_file, $line);
}

// Write the final queries to the structure file
fputs($structure_file, "-- Final queries starts here \n\n");
foreach ($finals as $line) {
  fputs($structure_file, $line);
}

// Write the data part to the data file
fputs($data_file, "-- Data starts here \n\n");
foreach ($data as $line) {
  fputs($data_file, $line);
}

// Close the opened files
fclose($structure_file);
fclose($data_file);
