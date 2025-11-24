<button class="btn btn-block w-100 mb-4 {{ $package->is_featured == 1 ? 'btn-primary' : 'btn-outline-primary' }}"
    data-package-id="{{ $package->id }}" data-price="{{ $package->sell_price }}"
    data-package-type="{{ $package->package_type }}"
    data-previous-package-type="{{ auth()->check() ? optional(optional(activePackageHistory())->subscriptionPackage ?? new \App\Models\SubscriptionPackage())->package_type : 'unauthorized' }}"
    data-user-type="{{ auth()->check() ? auth()->user()->user_type : 'unauthorized' }}"
    @if ($disabled) disabled
    @else
    onclick="handlePackagePayment(this)" @endif>
    {{ localize($name) }}
</button>
