@extends('backend.layouts.master')

@section('title')
    {{ localize('Theme Settings') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Theme Settings') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Theme Settings') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-4 pb-650">
                <!--left sidebar-->
                @foreach ($themes as $theme)
                    {{-- paypal --}}
                    <div class="col-lg-3 col-md-6">
                        <div class="card {{ $theme->is_default == 1 ? 'border-success' : '' }} flex-cloumn h-100">
                            <div class="card-header py-3">
                                <h6 class="mb-0">{{ $theme->name }}</h5>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <img class="img-fluid" src="{{ asset('public/' . $theme->preview_image) }}"
                                        alt="avatar" />

                                </div>
                            </div>
                            <div class="card-footer py-3 d-flex justify-content-between flex-wrap align-items-center">
                                @if ($theme->is_default == 1)
                                    <strong>{{ localize('Actived') }}</strong>
                                   
                                @else
                                    <a href="{{route('admin.theme.change-status', ['id'=>$theme->id, 'status'=>'active_now'])}}" class="btn btn-primary btn-sm">{{ localize('Active Now') }}</a>
                                    <div class=""></div>
                                @endif
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

        </div>
        </div>
    </section>
@endsection
