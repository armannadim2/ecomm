<?php

namespace App\Domain\Payments\Gateways\Redsys;

/**
 * Redsys "HMAC_SHA256_V1" signature (Redsys integration guide, "Firma de la operación"):
 *   1. key_order = 3DES-CBC(Ds_Order, base64_decode(secret key), IV = 8 zero bytes, zero padding)
 *   2. signature = base64(HMAC-SHA256(Ds_MerchantParameters, key_order))
 * Notifications use the URL-safe base64 alphabet (- and _), so both sides are normalised before comparing.
 */
final class RedsysSignature
{
    public const VERSION = 'HMAC_SHA256_V1';

    public static function encodeParameters(array $parameters): string
    {
        return base64_encode((string) json_encode($parameters, JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, mixed> */
    public static function decodeParameters(string $encoded): array
    {
        $json = base64_decode(strtr($encoded, '-_', '+/'), true);
        $data = $json === false ? null : json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    public static function sign(string $encodedParameters, string $order, string $secretKey): string
    {
        $key = base64_decode($secretKey, true);

        if ($key === false || $key === '') {
            throw new \InvalidArgumentException('Invalid Redsys secret key.');
        }

        $length = (int) (ceil(strlen($order) / 8) * 8);
        $orderKey = openssl_encrypt(str_pad($order, $length, "\0"), 'des-ede3-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, "\0\0\0\0\0\0\0\0");

        return base64_encode(hash_hmac('sha256', $encodedParameters, (string) $orderKey, true));
    }

    public static function matches(string $expected, string $given): bool
    {
        return hash_equals(strtr($expected, '-_', '+/'), strtr($given, '-_', '+/'));
    }
}
