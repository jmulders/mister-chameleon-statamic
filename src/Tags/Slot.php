<?php

namespace MisterChameleon\Statamic\Tags;

use Statamic\Tags\Tags;
use MisterChameleon\Statamic\Support\PlatformClient;
use MisterChameleon\Statamic\Support\VisitorContext;

/**
 * {{ mc:slot type="hero" default="hero_default" }}
 *
 * Resolves a personalised context-slot variant for the current visitor via the
 * platform decision engine and renders it. Three rendering paths, picked by
 * config('mister_chameleon.mode'):
 *
 *   edge   — resolve server-side now, emit final HTML (best SEO, no flash).
 *   client — emit the CMS default wrapped in a swap marker; the JS snippet
 *            asks the platform and swaps in the browser (cache/CDN friendly).
 *   hybrid — server-side default + a data-attribute so the snippet can refine.
 *
 * On any failure it renders the CMS-authored default variant — personalisation
 * never blocks or breaks a page.
 */
class Slot extends Tags
{
    public function index()
    {
        $slotType = (string) ($this->params->get('type') ?? '');
        if ($slotType === '') {
            return '';
        }

        $default = (string) ($this->params->get('default') ?? $slotType . '_default');
        $mode = (string) config('mister_chameleon.mode', 'edge');

        $page = [
            'collection' => (string) ($this->context->get('collection') ?? 'pages'),
            'slug' => (string) ($this->context->get('slug') ?? ''),
            'locale' => (string) ($this->context->get('site') ?? app()->getLocale()),
            // Editor-set meta keywords for interest-profile scoring (mirrors what
            // the JS snippet reads from <meta name="keywords">).
            'keywords' => $this->pageKeywords(),
        ];

        $visitor = VisitorContext::fromRequest(request());

        // Client-only mode: never call the platform server-side. Emit the CMS
        // default inside a swap marker the snippet upgrades in the browser.
        if ($mode === 'client') {
            return $this->wrap($slotType, $default, $this->renderVariant($slotType, $default, []), $mode);
        }

        // edge / hybrid: bots always get the stable default.
        $resolved = ($visitor['is_bot'] && config('mister_chameleon.bot_default', true))
            ? null
            : app(PlatformClient::class)->resolveSlot($slotType, $default, $page, $visitor);

        $variantKey = $resolved['variant_key'] ?? $default;
        $content = $resolved['content'] ?? [];

        $html = $this->renderVariant($slotType, $variantKey, $content);

        return $this->wrap($slotType, $variantKey, $html, $mode);
    }

    /**
     * Render a slot variant through the platform-managed block partial. The
     * partial is provisioned by the platform (single source of truth), so the
     * markup matches the live site exactly.
     */
    /**
     * The page's editor-set meta keywords, normalised to a lowercased array.
     * Looks in the common fields (keywords / seo_keywords / meta_keywords) and
     * the SEO Pro `seo` array. Empty when the page has none.
     */
    protected function pageKeywords(): array
    {
        $raw = $this->context->get('keywords')
            ?? $this->context->get('seo_keywords')
            ?? $this->context->get('meta_keywords');

        if (! $raw) {
            $seo = $this->context->get('seo');
            if (is_array($seo) && isset($seo['keywords'])) {
                $raw = $seo['keywords'];
            }
        }

        if (! $raw) {
            return [];
        }

        $parts = is_array($raw) ? $raw : preg_split('/,+/', (string) $raw);

        return array_values(array_filter(array_map(
            fn ($k) => strtolower(trim((string) $k)),
            $parts ?: [],
        )));
    }

    protected function renderVariant(string $slotType, string $variantKey, array $content): string
    {
        $view = "mister-chameleon::blocks.context_slot";

        return (string) view()->exists($view)
            ? view($view, [
                'slot_type' => $slotType,
                'variant_key' => $variantKey,
                'content' => $content,
            ])->render()
            : '';
    }

    /** Wrap output so the client snippet can locate and (in client/hybrid) swap it. */
    protected function wrap(string $slotType, string $variantKey, string $html, string $mode): string
    {
        $attrs = sprintf(
            'data-mc-slot="%s" data-mc-variant="%s" data-mc-mode="%s"',
            e($slotType),
            e($variantKey),
            e($mode),
        );

        return "<div {$attrs}>{$html}</div>";
    }
}
