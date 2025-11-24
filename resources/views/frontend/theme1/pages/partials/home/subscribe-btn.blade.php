<button class="link py-3 px-6 mt-auto  border border-primary-key :border-primary-30 bg-primary-key :bg-primary-30 rounded-1 fw-bold clr-white mx-auto align-items-center   fs-12 {{ $package->is_featured == 1 ? 'bg-primary-key' : 'btn-outline-primary' }}"
    data-package-id="{{ $package->id }}" data-price="{{ $package->sell_price }}"
    data-package-type="{{ $package->package_type }}"
    data-previous-package-type="{{ auth()->check() ? optional(optional(activePackageHistory())->subscriptionPackage ?? new \App\Models\SubscriptionPackage())->package_type : 'unauthorized' }}"
    data-user-type="{{ auth()->check() ? auth()->user()->user_type : 'unauthorized' }}"
    @if ($disabled) disabled
    @else
    onclick="handlePackagePayment(this)" @endif>
    {{ localize($name) }}
</button>
