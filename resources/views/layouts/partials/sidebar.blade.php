<aside
    class="app-sidebar"
    :class="{ 'open': sidebarOpen }"
    @keydown.escape.window="sidebarOpen = false"
>
    <div class="flex h-full flex-col">
        <div class="sidebar-brand">
            <img
                src="https://d2xsxph8kpxj0f.cloudfront.net/310519663368468239/Ge2emXXoKVgq4kYU9oXE74/saffhire-logo_fe0fac3a.png"
                alt="SaffHire"
                class="h-8 w-auto"
            />
            <div class="sidebar-brand-subtitle mt-2">{{ config('app.name') }}</div>
        </div>

        <nav class="flex-1 overflow-y-auto pb-4">
            @foreach ($navigationSections ?? [] as $section)
                <div class="sidebar-section-label">{{ $section['label'] }}</div>
                <div class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'nav-item',
                                'nav-item-active' => $item['active'],
                            ])
                            @click="sidebarOpen = false"
                        >
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        @if ($currentOrganization ?? null)
            <div class="border-t border-enterprise-200 px-4 py-3">
                <div class="meta-label">Active organization</div>
                <div class="meta-value truncate">{{ $currentOrganization->name }}</div>
            </div>
        @elseif (auth()->user()?->isPlatformUser())
            <div class="border-t border-enterprise-200 px-4 py-3 text-xs font-medium text-accent-darker">
                Platform operations mode
            </div>
        @endif
    </div>
</aside>
