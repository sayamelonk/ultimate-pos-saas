@props([
    'paginator' => null,
])

@if($paginator && $paginator->hasPages())
    <nav class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
            @if($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-muted bg-surface border border-border rounded-lg cursor-not-allowed">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-secondary-700 bg-surface border border-border rounded-lg hover:bg-secondary-50">
                    Previous
                </a>
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-secondary-700 bg-surface border border-border rounded-lg hover:bg-secondary-50">
                    Next
                </a>
            @else
                <span class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-muted bg-surface border border-border rounded-lg cursor-not-allowed">
                    Next
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-muted">
                    Showing
                    <span class="font-medium text-text">{{ $paginator->firstItem() ?? 0 }}</span>
                    to
                    <span class="font-medium text-text">{{ $paginator->lastItem() ?? 0 }}</span>
                    of
                    <span class="font-medium text-text">{{ $paginator->total() }}</span>
                    results
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-lg shadow-sm">
                    {{-- Previous --}}
                    @if($paginator->onFirstPage())
                        <span class="relative inline-flex items-center px-2 py-2 rounded-l-lg border border-border bg-surface text-muted cursor-not-allowed">
                            <x-icon name="chevron-left" class="w-5 h-5" />
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-2 py-2 rounded-l-lg border border-border bg-surface text-secondary-500 hover:bg-secondary-50">
                            <x-icon name="chevron-left" class="w-5 h-5" />
                        </a>
                    @endif

                    {{-- Pages --}}
                    @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                        @if($page == $paginator->currentPage())
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px border border-primary bg-primary-50 text-sm font-medium text-primary">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 -ml-px border border-border bg-surface text-sm font-medium text-secondary-700 hover:bg-secondary-50">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-2 py-2 -ml-px rounded-r-lg border border-border bg-surface text-secondary-500 hover:bg-secondary-50">
                            <x-icon name="chevron-right" class="w-5 h-5" />
                        </a>
                    @else
                        <span class="relative inline-flex items-center px-2 py-2 -ml-px rounded-r-lg border border-border bg-surface text-muted cursor-not-allowed">
                            <x-icon name="chevron-right" class="w-5 h-5" />
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
