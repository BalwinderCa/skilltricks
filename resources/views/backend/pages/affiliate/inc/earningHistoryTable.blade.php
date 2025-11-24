<table class="table tt-footable border-top align-middle" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center">{{ localize('S/L') }}</th>
            <th>{{ localize('User') }}</th>
            @if (auth()->user()->user_type != 'customer')
                <th data-breakpoints="xs sm">{{ localize('Referred By') }}</th>
            @endif
            <th data-breakpoints="xs sm md">{{ localize('Package') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Rate') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Earning') }}</th>
            <th data-breakpoints="xs sm" class="text-center">{{ localize('Date') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($earningHistories as $key => $history)
            <tr>
                <td class="text-center">
                    {{ $key + 1 + ($earningHistories->currentPage() - 1) * $earningHistories->perPage() }}
                </td>
                <td>
                    <a href="javascript:void(0);" class="d-flex align-items-center">
                        <div class="avatar avatar-sm">
                            <img class="rounded-circle" src="{{ uploadedAsset($history->user->avatar) }}" alt=""
                                onerror="this.onerror=null;this.src='{{ staticAsset('backend/assets/img/placeholder-thumb.png') }}';" />
                        </div>
                        <h6 class="fs-sm mb-0 ms-2">{{ $history->user->name }}
                        </h6>
                    </a>
                </td>

                @if (auth()->user()->user_type != 'customer')
                    <td>
                        <a href="javascript:void(0);" class="d-flex align-items-center">
                            <div class="avatar avatar-sm">
                                <img class="rounded-circle" src="{{ uploadedAsset($history->referredBy->avatar) }}"
                                    alt=""
                                    onerror="this.onerror=null;this.src='{{ staticAsset('backend/assets/img/placeholder-thumb.png') }}';" />
                            </div>
                            <h6 class="fs-sm mb-0 ms-2">{{ $history->referredBy->name }}
                            </h6>
                        </a>
                    </td>
                @endif

                <td>
                    <div>
                        {{ optional(optional($history->subscriptionHistory)->subscriptionPackage)->title }}/{{ optional(optional($history->subscriptionHistory)->subscriptionPackage)->package_type }}
                    </div>
                    <span
                        class="fw-bold">{{ formatPrice($history->subscriptionHistory ? $history->subscriptionHistory->price : 0) }}</span>
                </td>

                <td>
                    {{ $history->commission_rate }}%
                </td>

                <td>
                    {{ formatPrice($history->amount) }}
                </td>

                <td class="text-center">
                    {{ date('d M, Y', strtotime($history->created_at)) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<!--pagination start-->
<div class="d-flex align-items-center justify-content-between px-4 pb-4">
    <span>{{ localize('Showing') }}
        {{ $earningHistories->firstItem() }}-{{ $earningHistories->lastItem() }}
        {{ localize('of') }}
        {{ $earningHistories->total() }} {{ localize('results') }}</span>
    <nav>
        {{ $earningHistories->appends(request()->input())->links() }}
    </nav>
</div>
<!--pagination end-->
