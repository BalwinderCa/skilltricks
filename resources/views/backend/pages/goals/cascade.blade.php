{{-- Notion Epic 3: cascade $root (or the sub-goal $parent) to the user's direct reports. --}}
@php
    $items = $cascades->itemsFor($user, $root, $parent);
    $drafts = $items->whereNull('sent_at');
    $sentItems = $items->whereNotNull('sent_at');
    [$sourceName, $sourceId] = $parent ? ['cascade_id', $parent->id] : ['goal_id', $root->id];
@endphp
{{-- Sent sub-goals stay visible; only the controls collapse. --}}
@foreach ($sentItems as $item)
    <div class="small mt-1">&rarr; <strong>{{ $item->assignee?->name }}</strong>: {{ $item->text }}
        <span class="text-muted">[{{ $statusLabels[$item->status] ?? localize('Not started') }}@if ($item->pct !== null) · {{ $item->pct }}%@endif]</span>
    </div>
@endforeach
<details class="mt-2" @if ($drafts->isNotEmpty()) open @endif>
    <summary class="small" style="color:#2c6d82;cursor:pointer">{{ localize('Cascade to your team') }} ({{ $myReports->count() }})</summary>
    <form method="POST" action="{{ route('my-goals.cascade.suggest') }}" class="mt-2">
        @csrf
        <input type="hidden" name="{{ $sourceName }}" value="{{ $sourceId }}">
        <button type="submit" class="btn btn-sm mg-btn">{{ localize('Suggest line-item actions for my team') }}</button>
    </form>
    @if ($drafts->isNotEmpty())
        <form method="POST" action="{{ route('my-goals.cascade.send') }}" class="mt-2">
            @csrf
            <input type="hidden" name="{{ $sourceName }}" value="{{ $sourceId }}">
            @foreach ($drafts as $draft)
                <div class="mb-2">
                    <div class="small"><strong>{{ $draft->assignee?->name }}</strong>
                        <label class="ms-2 text-muted"><input type="checkbox" name="remove[]" value="{{ $draft->id }}"> {{ localize('remove') }}</label>
                    </div>
                    <textarea name="texts[{{ $draft->id }}]" rows="2" maxlength="300" class="form-control form-control-sm">{{ $draft->text }}</textarea>
                </div>
            @endforeach
            <button type="submit" class="btn btn-sm" style="background:#36839b;color:#fff">{{ localize('Send to my team') }}</button>
        </form>
    @endif
    <form method="POST" action="{{ route('my-goals.cascade.add') }}" class="row g-2 mt-2">
        @csrf
        <input type="hidden" name="{{ $sourceName }}" value="{{ $sourceId }}">
        <div class="col-md-3">
            <select name="assignee_id" class="form-select form-select-sm">
                @foreach ($myReports as $report)
                    <option value="{{ $report->id }}">{{ $report->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7"><input type="text" name="text" maxlength="300" required class="form-control form-control-sm" placeholder="{{ localize('Or write a sub-goal yourself') }}"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-sm mg-btn w-100">{{ localize('Add draft') }}</button></div>
    </form>
    @error('assignee_id')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
</details>
