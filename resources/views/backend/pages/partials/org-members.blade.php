@if($orgMembers->isNotEmpty())
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="mb-3">{{ localize('Organization members') }}</h5>
            <p class="text-muted small">
                {{ localize('As the owner of this organization you can correct a member\'s declared seniority. The highest rank sets the active strategic context for everyone.') }}
            </p>

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>{{ localize('Name') }}</th>
                        <th>{{ localize('Email') }}</th>
                        <th>{{ localize('Rank') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orgMembers as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td colspan="2">
                            <form method="POST" action="{{ route('organization.member-rank') }}" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $member->id }}">
                                <select name="rank" class="form-control">
                                    @foreach([10 => 'Individual Contributor', 20 => 'Manager', 30 => 'Director', 40 => 'Vice President', 50 => 'C-Suite', 60 => 'Board'] as $value => $label)
                                        <option value="{{ $value }}" {{ (int) $member->hierarchy_rank === $value ? 'selected' : '' }}>
                                            {{ localize($label) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary">{{ localize('Save') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
