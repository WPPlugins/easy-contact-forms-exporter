<?php
header("Content-type: application/csv");
header("Content-Disposition: attachment; filename=file.csv");
header("Pragma: no-cache");
header("Expires: 0");

$filename = sys_get_temp_dir() . '/' . $_REQUEST['file'];
$file = fopen($filename, r);
$size = filesize($filename);
$theData = fread($file, $size);
fclose($$file);
echo $theData;