<?php declare(strict_types=1);

require_once '../_boot.php';

header('Content-type: application/json');

if(!isset($_GET['lang'])) {
    echo json_encode(['error' => 'missing locale']);
    die;
}

$lang = $_GET['lang'];

$localization = new Localization($lang);

if(!isset($_GET['add'])) {
    echo $localization->get();
    die;
}

if(isset($_POST['_t'], $_GET['add']) && count($_POST) === 2) {
    echo json_encode(['success' => $localization->add($_POST)]);
}
