<?php declare(strict_types=1);

namespace ResourcesHelper;

use DateTime;
use Firebase\JWT\JWT;
use Tuupola\Base62;

class Token {

    public static function create(int $id): string {
        $now    = new DateTime();
        $future = new DateTime('now +2 hours');

        $payload = [
            'iat' => $now->getTimestamp(),
            'exp' => $future->getTimestamp(),
            'jti' => (new Base62())->encode(random_bytes(16)),
            'uid' => $id,
        ];

        $secret = $_ENV['jwtSecret'];
        return JWT::encode($payload, $secret, 'HS256');
    }
}
