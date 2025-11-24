@foreach ($templateGroups as $group)
    <div class="row g-2 pb-4 template-groups-wrapper">
        <div class="col-12">
            <div class="d-flex align-items-center mt-2">
                <h6 class="mb-0">{{ localize($group->name) }}</h6>
            </div>
        </div>

        @foreach ($templates as $template)
            @if ($template->template_group_id == $group->id)
                <div class="col-6 col-md-4">
                    <div class="alert alert-secondary d-flex flex-wrap mb-0 py-2">
                        <label class="flex-grow-1">
                            {{ $template->collectLocalization('name') }}
                        </label>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
