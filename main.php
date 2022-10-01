<?php
/**
* Smalot PDF Parser looses some data when parsing - reason unknown, needs indepth study of Smalot code.
* This part was left in case Smalot fix will be found
*/

if($argc != 2) {
  echo "Usage - main.php [path to file]\n";
  return -1;
}

$filename = $argv[1];

if(!file_exists($filename)) {
  echo "File dos not exist or the path was specified incorrectly. Please provide a valid filepath.\n";
  return -1;
}

shell_exec("pdftotext -layout test/test.pdf");
$text_data = file_get_contents("test/test.txt");

$page_number = 0;

$out = fopen("out.txt", "w");

if(!$out) {
  echo "Coudn't open file for writing\n";
}

$re_match = '/Name|Invoice Number|Invoice Date|Invoice Due Date|Invoice Total|Account Number|Job|AWB|CountryName|Service|Format|Pcs|PerPc
              |PcTotal|Lbs|PerLb|LbTotal|Flat|Pickup|Reference|GrandTotal|[A-Za-z\-]+|[\d\/\.,$]+/';

preg_match_all($re_match, $text_data, $match_data, PREG_PATTERN_ORDER, 0);
$arrays_data = $match_data[0];
//fwrite($out, print_r($arrays_data, true));
//exit(0);

$res_array = Array();

$ind = array_search('Name', $arrays_data);
if($ind !== false) {
  $ind2 = array_search('Invoice Number', $arrays_data);
  $val = "";
  for($i = $ind+1; $i < $ind2; $i++) {
    $val .= $arrays_data[$i] . " ";
  }

  $res_array["Header"]["Name"] = trim($val);
  $res_array["Header"]["Invoice Number"] = $arrays_data[$ind2++];
}

fwrite($out, json_encode($res_array));

