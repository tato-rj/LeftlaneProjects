<div class="table-footer">
    <span>
        @if($records->total())
            Showing
            <strong>
                {{ number_format($records->firstItem()) }}–{{ number_format($records->lastItem()) }}
            </strong>
            of {{ number_format($records->total()) }}
        @else
            0 results
        @endif
    </span>
    <nav class="pagination" aria-label="Pagination">
        @if($records->onFirstPage())
            <span aria-disabled="true">
                ← Previous
            </span>
        @else
            <a href="{{ $records->previousPageUrl() }}">
                ← Previous
            </a>
        @endif
        <span class="page-number">
            Page {{ $records->currentPage() }} of {{ $records->lastPage() }}
        </span>
        @if($records->hasMorePages())
            <a href="{{ $records->nextPageUrl() }}">
                Next →
            </a>
        @else
            <span aria-disabled="true">
                Next →
            </span>
        @endif
    </nav>
</div>
