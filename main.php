<?php
/**
* Smalot PDF Parser looses some data when parsing - reason unknown, needs indepth study of Smalot code.
* This part was left in case Smalot fix will be found
*/

if($argc != 2) {
  echo "Usage - main.php [path to file]\n";
  return -1;
}

$filepath = $argv[1];

if(!file_exists($filepath)) {
  echo "File does not exist or the path was specified incorrectly. Please provide a valid filepath.\n";
  return -1;
}

$pathinfo = pathinfo($filepath);
$path = $pathinfo["dirname"];
$filename = $pathinfo["filename"];
$read_path = $path."/".$filename.".txt";
$result_path = "./json_encoded_result.txt";

//Convert pdf to text using poppler-utils pdftotext, keeping the layout
shell_exec("pdftotext -layout $filepath");
if(!file_exists($read_path)) {
  echo "Something went wrong and the file conversion failed. Please try fileconversion outside this script with following command: pdftotext -layout [path to file]\n";
  return -2;
}

$res = Array();

$toRead = fopen($read_path, "r");

if(!$toRead) {
  echo "Coudn't open file for reading\n";
  return -3;
}

$table_found = false;
$i = 0;
while( ($line = fgets($toRead)) !== false ) {
  if(!isset($res["Header"]["Name"]) && count($parts = preg_split("/\bName\b/", $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)) == 2) {
    $res["Header"]["Name"] = trim($parts[1]);
    continue;
  } else if(!isset($res["Invoice Number"]) && count($parts = preg_split("/\bInvoice Number\b/", $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)) == 2) {
    $res["Header"]["Invoice Number"] = trim($parts[1]);
    continue;
  } else if(!isset($res["Header"]["Invoice Date"]) && count($parts = preg_split("/\bInvoice Date\b/", $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)) == 2) {
    $res["Header"]["Invoice Date"] = trim($parts[1]);
    continue;
  } else if(!isset($res["Header"]["Invoice Due Date"]) && count($parts = preg_split("/\bInvoice Due Date\b/", $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)) == 2) {
    $res["Header"]["Invoice Due Date"] = trim($parts[1]);
    continue;
  } else if(!isset($res["Header"]["Invoice Total"]) && count($parts = preg_split("/\bInvoice Total\b/", $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)) == 2) {
    $res["Header"]["Invoice Total"] = trim($parts[1]);
    continue;
  } else if(!isset($res["Header"]["Account Number"]) && count($parts = preg_split("/\bAccount Number\b/", $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)) == 2) {
    $res["Header"]["Account Number"] = trim($parts[1]);
    continue;
  }

  if(count($res["Header"]) != 6) {
    echo "Header parsed incorrectly. Check parsing or chech file\n";
    return -4;
  }

  if(!$table_found && preg_match("/Job/", $line)) {
    $parts = preg_split("/\s{2,}/", $line, -1, PREG_SPLIT_NO_EMPTY);
    $table_found = true;

    foreach($parts as $part) {
      $res["Table"]["$part"] = Array();
    }
    continue;
  } else if(!$table_found) {
    continue;
  } else if($table_found && preg_match("/Job/", $line)) {
    continue;
  } else if($table_found && $parts = preg_split("/\s{2,}/", $line, -1, PREG_SPLIT_NO_EMPTY)) {
    if(count($parts) == 14 || count($parts) == 15) {
      $j = 0;
      foreach($res["Table"] as $key => $value) {
        if(count($parts) == 14 && $key == "Reference") {
          $res["Table"]["Reference"]["$i"] = "";
          continue;
        }
        $res["Table"]["$key"]["$i"] = $parts[$j];
        $j++;
      }
      $i++;
    }
    continue;
  } else {
    continue;
  }
}

fclose($toRead);

$out = fopen($result_path, "w");
if(!$out) {
  echo "Couldn't open result file for writing\n";
  return - 5;
}

fwrite($out, json_encode($res));
fclose($out);
