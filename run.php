<?php

require_once './Parser.php';
$pathToLogFile = (@$argv[1]) ? $argv[1] : './sample.log';
$pathToOutputFile = (@$argv[2]) ? $argv[2] : './report.txt';

$parser = new Parser();
echo "Processing...";
$stats = $parser->readLog($pathToLogFile);
$parser->generateStatReport($pathToOutputFile, $stats, true);//and release memory as well
echo "\n\rOutput Generated :)\n\r";