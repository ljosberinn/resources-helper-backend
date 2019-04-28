<?php declare(strict_types=1);

require_once '../_boot.php';

header('Content-type: application/json');

echo (new Localization($_GET['lang']))->get();
