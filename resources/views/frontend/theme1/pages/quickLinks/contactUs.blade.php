@extends('frontend.theme1.layouts.master')

@section('title')
    {{ localize('Contact Us') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('content')
    <div class="breadcrumb-section">
        <div class="container">
            <div class="row g-4">

                <div class="col-md-6 col-lg-6">
                    <div class="ai-template-card p-4 p-sm-6 px-xxl-12 py-xxl-12 text-center text-xl-start h-100">
                        <div class="clr-neutral-50 fs-32">
                            <img src="{{ asset('public/frontend/theme1/') }}/assets/img/use-case-icon-2.png" alt="image">
                        </div>
                        <h6 class="clr-neutral-90 mt-6 mb-2"> {{ localize('Email Us') }} </h6>
                        <p class="clr-neutral-80 mb-6 fs-16">
                            {{ localize("Drop us an email and you'll receive a reply within a short time.") }}
                             </p>
                        <a href="mailto:{{ getSetting('contact_email') }}"
                            class="link d-inline-flex justify-content-center align-items-center gap-3 rounded bg-neutral-10 :bg-primary-40 clr-white px-6 py-3 fs-14 text-center rounded top-shadow">
                            <span class="d-inline-block fw-extrabold "> {{ localize('Email us ') }}</span>
                            <span class="d-inline-block">
                                <i class="bi bi-arrow-right"></i> {{ getSetting('contact_email') }}
                            </span>
                        </a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6">
                    <div class="ai-template-card p-4 p-sm-6 px-xxl-12 py-xxl-12 text-center text-xl-start h-100">
                        <div class="clr-neutral-50 fs-32">
                            <img src="{{ asset('public/frontend/theme1/') }}/assets/img/use-case-icon-6.png" alt="image">
                        </div>
                        <h6 class="clr-neutral-90 mt-6 mb-2"> {{ localize('Give us a call') }} </h6>
                        <p class="clr-neutral-80 mb-6 fs-16">
                            {{ localize('Give us a call. Our Experts are ready to talk to you.') }} </p>
                        <a href="tel:   "
                            class="link d-inline-flex justify-content-center align-items-center gap-3 rounded bg-neutral-10 :bg-primary-40 clr-white px-6 py-3 fs-14 text-center rounded top-shadow">
                            <span class="d-inline-block fw-extrabold "> {{ localize('Give us call') }} </span>
                            <span class="d-inline-block">
                                <i class="bi bi-arrow-right"></i> {{ getSetting('contact_phone') }}
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="section-space-y">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h4 class="h4 fw-bold mb-2 clr-neutral-90"> {{ localize('Talk to Our Team') }} </h4>
                    <p class="clr-neutral-80 fs-14 mb-6">
                        {{ localize('Write to us, we are happy to assist you about your queries') }}. </p>

                    {!! Form::open([
                        'route' => 'contactUs.store',
                        'class' => 'row g-4',
                        'id' => 'contactUs-form',
                        'method' => 'POST',
                    ]) !!}
                    @if (getSetting('enable_recaptcha') == 1)
                        {!! RecaptchaV3::field('recaptcha_token') !!}
                    @endif
                    <div class="col-12">
                        <label class="clr-neutral-80 mb-2">{{ localize('Name') }}</label>
                        <input type="text" name="name" required
                            class="form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-2 px-4 bg-neutral-4 placeholder-50 focus-bg-none"
                            placeholder="Your name">
                    </div>
                    <div class="col-md-6">
                        <label class="clr-neutral-80 mb-2">{{ localize('Email') }}</label>
                        <input type="email" name="email" required
                            class="form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-2 px-4 bg-neutral-4 placeholder-50 focus-bg-none"
                            placeholder="Your Email">
                    </div>
                    <div class="col-md-6">
                        <label class="clr-neutral-80 mb-2">{{ localize('Phone') }}</label>
                        <input type="text" name="phone" required
                            class="form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-2 px-4 bg-neutral-4 placeholder-50 focus-bg-none"
                            placeholder="Your Phone">
                    </div>
                    <div class="col-12">
                        <label class="clr-neutral-80 mb-2">{{ localize('Messages') }}</label>
                        <textarea rows="3" name="message" required
                            class="form-control border border-neutral-17 clr-neutral-90 :focus-clr-current rounded-2 py-2 px-4 bg-neutral-4 placeholder-50 focus-bg-none"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit"
                            class="link d-inline-flex justify-content-center align-items-center gap-2 py-3 px-6 border border-primary-key bg-primary-key rounded fw-semibold clr-white border-0 :arrow-btn">
                            <span>{{ localize('Get in Touch') }}</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                    {!! Form::close() !!}
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <img src="{{ asset('public/frontend/default/') }}/assets/img/website/contact-us.svg" alt="image"
                            class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
