@extends('backend.layouts.master')

@section('title')
    {{ localize('Set your password') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            {{-- Fallback copy for anyone whose browser cannot open a <dialog>:
                 the form below is the same one, just not modal. --}}
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-2">{{ localize('Set your password') }}</h5>
                            <p class="text-muted small mb-0">
                                {{ localize('You signed in with a temporary password. Choose your own to continue.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <dialog id="setPasswordDialog" class="st-modal" aria-labelledby="setPasswordTitle">
        <form method="POST" action="{{ route('password.change.store') }}">
            @csrf

            <h2 class="st-dialog-title" id="setPasswordTitle">{{ localize('Set your password') }}</h2>

            <p class="text-muted small">
                {{ localize('You signed in with a temporary password. Choose your own to continue — the temporary one stops working straight away.') }}
            </p>

            <div class="mb-3">
                <label class="form-label" for="newPassword">{{ localize('New password') }}<span class="text-danger">*</span></label>
                <input class="form-control" type="password" id="newPassword" name="password" required minlength="6" autocomplete="new-password">
                @error('password')
                    <p class="text-danger small mt-2 mb-0">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="newPasswordConfirm">{{ localize('Confirm password') }}<span class="text-danger">*</span></label>
                <input class="form-control" type="password" id="newPasswordConfirm" name="password_confirmation" required minlength="6" autocomplete="new-password">
            </div>

            {{-- No Cancel: there is nowhere to cancel to. Signing out is in the
                 header menu, which stays reachable. --}}
            <div class="st-dialog-actions">
                <button type="submit" class="st-dialog-ok">{{ localize('Save password') }}</button>
            </div>
        </form>
    </dialog>

    <script>
    (function () {
        var dialog = document.getElementById('setPasswordDialog');

        if (!dialog || typeof dialog.showModal !== 'function') return;

        dialog.showModal();
        document.getElementById('newPassword').focus();

        // Escape fires `cancel` on a native dialog; this one has nothing behind
        // it worth reaching, so it stays open.
        dialog.addEventListener('cancel', function (e) { e.preventDefault(); });
    })();
    </script>
@endsection
