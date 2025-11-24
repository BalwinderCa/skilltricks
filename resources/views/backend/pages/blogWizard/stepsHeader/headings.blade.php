<ul class="tt-progressbar-list">
    <li class="d-inline-block position-relative text-center float-start progressbar-list is-active">
        {{ localize('Keywords') }}</li>
    <li class="d-inline-block position-relative text-center float-start progressbar-list">
        {{ localize('Title') }}
    </li>
    @if(getSetting('generate_image') == 1)
    <li class="d-inline-block position-relative text-center float-start progressbar-list">
        {{ localize('Image') }}
    </li>
    @endif
    <li class="d-inline-block position-relative text-center float-start progressbar-list">
        {{ localize('Outline') }}
    </li>

    <li class="d-inline-block position-relative text-center float-start progressbar-list">
        {{ localize('Article') }}
    </li>
</ul>
