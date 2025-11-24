@extends('backend.layouts.master')

@section('title')
    {{ localize('AI Keys') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('AI Keys') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('admin.settings.openAi') }}">{{ localize('AI Settings') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('AI Keys Support Model') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row g-4">
                <div class="row align-items-center">
                    <x-open-ai-error-message/>
                </div>
                <div class="col-12">
                    <div class="card mb-4" id="section-1">                      

                        <table class="table tt-footable border-top" data-use-parent-width="true">
                            <thead>
                                <tr>
                                    <th class="text-center">{{ localize('S/L') }}</th>
                                    <th>{{ localize('Name') }}</th>
                                    <th>{{ localize('Model') }}</th>

                                

                                </tr>
                            </thead>
                            <tbody>
                                @isset($models)
                                    @foreach ($models as $key => $model)
                                        <tr>
                                            <td class="text-center">
                                            {{$loop->iteration}}
                                            <td> {{ucfirst(str_replace('-', ' ', $model->id))}}</td>
                                            <td> {{$model->id}}</td>
                                        </tr>
                                    @endforeach
                                @endisset
                            </tbody>
                        </table>
                      
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

