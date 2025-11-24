<div class="tt-article-title-wrap mb-5">
    @if (count($outlines) > 0)
        @foreach ($outlines as $key => $outline)
            @php
                $values = json_decode($outline['values']) ?? [];
            @endphp

            <div class="form-check tt-article-title p-0 mb-3">
                <input class="form-check-input tt-custom-radio selection-clicked" type="radio" name="flexRadioDefault"
                    id="outlineSelection-{{ $key }}" data-value="{{ $outline['values'] }}">
                <label class="form-check-label w-100 p-4 rounded-3 bg-light-subtle cursor-pointer"
                    for="outlineSelection-{{ $key }}">
                    <ul class="tt-article-outline-list ps-3">
                        @foreach ($values as $value)
                            <li>{{ $value }}</li>
                        @endforeach
                    </ul>
                    <span class="text-muted fs-md">{{ count($values) }} {{ localize('Sections in this block') }}</span>
                </label>
            </div>
        @endforeach
    @else
        <div class="">
            {{ localize('Your generated outlines will be listed here') }}
        </div>
    @endif
</div>
