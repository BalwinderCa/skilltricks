<table>
    <thead>
        <tr>
            <th colspan="5" style="background-color: #4472C4; color: white; font-weight: bold; text-align: center; padding: 10px;">
                Role-Based Goals Export
            </th>
        </tr>
        @if($goal)
        <tr>
            <th colspan="5" style="background-color: #E7E6E6; padding: 8px;">
                <strong>Goal:</strong> {{ $goal }}
            </th>
        </tr>
        @endif
        @if($strategy)
        <tr>
            <th colspan="5" style="background-color: #E7E6E6; padding: 8px;">
                <strong>Selected Strategy:</strong> {{ $strategy }}
            </th>
        </tr>
        @endif
        @if($scenario)
        <tr>
            <th colspan="5" style="background-color: #E7E6E6; padding: 8px;">
                <strong>Selected Scenario:</strong> {{ $scenario }}
            </th>
        </tr>
        @endif
        <tr>
            <th style="background-color: #D9E1F2; font-weight: bold; padding: 8px; border: 1px solid #000;">#</th>
            <th style="background-color: #D9E1F2; font-weight: bold; padding: 8px; border: 1px solid #000;">Role</th>
            <th style="background-color: #D9E1F2; font-weight: bold; padding: 8px; border: 1px solid #000;">Goal</th>
            <th style="background-color: #D9E1F2; font-weight: bold; padding: 8px; border: 1px solid #000;">Actions</th>
            <th style="background-color: #D9E1F2; font-weight: bold; padding: 8px; border: 1px solid #000;">Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($roleGoals as $index => $roleGoal)
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000; padding: 8px; font-weight: bold;">{{ $roleGoal['role'] ?? 'N/A' }}</td>
            <td style="border: 1px solid #000; padding: 8px;">{{ $roleGoal['goal'] ?? 'N/A' }}</td>
            <td style="border: 1px solid #000; padding: 8px;">{!! nl2br(e($roleGoal['actions'] ?? '')) !!}</td>
            <td style="border: 1px solid #000; padding: 8px;">{{ $roleGoal['notes'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>


