<div class="tt-full-article mb-5">
    @if (!is_null($article))
        <div class="tt-introduction mb-3">
            <h1 class="h4 mb-3">{{ $article->title }}</h1>
            @if ($article->image != null && $article->updated_by == null)
                <div class="tt-blog-img-wrap rounded mb-4">
                    <img src="{{ staticAsset($article->image) }}" alt="" class="img-fluid rounded">
                </div>
            @endif
        </div>
        <div class="mb-4 tt-single-para article-content">
            {!! preg_replace('/\*\*(.*?)\*\*/', '<h2 class="mb-0 mt-4 h5">$1</h2>', $article->value) !!}
        </div>

        <div class="edit-blog-btn d-none">
            <a href="{{ route('blog.wizard.edit', $article->aiBlogWizard->uuid) }}" target="__blank"
                class="btn btn-sm btn-soft-primary"><i data-feather="edit-3" class="me-1"></i>
                {{ localize('Edit Blog Article') }}</a>
        </div>
    @else
        <h4 class="">
            {{ localize('Your generated article will be shown here') }}
        </h4>
    @endif
</div>

