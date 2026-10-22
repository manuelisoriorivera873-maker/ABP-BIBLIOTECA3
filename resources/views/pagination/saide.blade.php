<nav class="saide-pagination" aria-label="{{ __('pagination.navigation') }}">
    <p class="saide-pagination__summary">
        {{ __('pagination.summary', ['first' => $paginator->firstItem() ?? 0, 'last' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}
    </p>
    @if($paginator->hasPages())
        <ul class="pagination mb-0">
            @if($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ __('pagination.previous') }}</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a></li>
            @endif
            @foreach($elements as $element)
                @if(is_string($element))
                    <li class="page-item disabled page-number" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @else
                    @foreach($element as $page => $url)
                        @if($page == $paginator->currentPage())
                            <li class="page-item active page-number" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item page-number"><a class="page-link" href="{{ $url }}" aria-label="{{ __('pagination.page', ['page' => $page]) }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if($paginator->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a></li>
            @else
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ __('pagination.next') }}</span></li>
            @endif
        </ul>
    @endif
</nav>
