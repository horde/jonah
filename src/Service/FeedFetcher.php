<?php

declare(strict_types=1);

/**
 * Copyright 2002-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

namespace Horde\Jonah\Service;

use Jonah_Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Fetches remote feed content via PSR-18 HTTP client.
 *
 * Replaces the static Jonah::readURL() method.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Jonah
 */
class FeedFetcher
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Fetch the body content from a remote URL.
     *
     * @param string $url  The URL to fetch.
     *
     * @return array{body: string, charset?: string}  The response body and
     *                                                 optional charset.
     *
     * @throws Jonah_Exception  On HTTP errors.
     */
    public function fetch(string $url): array
    {
        $request = $this->requestFactory->createRequest('GET', $url);

        try {
            $response = $this->client->sendRequest($request);
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            $this->logger->error('FeedFetcher: request failed for {url}', [
                'url' => $url,
                'exception' => $e,
            ]);
            throw new Jonah_Exception(
                sprintf(_("Could not open %s: %s"), $url, $e->getMessage()),
            );
        }

        $status = $response->getStatusCode();
        if ($status !== 200) {
            $this->logger->warning('FeedFetcher: HTTP {status} for {url}', [
                'status' => $status,
                'url' => $url,
            ]);
            throw new Jonah_Exception(
                sprintf(_("Could not open %s: %s"), $url, (string) $status),
            );
        }

        $body = (string) $response->getBody();
        $result = ['body' => $body];

        $contentType = $response->getHeaderLine('Content-Type');
        $charset = $this->parseCharset($contentType, $body);
        if ($charset !== null) {
            $result['charset'] = $charset;
        }

        return $result;
    }

    /**
     * Extract charset from Content-Type header or XML declaration.
     */
    private function parseCharset(string $contentType, string $body): ?string
    {
        if (preg_match('/;\s*charset="?([^";\s]*)/', $contentType, $match)) {
            return $match[1];
        }
        if (preg_match(
            '/<\?xml[^>]+encoding=["\']?([^"\'\s?]+)[^?].*?>/i',
            $body,
            $match,
        )) {
            return $match[1];
        }

        return null;
    }
}
