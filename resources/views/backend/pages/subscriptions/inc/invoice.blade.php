@extends('backend.layouts.master')


@section('title')
    {{ localize('Invoice') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection
@section('contents')

    <section class="tt-section py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Invoice') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item active"> <a
                                            href="{{ route('subscriptions.histories.index') }}">{{ localize('Subscription Histories') }}
                                        </a></li>
                                </ol>
                            </div>
                            <div class="tt-action">
                                <a href="{{ route('subscriptions.purchase-invoice-download', $history->id) }}"
                                    class="btn btn-primary">
                                    <i data-feather="download" width="18"></i>
                                    {{ localize('Download Invoice') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-body">
                            {{-- header start --}}
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                                class="fullTable" bgcolor="#ffffff">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table width="600" border="0" cellpadding="0" cellspacing="0"
                                                align="center" class="fullTable" bgcolor="#ffffff">
                                                <tbody>
                                                    <tr class="visibleMobile">
                                                        <td height="40"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <table width="100%" border="0" cellpadding="0"
                                                                cellspacing="0" align="center" class="fullPadding">
                                                                <tbody>
                                                                    <tr class="">
                                                                        <td colspan="4"
                                                                            style="border-right: 1px solid #e4e4e4; width: 300px; color: #323232; line-height: 1.5; vertical-align: top;">
                                                                            <p
                                                                                style="font-size: 15px; color: #5b5b5b; font-weight: bold; line-height: 1; vertical-align: top; ">
                                                                                {{ localize('INVOICE') }}</p>
                                                                            <br>
                                                                            <p
                                                                                style="font-size: 12px; color: #5b5b5b; line-height: 24px; vertical-align: top;">
                                                                                {{ localize('Invoice No') }} :
                                                                                {{ getSetting('order_code_prefix') }}{{ getSetting('order_code_start') }}{{ $history->id }}<br>
                                                                                {{ localize('Purchase Date') }} :
                                                                                {{ date('d M, Y', strtotime($history->created_at)) }}
                                                                            </p>


                                                                        </td>
                                                                        <td colspan="4" align="right"
                                                                            style="width: 300px; text-align: right; padding-left: 50px; line-height: 1.5; color: #323232;">
                                                                            <img src="{{ uploadedAsset(getSetting('admin_panel_logo_dark') ?? getSetting('admin_panel_logo')) }}"
                                                                                alt="logo" border="0" />
                                                                            <p
                                                                                style="font-size: 12px;font-weight: bold; color: #5b5b5b; line-height: 1; vertical-align: top; ">
                                                                                {{ getSetting('system_title') }}</p>
                                                                            <br>
                                                                            {{ localize('Email') }}:
                                                                            {{ getSetting('contact_email') }}
                                                                            <br>
                                                                            {{ localize('Phone') }}:
                                                                            {{ getSetting('contact_phone') }}
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                    <tr class="visibleMobile">
                                                                        <td height="10"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="10"
                                                                            style="border-bottom:1px solid #e4e4e4"></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height="20"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            {{-- header end --}}

                            {{-- billing and shipping start --}}
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                                class="fullTable" bgcolor="#ffffff">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table width="600" border="0" cellpadding="0" cellspacing="0"
                                                align="center" class="fullTable" bgcolor="#ffffff">
                                                <tbody>
                                                    <tr class="visibleMobile">
                                                        <td height="40"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <table width="100%" border="0" cellpadding="0"
                                                                cellspacing="0" align="center" class="fullPadding">
                                                                <tbody>
                                                                    <p
                                                                        style="font-size: 12px; font-weight: bold; color: #5b5b5b; line-height: 1; vertical-align: top; ">
                                                                        {{ localize('CUSTOMER INFORMATION') }}</p>

                                                                    <p
                                                                        style="font-size: 12px; color: #5b5b5b; line-height: 24px; vertical-align: top;">


                                                                        {{ $history->user->name }},
                                                                        {{ $history->user->phone }},<br>
                                                                        {{ $history->user->email }}<br>


                                                                    </p>

                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height="20"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            {{-- billing and shipping end --}}

                            {{-- item details start --}}
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                                class="fullTable" bgcolor="#ffffff">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table width="600" border="0" cellpadding="0" cellspacing="0"
                                                align="center" class="fullTable" bgcolor="#ffffff">
                                                <tbody>
                                                    <tr class="visibleMobile">
                                                        <td height="40"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <table width="100%" border="0" cellpadding="0"
                                                                cellspacing="0" align="center" class="fullPadding">
                                                                <tbody>
                                                                    <tr>
                                                                        <th style="font-size: 12px; color: #000000; font-weight: normal;
                                line-height: 1; vertical-align: top; padding: 0 10px 7px 0;"
                                                                            width="52%" align="left">
                                                                            {{ localize('Package') }}
                                                                        </th>
                                                                        <th style="font-size: 12px; color: #000000; font-weight: normal;
                                line-height: 1; vertical-align: top; padding: 0 0 7px;"
                                                                            align="left">
                                                                            {{ localize('Start Date') }}
                                                                        </th>
                                                                        <th style="font-size: 12px; color: #000000; font-weight: normal;
                                line-height: 1; vertical-align: top; padding: 0 0 7px; text-align: center; "
                                                                            align="center">
                                                                            {{ localize('Package Expire') }}
                                                                        </th>
                                                                        <th style="font-size: 12px; color: #000000; font-weight:
                                normal; line-height: 1; vertical-align: top; padding: 0 0 7px; text-align: right; "
                                                                            align="right">
                                                                            {{ localize('Price') }}
                                                                        </th>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="1" style="background: #e4e4e4;"
                                                                            colspan="4"></td>
                                                                    </tr>


                                                                    <tr>
                                                                        <td style="font-size: 12px; color: #5b5b5b;  line-height: 18px;  vertical-align: top; padding:10px 0;"
                                                                            class="article">
                                                                            <div>{!! html_entity_decode($history->subscriptionPackage->title) !!}
                                                                            </div>
                                                                            <div class="text-muted">
                                                                                <span class="fs-xs">
                                                                                    {{ $history->subscriptionPackage->package_type == 'starter' ? localize('Monthly') : localize($history->subscriptionPackage->package_type) }}
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                        <td
                                                                            style="font-size: 12px; color: #646a6e;  line-height:
                            18px;  vertical-align: top; padding:10px 0;">
                                                                            {{ $history->start_date }}</td>
                                                                        <td style="font-size: 12px; color: #646a6e;  line-height:
                            18px;  vertical-align: top; padding:10px 0; text-align: center;"
                                                                            align="center">{{ $history->end_date }}</td>
                                                                        <td style="font-size: 12px; color: #1e2b33;  line-height:
                            18px;  vertical-align: top; padding:10px 0; text-transform: capitalize !important;"
                                                                            align="right">

                                                                            <strong>
                                                                                {{ $history->package_price > 0 ? formatPrice($history->package_price) : localize('Free') }}
                                                                            </strong>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="1" style="background: #e4e4e4;"
                                                                            colspan="4"></td>
                                                                    </tr>

                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height="20"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            {{-- item details end --}}

                            {{-- item total start --}}
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                                class="fullTable" bgcolor="#ffffff">
                                <tbody>
                                    <tr>
                                        <td>
                                            <table width="600" border="0" cellpadding="0" cellspacing="0"
                                                align="center" class="fullTable" bgcolor="#ffffff">
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <!-- Table Total -->
                                                            <table width="100%" border="0" cellpadding="0"
                                                                cellspacing="0" align="center" class="fullPadding">
                                                                <tbody>
                                                                    <tr>
                                                                        <td
                                                                            style="font-size: 12px; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                                                            {{ localize('Subtotal') }}
                                                                        </td>
                                                                        <td style="font-size: 12px; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; white-space:nowrap;"
                                                                            width="80">
                                                                            {{ $history->package_price > 0 ? formatPrice($history->package_price) : localize('Free') }}
                                                                        </td>
                                                                    </tr>



                                                                    @if ($history->discount)
                                                                        <tr>
                                                                            <td
                                                                                style="font-size: 12px; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                                                                {{ localize('Discount') }} @if ($history->discount_type)
                                                                                    ({{ $history->discount_type == 1 ? localize('flat') : '%' }})
                                                                                @endif
                                                                            </td>
                                                                            <td
                                                                                style="font-size: 12px; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                                                                {{ formatPrice($history->discount) }}
                                                                            </td>
                                                                        </tr>
                                                                    @endif
                                                                    <tr>
                                                                        <td
                                                                            style="font-size: 12px; color: #000; line-height: 22px; vertical-align: top; text-align:right; ">
                                                                            <strong>{{ localize('Grand Total') }}</strong>
                                                                        </td>
                                                                        <td
                                                                            style="font-size: 12px; color: #000; line-height: 22px; vertical-align: top; text-align:right; ">
                                                                            <strong>{{ $history->price > 0 ? formatPrice($history->price, false, false, true, true, $history->currency_code) : localize('Free') }}</strong>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                            <!-- /Table Total -->
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            {{-- item total end --}}

                            {{-- footer start --}}
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                                class="fullTable" bgcolor="#ffffff">

                                <tr>
                                    <td>
                                        <table width="600" border="0" cellpadding="0" cellspacing="0"
                                            align="center" class="fullTable" bgcolor="#ffffff"
                                            style="border-radius: 0 0 10px 10px;">
                                            <tr>
                                            <tr class="hiddenMobile">
                                                <td height="30"></td>
                                            </tr>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <td>
                                                <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                                    align="center" class="fullPadding">
                                                    <tbody>
                                                        <tr>
                                                            <td
                                                                style="font-size: 12px; color: #5b5b5b; line-height: 18px; vertical-align: top; text-align: left;">
                                                                <p
                                                                    style="font-size: 12px; color: #5b5b5b; line-height: 18px; vertical-align: top; text-align: left;">
                                                                    {{ localize('Hello') }}
                                                                    <strong>{{ optional($history->user)->name }},</strong>
                                                                    <br>
                                                                    {{ getSetting('invoice_thanksgiving') }}
                                                                </p>
                                                                <br><br>
                                                                <p
                                                                    style="font-size: 12px; color: #5b5b5b; line-height: 18px; vertical-align: top; text-align: left;">
                                                                    {{ localize('Best Regards') }},
                                                                    <br>{{ getSetting('system_title') }} <br>
                                                                    {{ localize('Email') }}:
                                                                    {{ getSetting('contact_email') }}<br>
                                                                    {{ localize('Website') }}: {{ env('APP_URL') }}
                                                                </p>

                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                </tr>
                            </table>
                            </td>
                            </tr>
                            </table>
                            {{-- footer end --}}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection
