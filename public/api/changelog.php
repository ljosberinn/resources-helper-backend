<?php declare(strict_types=1);

error_reporting(E_ERROR);

$url = 'http://appweb.resources-game.ch/webcontent/news.php' . (isset($_GET['lang']) ? '?lang=' . $_GET['lang'] : '');

$DOM = new DOMDocument();

try {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($curl);
    $DOM->loadHTML($response);
    curl_close($curl);
} catch(Error $error) {
    die($error->getMessage());
}

$divs = [];
foreach($DOM->getElementsByTagName('div') as $div) {
    $divs[] = $div;
}

$newsitems = array_filter($divs, function($div) {
    /* @var DOMDocument|DOMElement $div */
    return $div->getAttribute('class') === 'newsitem';
});

$changelogDiv     = end($newsitems);
$changeLogEntries = $changelogDiv->getElementsByTagName('div');

echo get_inner_html($changeLogEntries[0]);

function get_inner_html($node) {
    $innerHTML = '';

    foreach($node->childNodes as $child) {
        $innerHTML .= $child->ownerDocument->saveXML($child);
    }

    return $innerHTML;
}
