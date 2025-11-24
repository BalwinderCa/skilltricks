<!--Paypal-->
@if (getSetting('enable_paypal') == 1)
    <option value="paypal" {{ old('payment_method') == 'paypal' ? 'selected' : '' }}>{{ localize('Paypal') }}</option>
@endif

<!--stripe-->
@if (getSetting('enable_stripe') == 1)
    <option value="stripe" {{ old('payment_method') == 'stripe' ? 'selected' : '' }}>{{ localize('Stripe') }}</option>
@endif

<!--paytm-->
@if (getSetting('enable_paytm') == 1)
    <option value="paytm" {{ old('payment_method') == 'paytm' ? 'selected' : '' }}>{{ localize('Paytm') }}</option>
@endif

<!--razorpay-->
@if (getSetting('enable_razorpay') == 1)
    <option value="razorpay" {{ old('payment_method') == 'razorpay' ? 'selected' : '' }}>{{ localize('Razorpay') }}
    </option>
@endif

<!--iyzico-->
@if (getSetting('enable_iyzico') == 1)
    <option value="iyzico" {{ old('payment_method') == 'iyzico' ? 'selected' : '' }}>{{ localize('Iyzico') }}</option>
@endif

<!--paystack-->
@if (getSetting('enable_paystack') == 1)
    <option value="paystack" {{ old('payment_method') == 'paystack' ? 'selected' : '' }}>{{ localize('Paystack') }}
    </option>
@endif


<!--flutterwave-->
@if (getSetting('enable_flutterwave') == 1)
    <option value="flutterwave" {{ old('payment_method') == 'flutterwave' ? 'selected' : '' }}>
        {{ localize('Flutterwave') }}</option>
@endif

<!--duitku-->
@if (getSetting('enable_duitku') == 1)
    <option value="duitku" {{ old('payment_method') == 'duitku' ? 'selected' : '' }}>{{ localize('Duitku') }}</option>
@endif

<!--yookassa-->
@if (getSetting('enable_yookassa') == 1)
    <option value="yookassa" {{ old('payment_method') == 'yookassa' ? 'selected' : '' }}>{{ localize('Yookassa') }}
    </option>
@endif

<!--molile-->
@if (getSetting('enable_molile') == 1)
    <option value="molile" {{ old('payment_method') == 'molile' ? 'selected' : '' }}>{{ localize('Molile') }}</option>
@endif

<!--mercadopago-->
@if (getSetting('enable_mercadopago') == 1)
    <option value="mercadopago" {{ old('payment_method') == 'mercadopago' ? 'selected' : '' }}>
        {{ localize('Mercadopago') }}</option>
@endif

<!--midtrans-->
@if (getSetting('enable_midtrans') == 1)
    <option value="midtrans" {{ old('payment_method') == 'midtrans' ? 'selected' : '' }}>{{ localize('Midtrans') }}
    </option>
@endif

<!--offline-->
@if (getSetting('enable_offline') == 1)
    <option value="offline" {{ old('payment_method') == 'offline' ? 'selected' : '' }}>{{ localize('Offline') }}
    </option>
@endif
