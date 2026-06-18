<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Fetches official company logos from the web (website scrape, Clearbit, manifest, image search).
 * Returns null when no confident logo is found — callers should leave the logo blank.
 */
class CompanyLogoFetcher
{
    private const MIN_DIMENSION = 48;
    private const MIN_BYTES     = 400;

    private string $saveDir;
    private string $relativePrefix;

    public function __construct(?string $publicSubdir = 'upload/job_logos')
    {
        $this->relativePrefix = trim($publicSubdir, '/');
        $this->saveDir        = public_path($this->relativePrefix);
        if (!is_dir($this->saveDir)) {
            @mkdir($this->saveDir, 0755, true);
        }
    }

    /**
     * @return array{relative_path:string,url:string,warning:?string}|null
     */
    public function fetch(string $companyName, ?string $websiteUrl = null, ?string $filePrefix = 'joblogo'): ?array
    {
        $companyName = trim($companyName);
        if ($companyName === '') {
            return null;
        }

        $websiteUrl = $this->normalizeUrl($websiteUrl);
        $candidates = $this->buildWebsiteCandidates($companyName, $websiteUrl);

        $imageData   = null;
        $contentType = '';

        foreach ($candidates as $candidateUrl) {
            $parsed  = parse_url($candidateUrl);
            $host    = $parsed['host'] ?? '';
            $domain  = preg_replace('/^www\./i', '', $host);
            $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . $host;

            [$imageData, $contentType] = $this->fetchFromWebsite($candidateUrl, $domain);
            if (!empty($imageData)) {
                break;
            }

            if ($domain) {
                [$imageData, $contentType] = $this->fetchClearbit($domain);
                if (!empty($imageData)) {
                    break;
                }
            }

            [$imageData, $contentType] = $this->fetchCommonPaths($baseUrl);
            if (!empty($imageData)) {
                break;
            }

            [$imageData, $contentType] = $this->fetchFromManifest($baseUrl);
            if (!empty($imageData)) {
                break;
            }

            if ($domain) {
                [$imageData, $contentType] = $this->fetchGoogleFavicon($domain);
                if (!empty($imageData)) {
                    break;
                }
            }
        }

        if (empty($imageData)) {
            [$imageData, $contentType] = $this->fetchFromImageSearch($companyName, $candidates);
        }

        if (empty($imageData)) {
            return null;
        }

        return $this->saveImage($imageData, $contentType, $filePrefix);
    }

    /**
     * @return array{relative_path:string,url:string,warning:?string}|null
     */
    public function saveUploadedFile($file, ?string $filePrefix = 'joblogo'): ?array
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true)) {
            return null;
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        $fileName = $filePrefix . '_' . time() . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
        if (!$file->move($this->saveDir, $fileName)) {
            return null;
        }

        if ($ext !== 'svg') {
            $this->resizeRaster($this->saveDir . '/' . $fileName);
        }

        $relative = $this->relativePrefix . '/' . $fileName;
        return [
            'relative_path' => $relative,
            'url'           => '/' . $relative,
            'warning'       => null,
        ];
    }

    public function isValidStoredPath(?string $relativePath): bool
    {
        if ($relativePath === null || $relativePath === '') {
            return false;
        }
        $relativePath = ltrim($relativePath, '/');
        if (strpos($relativePath, '..') !== false) {
            return false;
        }
        if (strpos($relativePath, $this->relativePrefix . '/') !== 0) {
            return false;
        }
        return is_file(public_path($relativePath));
    }

    /** @return string[] */
    private function buildWebsiteCandidates(string $companyName, ?string $websiteUrl): array
    {
        $out = [];
        if ($websiteUrl) {
            $out[] = $websiteUrl;
        }

        $known = $this->knownWebsite($companyName);
        if ($known) {
            $out[] = $known;
            return array_values(array_unique($out));
        }

        $brand = $this->primaryBrandToken($companyName);
        if ($brand && strlen($brand) >= 4) {
            $tlds = ['com', 'io', 'co', 'app', 'hk'];
            foreach ($tlds as $tld) {
                $guess = "https://{$brand}.{$tld}";
                if ($this->isReachableUrl($guess)) {
                    $out[] = $guess;
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($out)));
    }

    private function isReachableUrl(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY          => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT         => 4,
            CURLOPT_CONNECTTIMEOUT  => 3,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; AIMMI-LogoFetcher/1.0)',
            CURLOPT_SSL_VERIFYPEER  => false,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 400;
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $url = preg_replace('/#.*$/', '', $url);
        $url = rtrim($url, '?&');
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function knownWebsite(string $companyName): ?string
    {
        $compact = preg_replace('/[^a-z0-9]/', '', mb_strtolower($companyName, 'UTF-8'));
        $map = [
            'soqqle'     => 'https://soqqle.com',
            'aimmi'      => 'https://ai-mmi.com',
            'wealthskey' => 'https://wealthskey.com',
            'stripe'     => 'https://stripe.com',
            'notion'     => 'https://notion.so',
            'canva'      => 'https://www.canva.com',
            'airwallex'  => 'https://airwallex.com',
            'deel'       => 'https://www.deel.com',
            'revolut'    => 'https://www.revolut.com',
            'google'     => 'https://www.google.com',
            'microsoft'  => 'https://www.microsoft.com',
            'apple'      => 'https://www.apple.com',
            'monzo'      => 'https://monzo.com',
            'klarna'     => 'https://klarna.com',
        ];
        foreach ($map as $key => $url) {
            if (strpos($compact, $key) !== false) {
                return $url;
            }
        }
        return null;
    }

    private function primaryBrandToken(string $companyName): ?string
    {
        $tokens = $this->distinctiveTokens($companyName);
        return $tokens[0] ?? null;
    }

    private function httpGet($url, int $timeout = 10): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_TIMEOUT         => $timeout,
            CURLOPT_CONNECTTIMEOUT  => min($timeout, 5),
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER  => false,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ct   = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        return [$body, $code, $ct];
    }

    private function isLogoImage($body, $ct, bool $strictSize = true): bool
    {
        if (empty($body)) {
            return false;
        }

        $isSvg = strpos($body, '<svg') !== false || stripos((string) $ct, 'svg') !== false;
        if ($isSvg) {
            return strlen($body) >= 80;
        }

        if (strlen($body) < self::MIN_BYTES) {
            return false;
        }

        $isBinary = (stripos((string) $ct, 'image') !== false)
            || substr($body, 0, 4) === "\x89PNG"
            || substr($body, 0, 3) === "\xFF\xD8\xFF"
            || substr($body, 0, 6) === 'GIF87a' || substr($body, 0, 6) === 'GIF89a'
            || (substr($body, 0, 4) === 'RIFF' && substr($body, 8, 4) === 'WEBP');

        if (!$isBinary) {
            return false;
        }

        if ($strictSize) {
            $size = @getimagesizefromstring($body);
            if ($size && isset($size[0], $size[1]) && ($size[0] < self::MIN_DIMENSION || $size[1] < self::MIN_DIMENSION)) {
                return false;
            }
        }

        return true;
    }

    private function fetchFromWebsite(string $websiteUrl, ?string $domain): array
    {
        [$html, $code] = $this->httpGet($websiteUrl, 10);
        if ($code !== 200 || empty($html)) {
            return [null, ''];
        }

        $parsed  = parse_url($websiteUrl);
        $host    = $parsed['host'] ?? $domain;
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . $host;

        $iconCandidates = $this->extractIconLinksFromHtml($html, $parsed, $baseUrl);
        foreach ($iconCandidates as $logoUrl) {
            [$body, $c, $ct] = $this->httpGet($logoUrl);
            if ($c === 200 && $this->isLogoImage($body, $ct, false)) {
                return [$body, $ct];
            }
        }

        foreach ($this->extractMetaImagesFromHtml($html, $parsed, $baseUrl) as $logoUrl) {
            [$body, $c, $ct] = $this->httpGet($logoUrl);
            if ($c === 200 && $this->isLogoImage($body, $ct, false)) {
                return [$body, $ct];
            }
        }

        $logoUrl = null;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]+(?:id|class|alt)=["\'][^"\']*logo[^"\']*["\'][^>]*>/i', $html, $m)) {
            $logoUrl = $m[1];
        }
        if (!$logoUrl && preg_match('/<img[^>]+(?:id|class|alt)=["\'][^"\']*logo[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            $logoUrl = $m[1];
        }
        if (!$logoUrl && preg_match('/<img[^>]+src=["\']([^"\']+\.(png|svg|jpg|jpeg|gif|webp))["\'][^>]+(?:id|class|alt)=["\'][^"\']*logo[^"\']*["\'][^>]*>/i', $html, $m)) {
            $logoUrl = $m[1];
        }
        if (!$logoUrl && preg_match('/<img[^>]+(?:id|class|alt)=["\'][^"\']*logo[^"\']*["\'][^>]+src=["\']([^"\']+\.(png|svg|jpg|jpeg|gif|webp))["\'][^>]*>/i', $html, $m)) {
            $logoUrl = $m[1];
        }
        if (!$logoUrl && preg_match('/<(?:a|div|span|figure)[^>]+(?:class|id)=["\'][^"\']*logo[^"\']*["\'][^>]*>\s*<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            $logoUrl = $m[1];
        }

        if (!$logoUrl) {
            return [null, ''];
        }

        foreach ($this->expandImageSrcCandidates($logoUrl, $parsed, $baseUrl) as $candidate) {
            [$body, $c, $ct] = $this->httpGet($candidate);
            if ($c === 200 && $this->isLogoImage($body, $ct)) {
                return [$body, $ct];
            }
        }

        return [null, ''];
    }

    /** @return string[] */
    private function extractMetaImagesFromHtml(string $html, array $parsed, string $baseUrl): array
    {
        $found = [];
        $patterns = [
            '/<meta[^>]+property=["\']og:image(?::secure_url)?["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image(?::secure_url)?["\'][^>]*>/i',
            '/<meta[^>]+name=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image(?::src)?["\'][^>]*>/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $url) {
                    $found[] = $this->resolveUrl(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $parsed, $baseUrl);
                }
            }
        }
        return array_values(array_unique($found));
    }

    /** @return string[] */
    private function expandImageSrcCandidates(string $src, array $parsed, string $baseUrl): array
    {
        $candidates = [];
        $resolved = $this->resolveUrl($src, $parsed, $baseUrl);
        $candidates[] = $resolved;

        if (preg_match('/[?&]url=([^&"\']+)/i', $src, $m)) {
            $decoded = urldecode($m[1]);
            $candidates[] = $this->resolveUrl($decoded, $parsed, $baseUrl);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function fetchGoogleFavicon(string $domain): array
    {
        $domain = preg_replace('/^www\./i', '', $domain);
        foreach ([256, 128] as $sz) {
            [$body, $code, $ct] = $this->httpGet("https://www.google.com/s2/favicons?domain={$domain}&sz={$sz}", 6);
            if ($code === 200 && $this->isLogoImage($body, $ct, false)) {
                $size = @getimagesizefromstring($body);
                if ($size && isset($size[0], $size[1]) && $size[0] >= 32 && $size[1] >= 32) {
                    return [$body, $ct ?: 'image/png'];
                }
            }
        }
        return [null, ''];
    }

    /** @return string[] */
    private function extractIconLinksFromHtml(string $html, array $parsed, string $baseUrl): array
    {
        $found = [];
        if (preg_match_all('/<link\b[^>]*>/i', $html, $tags)) {
            foreach ($tags[0] as $tag) {
                $rel = '';
                $href = '';
                $type = '';
                $sizes = 0;
                if (preg_match('/\brel=["\']([^"\']+)["\']/i', $tag, $m)) {
                    $rel = strtolower($m[1]);
                }
                if (preg_match('/\bhref=["\']([^"\']+)["\']/i', $tag, $m)) {
                    $href = $m[1];
                }
                if (preg_match('/\btype=["\']([^"\']+)["\']/i', $tag, $m)) {
                    $type = strtolower($m[1]);
                }
                if (preg_match('/\bsizes=["\'](\d+)x\d+["\']/i', $tag, $m)) {
                    $sizes = (int) $m[1];
                }
                if ($href === '') {
                    continue;
                }
                $isIcon = (strpos($rel, 'icon') !== false) || (strpos($rel, 'apple-touch-icon') !== false) || (strpos($rel, 'mask-icon') !== false);
                if (!$isIcon) {
                    continue;
                }
                $score = $sizes;
                if (stripos($type, 'svg') !== false || preg_match('/\.svg(\?|$)/i', $href)) {
                    $score += 500;
                } elseif (preg_match('/\.png(\?|$)/i', $href)) {
                    $score += 300;
                }
                $found[] = ['href' => $href, 'score' => $score];
            }
        }

        usort($found, fn ($a, $b) => $b['score'] - $a['score']);
        $urls = [];
        foreach ($found as $item) {
            $urls[] = $this->resolveUrl($item['href'], $parsed, $baseUrl);
        }
        return array_values(array_unique($urls));
    }

    private function fetchClearbit(string $domain): array
    {
        [$body, $code, $ct] = $this->httpGet("https://logo.clearbit.com/{$domain}?size=600&format=png");
        if ($code === 200 && $this->isLogoImage($body, $ct)) {
            return [$body, 'image/png'];
        }
        return [null, ''];
    }

    private function fetchCommonPaths(string $baseUrl): array
    {
        $paths = [
            '/apple-touch-icon.png', '/apple-touch-icon-precomposed.png',
            '/favicon-256x256.png', '/favicon-192x192.png',
            '/logo.svg', '/logo.png',
            '/images/logo.png', '/img/logo.png', '/assets/images/logo.png',
            '/assets/logo.png', '/assets/logo.svg',
        ];
        foreach ($paths as $path) {
            [$body, $code, $ct] = $this->httpGet($baseUrl . $path, 4);
            if ($code === 200 && $this->isLogoImage($body, $ct)) {
                return [$body, $ct];
            }
        }
        return [null, ''];
    }

    private function fetchFromManifest(string $baseUrl): array
    {
        $parsed = parse_url($baseUrl);
        foreach ([$baseUrl . '/manifest.json', $baseUrl . '/site.webmanifest'] as $mUrl) {
            [$mBody, $mCode] = $this->httpGet($mUrl, 5);
            if ($mCode !== 200 || empty($mBody)) {
                continue;
            }
            $manifest = json_decode($mBody, true);
            $icons    = $manifest['icons'] ?? [];
            if (empty($icons)) {
                continue;
            }
            usort($icons, function ($a, $b) {
                $sa = isset($a['sizes']) ? (int) explode('x', strtolower($a['sizes']))[0] : 0;
                $sb = isset($b['sizes']) ? (int) explode('x', strtolower($b['sizes']))[0] : 0;
                return $sb - $sa;
            });
            foreach ($icons as $icon) {
                $iconSrc = $icon['src'] ?? '';
                if ($iconSrc === '') {
                    continue;
                }
                $iconSrc = $this->resolveUrl($iconSrc, $parsed, $baseUrl);
                [$body, $code, $ct] = $this->httpGet($iconSrc);
                if ($code === 200 && $this->isLogoImage($body, $ct)) {
                    return [$body, $ct];
                }
            }
        }
        return [null, ''];
    }

    /**
     * @param string[] $websiteCandidates
     */
    private function fetchFromImageSearch(string $companyName, array $websiteCandidates = []): array
    {
        $tokens = $this->distinctiveTokens($companyName);
        if (empty($tokens)) {
            return [null, ''];
        }

        $longTokens = array_values(array_filter($tokens, fn ($t) => strlen($t) >= 4));
        if (empty($longTokens) && empty($websiteCandidates)) {
            return [null, ''];
        }
        $matchTokens = !empty($longTokens) ? $longTokens : $tokens;

        $allowedHosts = [];
        foreach ($websiteCandidates as $u) {
            $h = parse_url($u, PHP_URL_HOST);
            if ($h) {
                $allowedHosts[] = preg_replace('/^www\./i', '', $h);
            }
        }

        $q = urlencode($companyName . ' official logo');
        $ch = curl_init("https://www.bing.com/images/search?q={$q}&qft=+filterui:imagesize-large&first=1&form=IRFLTR");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $bingHtml = curl_exec($ch);
        curl_close($ch);

        if (empty($bingHtml)) {
            return [null, ''];
        }

        $bingDecoded = html_entity_decode($bingHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('/"murl"\s*:\s*"(https?:[^"]+\.(?:png|jpg|jpeg|svg|webp)[^"]{0,300})"/i', $bingDecoded, $mUrls);
        foreach (array_slice($mUrls[1] ?? [], 0, 12) as $imgUrl) {
            $imgUrl = @json_decode('"' . str_replace('"', '\\"', $imgUrl) . '"') ?: $imgUrl;
            if (empty($imgUrl) || !filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                continue;
            }
            $urlLower = mb_strtolower($imgUrl, 'UTF-8');
            $host     = parse_url($imgUrl, PHP_URL_HOST) ?: '';
            $host     = preg_replace('/^www\./i', '', $host);
            $hostLower = mb_strtolower($host, 'UTF-8');

            $tokenHit = false;
            foreach ($matchTokens as $token) {
                if (strpos($hostLower, $token) !== false) {
                    $tokenHit = true;
                    break;
                }
            }
            if (!$tokenHit && !empty($allowedHosts) && in_array($host, $allowedHosts, true)) {
                $tokenHit = true;
            }
            if (!$tokenHit) {
                continue;
            }

            [$body, $code, $ct] = $this->httpGet($imgUrl, 10);
            if ($code === 200 && $this->isLogoImage($body, $ct)) {
                return [$body, $ct];
            }
        }

        return [null, ''];
    }

    /** @return string[] */
    private function distinctiveTokens(string $companyName): array
    {
        $stop = ['limited', 'ltd', 'pty', 'inc', 'corp', 'corporation', 'company', 'co', 'group', 'holdings', 'international', 'the', 'and', 'hong', 'kong', 'sar', 'australia', 'singapore', 'malaysia', 'kingdom', 'united', 'states', 'startup', 'that', 'does', 'not', 'exist', 'fake', 'random'];
        $slug  = mb_strtolower($companyName, 'UTF-8');
        $parts = preg_split('/[^a-z0-9]+/u', $slug);
        $tokens = [];
        foreach ($parts as $p) {
            if (strlen($p) < 3 || in_array($p, $stop, true) || is_numeric($p)) {
                continue;
            }
            $tokens[] = $p;
        }
        return array_values(array_unique($tokens));
    }

    private function resolveUrl(string $url, array $parsed, string $baseUrl): string
    {
        if (strpos($url, '//') === 0) {
            return ($parsed['scheme'] ?? 'https') . ':' . $url;
        }
        if (strpos($url, '/') === 0) {
            return $baseUrl . $url;
        }
        if (strpos($url, 'http') !== 0) {
            return $baseUrl . '/' . $url;
        }
        return $url;
    }

    /**
     * @return array{relative_path:string,url:string,warning:?string}|null
     */
    private function saveImage(string $imageData, string $contentType, string $filePrefix): ?array
    {
        $ext = 'png';
        if (stripos($contentType, 'svg') !== false || strpos($imageData, '<svg') !== false) {
            $ext = 'svg';
        } elseif (stripos($contentType, 'jpeg') !== false || stripos($contentType, 'jpg') !== false) {
            $ext = 'jpg';
        } elseif (stripos($contentType, 'webp') !== false) {
            $ext = 'webp';
        } elseif (stripos($contentType, 'gif') !== false) {
            $ext = 'gif';
        }

        $warning = null;
        if ($ext !== 'svg') {
            $size = @getimagesizefromstring($imageData);
            if ($size && ($size[0] < 64 || $size[1] < 64)) {
                $warning = 'Logo may be low resolution (' . $size[0] . '×' . $size[1] . 'px). Consider uploading a higher-quality image.';
            }
        }

        $fileName = $filePrefix . '_' . time() . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
        $savePath = $this->saveDir . '/' . $fileName;

        if (!file_put_contents($savePath, $imageData)) {
            Log::warning('CompanyLogoFetcher: failed to save logo', ['file' => $fileName]);
            return null;
        }

        if ($ext !== 'svg') {
            $this->resizeRaster($savePath);
        }

        $relative = $this->relativePrefix . '/' . $fileName;
        return [
            'relative_path' => $relative,
            'url'           => '/' . $relative,
            'warning'       => $warning,
        ];
    }

    private function resizeRaster(string $savePath): void
    {
        try {
            \Intervention\Image\Facades\Image::make($savePath)
                ->resize(400, 400, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->save($savePath, 92);
        } catch (\Throwable $e) {
            // keep original
        }
    }
}
