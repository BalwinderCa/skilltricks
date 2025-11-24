<div class="d-sm-block d-lg-flex align-items-center justify-content-between px-4 pb-4">
    <span>{{ localize('Showing') }}
        {{ $list->firstItem() ?? 0 }}-{{ $list->lastItem() ?? 0 }}
        {{ localize('of') }}
        {{ $list->total() }} {{ localize('results') }}</span>
    <nav>
        {{ $list->appends(request()->input())->onEachSide(0)->links() }}
    </nav>


</div>
