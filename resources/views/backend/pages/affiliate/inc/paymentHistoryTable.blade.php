<table class="table tt-footable border-top align-middle" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center">{{ localize('S/L') }}</th>
            <th>{{ localize('User') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Date') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Amount') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Payment Method') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Status') }}</th>
            <th data-breakpoints="xs sm lg">{{ localize('Additional Info') }}</th>
            <th data-breakpoints="xs sm lg">{{ localize('Remarks') }}</th>
            @if (auth()->user()->user_type != 'customer')
                <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($paymentHistories as $key => $history)
            <tr>
                <td class="text-center">
                    {{ $key + 1 + ($paymentHistories->currentPage() - 1) * $paymentHistories->perPage() }}
                </td>
                <td>
                    <a href="javascript:void(0);" class="d-flex align-items-center">
                        <div class="avatar avatar-sm">
                            <img class="rounded-circle" src="{{ uploadedAsset($history->user->avatar) }}"
                                alt=""
                                onerror="this.onerror=null;this.src='{{ staticAsset('backend/assets/img/placeholder-thumb.png') }}';" />
                        </div>
                        <h6 class="fs-sm mb-0 ms-2">{{ $history->user->name }}
                        </h6>
                    </a>
                </td>

                <td>
                    {{ date('d M, Y', strtotime($history->created_at)) }}
                </td>

                <td>
                    {{ formatPrice($history->amount) }}
                </td>

                <td>
                    {{ ucwords(str_replace('_', ' ', $history->payment_method)) }}
                </td>

                <td>
                    @if ($history->status == 'requested')
                        <span class="badge bg-soft-primary rounded-pill text-capitalize">
                            {{ $history->status }}
                        </span>
                    @elseif($history->status == 'paid')
                        <span class="badge bg-soft-success rounded-pill text-capitalize">
                            {{ $history->status }}
                        </span>
                    @else
                        <span class="badge bg-soft-danger rounded-pill text-capitalize">
                            {{ $history->status }}
                        </span>
                    @endif
                </td>

                <td>
                    @if ($history->additional_info)
                        {{ $history->additional_info }}
                    @else
                        -
                    @endif
                </td>

                <td>
                    @if ($history->remarks)
                        {{ $history->remarks }}
                    @else
                        -
                    @endif
                </td>

                @if (auth()->user()->user_type != 'customer')
                    <td class="text-end">
                        @if ($history->status != 'cancelled' && $history->status != 'paid')
                            <div class="dropdown tt-tb-dropdown">
                                <button type="button" class="btn p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i data-feather="more-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow">

                                    <a class="dropdown-item" href="javascript::void(0);" data-bs-toggle="modal"
                                        data-bs-target="#withdraw-modal-{{ $history->id }}">
                                        <i data-feather="credit-card"
                                            class="me-2"></i>{{ localize('Cancel or Pay Now') }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <span
                                class="badge bg-soft-danger rounded-pill text-capitalize">{{ localize('N/A') }}</span>
                        @endif
                    </td>
                @endif

            </tr>

            @if (auth()->user()->user_type != 'customer')
                {{-- modal --}}
                <div id="withdraw-modal-{{ $history->id }}" class="modal fade modalParentSelect2">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ localize('Cancel Or Pay Now') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-hidden="true"></button>
                            </div>
                            <div class="modal-body">
                                @php
                                    $payoutAccount = \App\Models\AffiliatePayoutAccount::where('user_id', $history->user_id)
                                        ->where('payment_method', $history->payment_method)
                                        ->first();
                                @endphp
                                <div class="mb-3">
                                    {{ localize('Payment Method') }}: <span
                                        class="badge bg-soft-success rounded-pill text-capitalize">
                                        {{ ucwords(str_replace('_', ' ', $history->payment_method)) }}
                                    </span>
                                </div>

                                <div class="mb-3">
                                    {{ localize('Account Details') }}: {{ $payoutAccount->account_details }}
                                </div>

                                @if ($history->additional_info)
                                    <div class="mb-3">
                                        {{ localize('Additional Info') }}: {{ $history->additional_info }}
                                    </div>
                                @endif

                                <form action="{{ route('affiliate.withdraw.update') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value={{ $history->id }}>

                                    <div class="mb-3">
                                        <label for="status" class="form-label">
                                            {{ localize('Status') }}
                                            <small class="text-danger">*</small>
                                        </label>
                                        <select class="form-select modalSelect2" id="status" name="status" required>
                                            <option value="">
                                                {{ localize('Change status to') }}
                                            </option>

                                            <option value="paid">
                                                {{ localize('Paid') }}
                                            </option>

                                            <option value="cancelled">
                                                {{ localize('Cancelled') }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="remarks" class="form-label">{{ localize('Remarks') }}</label>
                                        <textarea id="remarks" name="remarks" class="form-control" placeholder="{{ localize('Type remarks') }}"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <button class="btn btn-primary" type="submit">
                                            <i data-feather="save" class="me-1"></i>
                                            {{ localize('Save Changes') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </tbody>
</table>

<!--pagination start-->
<div class="d-flex align-items-center justify-content-between px-4 pb-4">
    <span>{{ localize('Showing') }}
        {{ $paymentHistories->firstItem() }}-{{ $paymentHistories->lastItem() }}
        {{ localize('of') }}
        {{ $paymentHistories->total() }} {{ localize('results') }}</span>
    <nav>
        {{ $paymentHistories->appends(request()->input())->links() }}
    </nav>
</div>
<!--pagination end-->
