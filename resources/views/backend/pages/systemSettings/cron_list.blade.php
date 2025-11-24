@extends('backend.layouts.master')

@section('title')
    {{ localize('Cron Job') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Cron Job') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Cron Job') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4">
                <!--left sidebar-->
                <div class="col-xl-12 order-2 order-md-2 order-lg-2 order-xl-1">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4" id="section-1">

                                <table class="table tt-footable border-top" data-use-parent-width="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="7%">{{ localize('S/L') }}</th>
                                            <th>{{ localize('Name') }}</th>
                                            <th>{{ localize('Command') }}</th>
                                            <th>{{ localize('Example') }}</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-center">
                                                1
                                            </td>

                                            <td class="fw-semibold">
                                                Subscription Auto Active and Expire
                                            </td>
                                            <td>
                                                <h4><code>artisan subscription:expire</code></h4>
                                            </td>
                                            <td>

                                                <code>
                                                    {{ 'cd ' . base_path() . '/ && php artisan subscription:expire >> /dev/null 2>&1' }}
                                                </code>
                                            </td>

                                        </tr>
                                    </tbody>
                                </table>
                                <!--pagination start-->

                                <!--pagination end-->
                            </div>
                        </div>

                    </div>
                </div>

                <!--right sidebar-->

            </div>
        </div>
    </section>
@endsection
