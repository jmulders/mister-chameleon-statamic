{{--
    Live Preview bridge view (rendered by the addon's /mc-live-preview route).

    Single iframe (proven to display inside the Statamic CP). It POSTs the
    current unsaved blocks to the Next.js draft store and points the iframe at
    the lightweight /mc-preview route (?mcdraft=TOKEN).

    Change detection: Statamic postMessage + polling the parent CP's reactive
    publish-form values, deduped by a content fingerprint.

    The loading text is hidden once the preview reports it has rendered
    (mc-preview-ready postMessage) or on iframe load.
--}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live Preview</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #fff; }
        #mc-preview { display: block; width: 100%; height: 100%; border: 0; }
        #mc-status  { position: absolute; top: 0; left: 0; right: 0; font: 13px/1.5 system-ui, -apple-system, sans-serif; color: #6b7280; padding: 16px; background: #fff; }
    </style>
</head>
<body>
    <script type="application/json" id="mc-draft-data">{!! json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <iframe id="mc-preview" title="Live preview"></iframe>
    <div id="mc-status">Live preview laden…</div>

    <script>
    (function () {
        var BASE = @json($base);
        var PATH = @json($path);
        var DEBUG = true;
        function log() { if (!DEBUG) return; try { var a = [].slice.call(arguments); a.unshift('[mc-bridge]'); console.log.apply(console, a); } catch (e) {} }

        var payload = null;
        try { payload = JSON.parse(document.getElementById('mc-draft-data').textContent); } catch (e) {}

        var frame  = document.getElementById('mc-preview');
        var status = document.getElementById('mc-status');
        var shownOnce = false;

        function hideStatus() { if (status) status.style.display = 'none'; }
        frame.addEventListener('load', function () { if (shownOnce) hideStatus(); });

        function fp(blocks, title) {
            try { return JSON.stringify(blocks || []) + '|' + (title || ''); } catch (e) { return String(Math.random()); }
        }
        var lastFp = payload ? fp(payload.pageBlocks, payload.title) : '';

        // POST blocks to the Next.js draft store, then point the iframe at the
        // resulting preview URL. Sequence guard so only the newest edit wins.
        var renderSeq = 0;
        function render(p) {
            var seq = ++renderSeq;
            log('render (' + ((p.pageBlocks || []).length) + ' blocks)');
            return fetch(BASE + '/api/statamic-draft', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(p)
            })
            .then(function (r) { log('POST', r.status); return r.ok ? r.json() : null; })
            .then(function (resp) {
                if (seq !== renderSeq) return;
                if (resp && resp.token) {
                    frame.src = BASE + '/mc-preview?mcdraft=' + encodeURIComponent(resp.token);
                } else if (!shownOnce) {
                    frame.src = BASE + PATH;
                }
            })
            .catch(function (e) { log('render error', e && e.message); });
        }

        // The preview page signals it has rendered → hide the loading text.
        window.addEventListener('message', function (e) {
            var msg = e.data;
            if (!msg || typeof msg !== 'object') return;
            if (msg.name === 'mc-preview-ready') { shownOnce = true; hideStatus(); return; }
            // Statamic postMessage (when emitted) → live update.
            var isUpdate = msg.name === 'statamic.preview.updated' || msg.type === 'statamic.preview.updated';
            var token = msg.token || (msg.data && msg.data.token);
            if (isUpdate && token) {
                fetch('/mc-live-preview-data?token=' + encodeURIComponent(token), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (pl) { if (pl && !pl.error) maybeRender(pl.pageBlocks, pl.title, pl.seoDescription); })
                .catch(function () {});
            }
        });

        // Safety: hide the loading text after a few seconds even if no ready
        // signal arrives (e.g. fallback page), so it never sticks forever.
        setTimeout(function () { shownOnce = true; hideStatus(); }, 6000);

        var debTimer = null;
        function maybeRender(blocks, title, seo) {
            if (!Array.isArray(blocks)) return;
            var f = fp(blocks, title);
            if (f === lastFp) return;
            lastFp = f;
            log('change detected');
            if (debTimer) clearTimeout(debTimer);
            debTimer = setTimeout(function () {
                render({
                    collection:     payload ? payload.collection : 'pages',
                    slug:           payload ? payload.slug : 'home',
                    title:          title !== undefined ? title : (payload ? payload.title : ''),
                    seoDescription: seo   !== undefined ? seo   : (payload ? payload.seoDescription : null),
                    pageBlocks:     blocks
                });
            }, 400);
        }

        // ── Initial render ────────────────────────────────────────────────────
        if (!payload) { frame.src = BASE + PATH; } else { render(payload); }

        // ── Poll the parent CP's reactive publish-form values ─────────────────
        function scanProvides(prov) {
            if (!prov || typeof prov !== 'object') return null;
            try {
                for (var key in prov) {
                    try {
                        var val = prov[key];
                        if (!val || typeof val !== 'object') continue;
                        if (Array.isArray(val.page_blocks)) return val;
                        if (val.values && Array.isArray(val.values.page_blocks)) return val.values;
                        if (val.__v_isRef && val.value) {
                            if (Array.isArray(val.value.page_blocks)) return val.value;
                            if (val.value.values && Array.isArray(val.value.values.page_blocks)) return val.value.values;
                        }
                        if (val.values && val.values.__v_isRef) {
                            var vv = val.values.value;
                            if (vv && Array.isArray(vv.page_blocks)) return vv;
                        }
                    } catch (ex) {}
                }
            } catch (e) {}
            return null;
        }
        function searchInstance(inst, depth) {
            if (!inst || depth > 30) return null;
            try {
                var f = scanProvides(inst.provides);
                if (f) return f;
                var ss = inst.setupState;
                if (ss && typeof ss === 'object') {
                    if (Array.isArray(ss.page_blocks)) return ss;
                    if (ss.values && Array.isArray(ss.values.page_blocks)) return ss.values;
                }
                if (inst.subTree) return walkVNode(inst.subTree, depth + 1);
            } catch (e) {}
            return null;
        }
        function walkVNode(vnode, depth) {
            if (!vnode || depth > 30) return null;
            try {
                if (vnode.component) { var r = searchInstance(vnode.component, depth); if (r) return r; }
                var ch = vnode.children;
                if (Array.isArray(ch)) {
                    for (var i = 0; i < ch.length && i < 40; i++) {
                        if (ch[i] && typeof ch[i] === 'object') { var r2 = walkVNode(ch[i], depth + 1); if (r2) return r2; }
                    }
                }
            } catch (e) {}
            return null;
        }
        function findViaDom(doc) {
            try {
                var sels = ['[data-fieldtype="replicator"]', '[class*="replicator"]', '.publish-fields', '.publish-form', 'form'];
                for (var s = 0; s < sels.length; s++) {
                    var els = doc.querySelectorAll(sels[s]);
                    for (var ei = 0; ei < els.length && ei < 12; ei++) {
                        var cur = els[ei].__vueParentComponent;
                        for (var d = 0; d < 30 && cur; d++) {
                            var f = scanProvides(cur.provides);
                            if (f) return f;
                            var ss = cur.setupState;
                            if (ss && typeof ss === 'object') {
                                if (Array.isArray(ss.page_blocks)) return ss;
                                if (ss.values && Array.isArray(ss.values.page_blocks)) return ss.values;
                            }
                            cur = cur.parent;
                        }
                    }
                }
            } catch (e) {}
            return null;
        }
        var _cached = null, _warned = false;
        function findValues() {
            if (_cached && Array.isArray(_cached.page_blocks)) return _cached;
            _cached = null;
            try {
                var p = window.parent;
                if (!p || !p.Statamic || !p.Statamic.$app) return null;
                var app = p.Statamic.$app;
                var root = app._instance;
                if (!root && app._container && app._container._vnode && app._container._vnode.component) root = app._container._vnode.component;
                if (!root) { var el = p.document.querySelector('[data-v-app]'); if (el && el._vnode && el._vnode.component) root = el._vnode.component; }
                if (root) _cached = searchInstance(root, 0);
                if (!_cached) _cached = findViaDom(p.document);
                if (_cached) { log('reactive values found (' + _cached.page_blocks.length + ' blocks)'); _warned = false; }
                else if (!_warned) { log('reactive values not found yet'); _warned = true; }
            } catch (e) {}
            return _cached;
        }
        function poll() { var v = findValues(); if (v) maybeRender(v.page_blocks, v.title, v.seo_description); }

        setInterval(poll, 600);
        try {
            var pdoc = window.parent.document;
            if (pdoc && pdoc.body) {
                new MutationObserver(poll).observe(pdoc.body, { childList: true, subtree: true, characterData: true });
                log('MutationObserver wired');
            }
        } catch (e) {}
    })();
    </script>
</body>
</html>
