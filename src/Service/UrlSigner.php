<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ralf Lang <ralf.lang@ralf-lang.de>
 */

namespace Horde\Jonah\Service;

use Horde_Url;

/**
 * Injectable HMAC-based URL signing and verification.
 *
 * Ports the signing logic from Horde::signUrl() / Horde::verifySignedUrl()
 * (Core/lib/Horde.php) into a testable, injectable service.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class UrlSigner
{
    /**
     * @param string $secretKey     The HMAC secret ($conf['secret_key']).
     * @param int    $hmacLifetime  Lifetime in minutes ($conf['urls']['hmac_lifetime']).
     */
    public function __construct(
        private readonly string $secretKey,
        private readonly int $hmacLifetime,
    ) {}

    /**
     * Append HMAC timestamp + signature to a URL string.
     *
     * @param string   $url  The URL to sign.
     * @param int|null $now  Current timestamp (override for testing).
     *
     * @return string  The signed URL.
     */
    public function sign(string $url, ?int $now = null): string
    {
        if ($this->secretKey === '') {
            return $url;
        }

        if ($url === '') {
            return $url;
        }

        $now ??= time();

        $url .= str_contains($url, '?') ? '&' : '?';
        $url .= '_t=' . $now . '&_h=';
        $url .= Horde_Url::uriB64Encode(
            hash_hmac('sha1', $url, $this->secretKey, true),
        );

        return $url;
    }

    /**
     * Verify a signed URL and return the original URL without signature.
     *
     * @param string   $data  The signed URL.
     * @param int|null $now   Current timestamp (override for testing).
     *
     * @return string|false  The original URL, or false if invalid.
     */
    public function verify(string $data, ?int $now = null): string|false
    {
        $now ??= time();

        $pos = strrpos($data, '&_h=');
        if ($pos === false) {
            return false;
        }
        $pos += 4;

        $url = substr($data, 0, $pos);
        $hmac = substr($data, $pos);

        $expected = Horde_Url::uriB64Encode(
            hash_hmac('sha1', $url, $this->secretKey, true),
        );
        if (!hash_equals($expected, $hmac)) {
            return false;
        }

        // HMAC valid — now validate timestamp
        parse_str((string) parse_url($url, PHP_URL_QUERY), $values);
        if (((int) ($values['_t'] ?? 0)) + $this->hmacLifetime * 60 < $now) {
            return false;
        }

        // Strip _t and _h from URL
        $pos = strrpos($data, '&_t=');
        if ($pos === false) {
            $pos = strrpos($data, '?_t=');
        }
        if ($pos === false) {
            return false;
        }

        return substr($data, 0, $pos);
    }
}
