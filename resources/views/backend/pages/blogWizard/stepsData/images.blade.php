<div class="tt-article-title-wrap mb-5">
    <div class="row g-3">
        @if (count($images) > 0)
            @foreach ($images as $key => $image)
                @php
                    $image_path = $image->storage_type == 'aws' ? $image->value : staticAsset($image->value);
                @endphp
                <div class="col-lg-4 col-sm-6">
                    <div class="form-check tt-article-title p-0">
                        <input class="form-check-input tt-custom-radio selection-clicked" type="radio"
                            name="flexRadioDefault" id="articleImage-{{ $key }}" data-value="{{ $image->value }}">
                        <label class="form-check-label w-100 p-2 rounded-3 bg-light-subtle cursor-pointer"
                            for="articleImage-{{ $key }}">
                            <img src="{{ $image_path }}" alt="" class="img-fluid rounded">
                        </label>
                    </div>
                </div>
            @endforeach
        @else
            <div class="col-12">
                {{ localize('Your generated images will be listed here') }}
            </div>
        @endif
    </div>
</div>
