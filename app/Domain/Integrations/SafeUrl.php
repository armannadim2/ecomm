<?php

namespace App\Domain\Integrations;

/**
 * Webhook URLs must point to the public internet: no localhost, private networks or
 * cloud metadata addresses (protects the server from being used to reach internal services).
 * Local and test environments may use http and private hosts.
 */
final class SafeUrl
{
    public static function check(string $url): ?string
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? null;

        if ($host === null || ! in_array($scheme, ['https', 'http'], true)) {
            return 'La dirección debe empezar por https://';
        }

        if (! app()->environment('production')) {
            return null;
        }

        if ($scheme !== 'https') {
            return 'Usa una dirección https:// (conexión cifrada).';
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if ($ips === []) {
            return 'No se encuentra el servidor de esa dirección.';
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'La dirección apunta a una red interna y no está permitida.';
            }
        }

        return null;
    }
}
