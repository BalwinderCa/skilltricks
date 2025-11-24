<div class="tt-article-title-wrap mb-5">
    @if (count($titles) > 0)
        @foreach ($titles as $key => $title)
            <div class="form-check tt-article-title p-0 mb-3">
                <input class="form-check-input tt-custom-radio selection-clicked" type="radio" name="flexRadioDefault"
                    data-value="{{ $title }}" id="articleTitle-{{ $key }}">
                <label class="form-check-label w-100 p-4 rounded-3 bg-light-subtle cursor-pointer"
                    for="articleTitle-{{ $key }}">
                    <h2 class="h5 mb-1">{{ $title }}</h2>
                    <span class="text-muted fs-md">{{ count(explode(' ', $title)) }}
                        {{ localize('Words in this title') }}</span>
                </label>
            </div>
        @endforeach
    @else
        <div class="">
            {{ localize('Your generated titles will be listed here') }}
        </div>
    @endif

</div>
