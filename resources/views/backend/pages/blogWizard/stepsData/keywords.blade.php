<table class="table table-responsive">
    <tbody class="border-top-0">
        @forelse ($keywords as $keyword)
            <tr>
                <td class="ps-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input keyword-checkbox" data-key="{{ $keyword }}">
                    </div>
                </td>
                <td>{{ $keyword }}</td>
            </tr>
        @empty
            <tr>
                <td class="py-3 ps-3" colspan="2">
                    {{ localize('Your generated keywords will be listed here') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>
<p class="fs-md text-muted mb-0 px-3">* {{ localize('All keywords are generated based on your topic') }}</p>
