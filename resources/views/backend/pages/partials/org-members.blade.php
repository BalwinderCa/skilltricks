@php
    // The six-rung ladder. Kept here so the editable and read-only views can
    // never disagree about what a rank is called.
    $rankLabels = [
        10 => 'Individual Contributor',
        20 => 'Manager',
        30 => 'Director',
        40 => 'Vice President',
        50 => 'C-Suite',
        60 => 'Board',
    ];
@endphp

<div class="card">
    <div class="card-body">
        <h5 class="mb-2">{{ localize('Members') }}</h5>

        <p class="text-muted small">
            @if($isOwner)
                {{ localize('As the owner of this organization you can correct a member\'s declared seniority. The highest rank sets the active strategic context for everyone.') }}
            @else
                {{ localize('Seniority is declared during calibration. The highest rank sets the active strategic context for everyone. Only the organization owner can change it.') }}
            @endif
        </p>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ localize('Name') }}</th>
                    <th>{{ localize('Email') }}</th>
                    <th>{{ localize('Rank') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($orgMembers as $member)
                <tr>
                    <td>
                        {{ $member->name }}
                        @if((int) $member->id === (int) $user->id)
                            <span class="badge bg-light text-muted">{{ localize('you') }}</span>
                        @endif
                        @if($org && (int) $org->owner_user_id === (int) $member->id)
                            <span class="badge bg-light text-muted">{{ localize('owner') }}</span>
                        @endif
                    </td>
                    <td>{{ $member->email }}</td>
                    <td>
                        @if($isOwner)
                            <form method="POST" action="{{ route('organization.member-rank') }}" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $member->id }}">
                                <select name="rank" class="form-control">
                                    @foreach($rankLabels as $value => $label)
                                        <option value="{{ $value }}" {{ (int) $member->hierarchy_rank === $value ? 'selected' : '' }}>
                                            {{ localize($label) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary">{{ localize('Save') }}</button>
                            </form>
                        @else
                            {{ $member->hierarchy_rank ? localize($rankLabels[(int) $member->hierarchy_rank] ?? '—') : localize('Not calibrated') }}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
