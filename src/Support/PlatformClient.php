<?php

namespace MisterChameleon\Statamic\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the Mister Chameleon decision-engine API.
 *
 * Contract (POST {api_url}/api/v1/slot):
 *   headers: { Authorization: "Bearer <tenant_key>", Content-Type: application/json }
 *   body: {
 *     slot_type:            "hero" | "proof" | "cta" | ...,
 *     default_variant_key:  string,        // CMS-authored fallback
 *     page:                 { collection, slug, locale },
 *     visitor: {
 *       fingerprint, referrer, utm: {...}, geo, device, is_bot,
 *       tokens: {...}                      // first-party signal tokens
 *     }
 *   }
 *   200 → {
 *     variant_key: string,
 *     is_default:  boolean,
 *     content:     object,                 // resolved field values for the slot
 *     experiment:  { id, variant } | null
 *   }
 *
 * Never throws to the template: on timeout/error it returns null so the caller
 * renders the CMS-authored default. Personalisation must never block a render.
 */
class PlatformClient
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $tenantKey,
        protected float $timeout = 1.5,
        protected int $cacheTtl = 60,
    ) {
    }

    /**
     * Resolve a slot variant for the current visitor.
     *
     * @return array{variant_key:string,is_default:bool,content:array,experiment:?array}|null
     */
    public function resolveSlot(string $slotType, string $defaultVariantKey, array $page, array $visitor): ?array
    {
        if (! $this->tenantKey) {
            return null; // not configured → fall back to CMS default
        }

        // Bots and the same visitor+slot within the TTL reuse one decision so
        // the page stays stable for crawlers and repeat paints.
        $cacheKey = 'mc:slot:' . md5(implode('|', [
            $slotType,
            $defaultVariantKey,
            $page['slug'] ?? '',
            $visitor['fingerprint'] ?? '',
        ]));

        if ($this->cacheTtl > 0 && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $res = Http::withToken($this->tenantKey)
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($this->baseUrl, '/') . '/api/v1/slot', [
                    'slot_type' => $slotType,
                    'default_variant_key' => $defaultVariantKey,
                    'page' => $page,
                    'visitor' => $visitor,
                ]);

            if (! $res->ok()) {
                return null;
            }

            $data = $res->json();
            if (! is_array($data) || ! isset($data['variant_key'])) {
                return null;
            }

            $resolved = [
                'variant_key' => (string) $data['variant_key'],
                'is_default' => (bool) ($data['is_default'] ?? true),
                'content' => (array) ($data['content'] ?? []),
                'experiment' => $data['experiment'] ?? null,
            ];

            if ($this->cacheTtl > 0) {
                Cache::put($cacheKey, $resolved, $this->cacheTtl);
            }

            return $resolved;
        } catch (\Throwable $e) {
            Log::debug('[mister-chameleon] slot resolve failed: ' . $e->getMessage());
            return null;
        }
    }
}
