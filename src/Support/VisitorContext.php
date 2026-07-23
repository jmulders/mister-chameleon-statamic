<?php

namespace MisterChameleon\Statamic\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Builds the per-visitor signal context the decision engine needs, from the
 * incoming request. Privacy-first: only coarse, first-party signals — no PII,
 * no cross-site identifiers.
 *
 * ─── Identity ─────────────────────────────────────────────────────────────────
 *
 *   The primary visitor key is a stable first-party `mc_vid` cookie (random
 *   UUID, 1-year, SameSite=Lax). It lets the platform accumulate behavioural
 *   context — funnel stage, interest, returning-visitor — across pageviews AND
 *   across days, and it does not collide between different people behind the
 *   same IP/User-Agent. It is sent to the platform in `tokens.mc_vid`, which the
 *   decision engine uses as the session key.
 *
 *   The legacy daily fingerprint (sha256 of ip|ua|date) is kept only as a coarse
 *   fallback for the very first request before the cookie has round-tripped.
 */
class VisitorContext
{
    /** First-party visitor-id cookie name. Readable by the client snippet too. */
    public const COOKIE = 'mc_vid';

    public static function fromRequest(Request $request): array
    {
        $ua = (string) $request->userAgent();

        return [
            'fingerprint' => self::fingerprint($request),
            'referrer' => (string) $request->headers->get('referer', ''),
            'utm' => self::utm($request),
            'device' => self::device($ua),
            'is_bot' => self::isBot($ua),
            // Stable first-party id — the platform's primary session key.
            'tokens' => [
                'mc_vid' => self::visitorId($request),
            ],
        ];
    }

    /**
     * Stable, first-party visitor id. Read from the mc_vid cookie; when absent,
     * mint a UUID and queue a 1-year first-party cookie so the id persists across
     * visits. Contains no PII and is never shared across sites.
     */
    protected static function visitorId(Request $request): string
    {
        $existing = $request->cookie(self::COOKIE);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $id = (string) Str::uuid();

        // name, value, minutes, path, domain, secure, httpOnly, raw, sameSite.
        // httpOnly=false so the client snippet (client/hybrid mode) can read the
        // SAME id; secure only on HTTPS; SameSite=Lax (first-party).
        Cookie::queue(cookie(
            self::COOKIE,
            $id,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'lax'
        ));

        return $id;
    }

    protected static function fingerprint(Request $request): string
    {
        // Anonymous, non-reversible, rotates daily. Coarse fallback only.
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
