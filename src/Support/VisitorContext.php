<?php

namespace MisterChameleon\Statamic\Support;

use Illuminate\Http\Request;

/**
 * Builds the per-visitor signal context the decision engine needs, from the
 * incoming request. Privacy-first: only coarse, first-party signals — no PII,
 * no cross-site identifiers. The fingerprint is a rotating, anonymous hash.
 */
class VisitorContext
{
    public static function fromRequest(Request $request): array
    {
        $ua = (string) $request->userAgent();

        return [
            'fingerprint' => self::fingerprint($request),
            'referrer' => (string) $request->headers->get('referer', ''),
            'utm' => self::utm($request),
            'device' => self::device($ua),
            'is_bot' => self::isBot($ua),
            'tokens' => [], // first-party signal tokens are merged in client-side
        ];
    }

    protected static function fingerprint(Request $request): string
    {
        // Anonymous, non-reversible, rotates daily. Enough to keep a single
        // visitor's variant stable within a session without tracking identity.
        return substr(hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent(),
            now()->format('Y-m-d'),
        ])), 0, 24);
    }

    protected static function utm(Request $request): array
    {
        $out = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $k) {
            if ($request->filled($k)) {
                $out[str_replace('utm_', '', $k)] = (string) $request->query($k);
            }
        }
        return $out;
    }

    protected static function device(string $ua): string
    {
        if (preg_match('/mobile|android|iphone|ipod/i', $ua)) return 'mobile';
        if (preg_match('/ipad|tablet/i', $ua)) return 'tablet';
        return 'desktop';
    }

    protected static function isBot(string $ua): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|quora|pinterest|vkshare|w3c_validator|lighthouse/i', $ua);
    }
}
