<?php declare(strict_types=1);

require_once '../_boot.php';

header('Content-type: application/json');

$lang = $_GET['lang'];

$localization = new Localization($lang);

if(empty($_POST)) {
    echo $localization->get();
    die;
}

if(isset($_POST['_t']) && count($_POST) === 2) {
    echo json_encode(['success' => $localization->add($_POST)]);
}
