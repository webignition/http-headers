<?php

namespace webignition\HttpHeaders;

use webignition\HttpCacheControlDirectives\HttpCacheControlDirectives;
use webignition\HttpCacheControlDirectives\Tokens;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parameter\Parser\AttributeParserException;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use webignition\InternetMediaType\Parser\SubtypeParserException;
use webignition\InternetMediaType\Parser\TypeParserException;

class Headers
{
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var InternetMediaTypeParser
     */
    private $internetMediaTypeParser;

    public function __construct(array $headers = [])
    {
        $this->headers = $this->filter($headers);
        $this->internetMediaTypeParser = new InternetMediaTypeParser();
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

    public function get(string $key): array
    {
        return $this->headers[$key] ?? [];
    }

    public function getLine(string $key): string
    {
        return implode(', ', $this->get($key));
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    public function getLastModified(): ?\DateTime
    {
        $lastModifiedHeaderLine = $this->getLine('last-modified');

        if (empty($lastModifiedHeaderLine)) {
            return null;
        }

        try {
            return new \DateTime($lastModifiedHeaderLine);
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
        $expiresHeaderLine = $this->getLine('expires');

        if ('' === $expiresHeaderLine) {
            return null;
        }

        try {
            return new \DateTime($expiresHeaderLine);
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

        $cacheControlDirectives = new HttpCacheControlDirectives('');

        $cacheControlLines = $this->get('cache-control');
        foreach ($cacheControlLines as $directives) {
            $cacheControlDirectives->addDirectives($directives);
        }

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

    public function getContentType(): ?InternetMediaType
    {
        $contentTypeHeaderLine = trim($this->getLine('content-type'));
        if ('' === $contentTypeHeaderLine) {
            return null;
        }

        try {
            return $this->internetMediaTypeParser->parse($contentTypeHeaderLine);
        } catch (AttributeParserException $e) {
        } catch (SubtypeParserException $e) {
        } catch (TypeParserException $e) {
        }

        return null;
    }

    private function filter(array $headers): array
    {
        $filteredHeaders = [];

        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                $value = $this->filterScalarArray($value);
            }

            if (!is_string($value) && !is_int($value) && !is_array($value)) {
                continue;
            }

            if (is_string($value) || is_int($value)) {
                $value = [$value];
            }

            $key = strtolower($key);

            $filteredHeaders[$key] = $value;
            asort($filteredHeaders);
        }

        return $filteredHeaders;
    }

    private function filterScalarArray(array $array): array
    {
        $filteredArray = [];

        foreach ($array as $value) {
            if (is_string($value) || is_int($value)) {
                $filteredArray[] = $value;
            }
        }

        return $filteredArray;
    }
}
