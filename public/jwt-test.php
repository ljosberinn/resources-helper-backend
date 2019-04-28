<?php declare(strict_types=1);

require_once '_boot.php';

use \Firebase\JWT\JWT;

$key = "example_key";

$now = time();

$token = [
    "iss" => "https://resources-helper.de", // issuer
    "aud" => "https://resources-helper.de", // audience
    "exp" => $now + 30 * 60 * 60,           // expiration
    "nbf" => $now - 5 * 60 * 60,            // not before
    "iat" => $now,                          // issued at
];

/**
 * IMPORTANT:
 * You must specify supported algorithms for your application. See
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
 * for a list of spec-compliant algorithms.
 */
$jwt = JWT::encode($token, $key);
echo $jwt . '<br>';
$decoded = JWT::decode($jwt, $key, ['HS256']);

print_r($decoded);

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/

$decoded_array = (array) $decoded;

/**
 * You can add a leeway to account for when there is a clock skew times between
 * the signing and verifying servers. It is recommended that this leeway should
 * not be bigger than a few minutes.
 *
 * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
 */
JWT::$leeway = 60; // $leeway in seconds
$decoded     = JWT::decode($jwt, $key, ['HS256']);

