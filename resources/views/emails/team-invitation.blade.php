{{-- Plain, inline-styled markup: email clients ignore stylesheets, and this one
     has to stay readable in whatever renders it. --}}
<div style="background-color:#D5D9E2; font-family:Arial,Helvetica,sans-serif; line-height:1.5; font-size:15px; color:#2F3044; margin:0; padding:24px 0; width:100%;">
    <div style="background-color:#ffffff; padding:32px; border-radius:16px; margin:0 auto; max-width:600px;">

        <h1 style="font-size:20px; margin:0 0 16px;">{{ localize('Hello') }} {{ $name }},</h1>

        <p style="margin:0 0 16px;">
            {{ localize('An account has been created for you on') }} {{ $systemTitle }}@if($organization),
            {{ localize('as part of') }} {{ $organization }}@endif.
        </p>

        <p style="margin:0 0 8px;">{{ localize('Sign in with') }}:</p>

        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
            <tr>
                <td style="padding:4px 12px 4px 0; color:#6b7280;">{{ localize('Email') }}</td>
                <td style="padding:4px 0;"><strong>{{ $email }}</strong></td>
            </tr>
            <tr>
                <td style="padding:4px 12px 4px 0; color:#6b7280;">{{ localize('Temporary password') }}</td>
                <td style="padding:4px 0;">
                    <strong style="font-family:monospace; font-size:16px; letter-spacing:1px;">{{ $temporaryPassword }}</strong>
                </td>
            </tr>
        </table>

        <p style="margin:0 0 24px;">
            <a href="{{ $loginUrl }}"
               style="background:#ff8f3b; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; display:inline-block; font-weight:bold;">
                {{ localize('Sign in') }}
            </a>
        </p>

        <p style="margin:0; color:#6b7280; font-size:13px;">
            {{ localize('You will be asked to choose your own password the first time you sign in. This temporary one stops working then.') }}
        </p>
    </div>
</div>
