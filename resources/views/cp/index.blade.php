@extends('statamic::layout')
@section('title', 'Mister Chameleon')

@section('content')
    <header class="mb-6 flex items-center">
        <img src="{{ $dashboardUrl }}/downloads/mc-plugin-icon.svg" alt="" class="w-10 h-10 rounded-lg mr-3" onerror="this.style.display='none'">
        <div class="flex-1">
            <h1 class="mb-1">Mister Chameleon</h1>
            <p class="text-gray text-sm">Per-visitor personalisation for Statamic, powered by the Mister Chameleon platform.</p>
        </div>
        <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener" class="btn-primary">Open platform dashboard</a>
    </header>

    <div class="card p-0 mb-4">
        <div class="flex items-center justify-between p-4 border-b">
            <h2 class="font-bold">Connection</h2>
            @if ($configured)
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-700">Connected</span>
            @else
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-red-100 text-red-700">Not configured</span>
            @endif
        </div>
        <div class="p-4">
            @unless ($configured)
                <p class="text-sm text-gray-700 mb-3">
                    No tenant key is set yet. Add <code>MISTER_CHAMELEON_TENANT_KEY</code> to your
                    <code>.env</code> to connect this site to the platform. Until then the add-on renders
                    the CMS-authored default variants (it never blocks a render).
                </p>
            @endunless
            <dl class="text-sm grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-6">
                <div class="flex justify-between border-b border-gray-200 py-1"><dt class="text-gray">Platform API</dt><dd class="font-mono">{{ $apiUrl ?: '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-200 py-1"><dt class="text-gray">Tenant key</dt><dd class="font-mono">{{ $tenantKeyMasked ?? '—' }}</dd></div>
                <div class="flex justify-between border-b border-gray-200 py-1"><dt class="text-gray">Rendering mode</dt><dd class="font-mono">{{ $mode }}</dd></div>
                <div class="flex justify-between border-b border-gray-200 py-1"><dt class="text-gray">Timeout</dt><dd class="font-mono">{{ $timeout }}s</dd></div>
                <div class="flex justify-between border-b border-gray-200 py-1"><dt class="text-gray">Cache TTL</dt><dd class="font-mono">{{ $cacheTtl }}s</dd></div>
                <div class="flex justify-between border-b border-gray-200 py-1"><dt class="text-gray">Provisioning</dt><dd class="font-mono">{{ $provisioning ? 'on' : 'off' }}</dd></div>
                <div class="flex justify-between border-b border-gray-200 py-1"><dt class="text-gray">Add-on version</dt><dd class="font-mono">{{ $version }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card p-0 mb-4">
        <div class="p-4 border-b"><h2 class="font-bold">Keeping content in lockstep</h2></div>
        <div class="p-4 text-sm text-gray-700">
            <p class="mb-2">
                Platform-managed fieldsets, block templates and design tokens are pulled into this site with:
            </p>
            <pre class="bg-gray-100 rounded p-3 text-xs"><code>php please mc:sync</code></pre>
            <p class="mt-2">Run it after the platform publishes new blocks or tokens, so the CMS stays a faithful mirror.</p>
        </div>
    </div>

    <div class="card p-0">
        <div class="p-4 border-b"><h2 class="font-bold">Resources</h2></div>
        <div class="p-4 flex flex-wrap gap-3 text-sm">
            <a href="{{ $docsUrl }}" target="_blank" rel="noopener" class="btn">Documentation</a>
            <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener" class="btn">Platform dashboard</a>
            <a href="mailto:{{ $supportEmail }}" class="btn">Contact support</a>
        </div>
    </div>
@endsection
