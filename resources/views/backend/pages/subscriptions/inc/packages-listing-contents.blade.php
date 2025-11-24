@foreach ($templateGroups as $group)
    <div class="row g-2 pb-4 template-groups-wrapper">
        <input type="hidden" name="package_id" value="{{ $package->id }}">
        <div class="col-12">
            <div class="d-flex align-items-center mt-2">
                <div class="form-check form-switch me-2">
                    <input type="checkbox" class="form-check-input" id="all_templates-{{ $group->id }}"
                        name="all_templates-{{ $group->id }}" onchange="toggleGroupAll(this)">
                </div>
                <h6 class="mb-0"><label class="form-check-label fw-medium ms-1 cursor-pointer"
                        for="all_templates-{{ $group->id }}">{{ localize($group->name) }}</label></h6>
            </div>
        </div>

        @foreach ($templates as $template)
            @if ($template->template_group_id == $group->id)
                <div class="col-6 col-md-4">
                    <div class="alert alert-secondary d-flex flex-wrap mb-0 py-2">
                        <label class="flex-grow-1 cursor-pointer" for="template-{{ $template->id }}">
                            {{ $template->collectLocalization('name') }}
                        </label>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input permission-checkbox" name="templates[]"
                                id="template-{{ $template->id }}" value="{{ $template->id }}"
                                @if (in_array($template->id, $packageTemplateIds)) checked @endif>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
