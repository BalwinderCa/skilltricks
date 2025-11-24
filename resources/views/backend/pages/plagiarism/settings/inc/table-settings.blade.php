<table class="table tt-footable border-top" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center">{{ localize('S/L') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Key') }}</th> 
            <th data-breakpoints="xs sm">{{ localize('Status') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($settings as $key => $setting)
            <tr>
                <td class="text-center fs-sm">{{$setting->id}}</td>
                <td><span class="fs-sm">{{ isAdmin() || auth()->user()->id == $setting->created_by ? $setting->key : '*******************************' }}</span></td>
                <td>
                    <x-status-change :modelid="$setting->id" :table="$setting->getTable()"
                        :status="$setting->is_active" />
                </td>
                
            </tr>
        @endforeach
    </tbody>
</table>
