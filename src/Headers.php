<?php

namespace webignition\HttpHeaders;

use webignition\HttpCacheControlDirectives\HttpCacheControlDirectives;
use webignition\HttpCacheControlDirectives\Tokens;

class Headers
{
    /**
     * @var array
     */
    private $headers = [];

    public function __construct(array $headers = [])
    {
        $this->headers = $this->filter($headers);
    }

    public function createHash(): string
    {
        return md5(json_encode($this->headers));
    }

    /**
     * @param string $key
     * @param string|int|null $value
     *
     * @return Headers
     */
    public function withHeader(string $key, $value): Headers
    {
        return new Headers(array_merge($this->headers, $this->filter([$key => $value])));
    }

    /**
     * @param string $key
     *
     * @return string|int|null
     */
    public function get(string $key)
    {
        return $this->headers[$key] ?? null;
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    public function getLastModified(): ?\DateTime
    {
        if (!isset($this->headers['last-modified'])) {
            return null;
        }

        try {
            return new \DateTime($this->headers['last-modified']);
        } catch (\Exception $exception) {
        }

        return null;
    }

    public function getAge(\DateTime $now = null): ?int
    {
        $lastModified = $this->getLastModified();
        if (empty($lastModified)) {
            return null;
        }

        if (empty($now)) {
            $now = new \DateTime();
        }

        return $now->getTimestamp() - $lastModified->getTimestamp();
    }

    /**
     * @return \DateTime|int|null
     */
    public function getExpires()
    {
        if (!isset($this->headers['expires'])) {
            return null;
        }

        try {
            return new \DateTime($this->headers['expires']);
        } catch (\Exception $exception) {
        }

        return -INF;
    }

    public function hasExpired(\DateTime $now = null)
    {
        $expires = $this->getExpires();
        if (null === $expires) {
            return false;
        }

        $cacheControlDirectives = new HttpCacheControlDirectives($this->get('cache-control') ?? '');

        $hasCacheControlMaxAge = $cacheControlDirectives->hasDirective(Tokens::MAX_AGE);
        $hasCacheControlSMaxAge = $cacheControlDirectives->hasDirective(Tokens::S_MAXAGE);

        if ($hasCacheControlMaxAge || $hasCacheControlSMaxAge) {
            return false;
        }

        if (-INF === $expires) {
            return true;
        }

        if (empty($now)) {
            $now = new \DateTime();
        }

        return $now->getTimestamp() >= $expires->getTimestamp();
    }

    private function filter(array $headers): array
    {
        $filteredHeaders = [];

        foreach ($headers as $key => $value) {
            if (!is_string($value) && !is_int($value)) {
                continue;
            }

            $key = strtolower($key);

            $filteredHeaders[$key] = $value;
            asort($filteredHeaders);
        }

        return $filteredHeaders;
    }
}
