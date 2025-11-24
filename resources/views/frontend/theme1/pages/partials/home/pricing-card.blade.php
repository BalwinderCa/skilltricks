<div class="pricing-5-card text-center h-100 d-flex flex-column">
    @if ($package->is_featured)
    <div class="py-1 ps-10 pe-6 d-inline-block text-center bg-primary-50 text-white fs-12 position-absolute top-0 end-0 popular-label"> {{ localize('Featured') }}</div>
    @endif
    <span class="pricing-5-card-name fs-16 fw-bold clr-neutral-90"> {!! html_entity_decode($package->title) !!}</span>
    <p class="fs-14 clr-neutral-80 mt-3"> {!! html_entity_decode($package->description) !!}</p>
    <div class="d-flex align-items-center justify-content-center gap-2">
        <h3 class="pricing-5-card-price fw-extrabold mb-0 clr-neutral-90">
            @if ($package->package_type == 'starter')
                {{ localize('Free') }}
            @else
                @if ((float) $package->price == 0.0)
                    {{ localize('Free') }}
                @else
                    @if (packageDiscountStatus($package->id))
                        <span class="monthly-price"> {{ formatPrice($package->discount_price) }}</span>
                    @else
                        <span class="monthly-price"> {{ formatPrice($package->price) }}</span>
                    @endif
                @endif
            @endif
        </h3>
        @if ($package->discount)
            <div class="text-start">
                <del class="pricing-5-card-discount">{{ formatPrice($package->price) }}</del>
            </div>
        @endif
    </div>
    {{-- action btns --}}
    @php
        if ($package->package_type == 'starter') {
            if (!auth()->user()) {
                $data = [
                    'package' => $package,
                    'name' => 'Get Started',
                    'disabled' => false,
                ];
            }
            if (auth()->user()) {
                if (activePackageHistory() && optional(activePackageHistory())->subscription_package_id == null) {
                    $data = [
                        'package' => $package,
                        'name' => 'Get Started',
                        'disabled' => false,
                    ];
                } else {
                    $data = [
                        'package' => $package,
                        'name' => 'Applied on Registration',
                        'disabled' => true,
                    ];
                }
            }
        } else {
            if (
                Auth::check() &&
                activePackageHistory() != null &&
                optional(activePackageHistory())->subscription_package_id == $package->id
            ) {
                $autoSubscription = autoSubscription('paypal', activePackageHistory()->id);
                if ($autoSubscription) {
                    $data = [
                        'package' => $package,
                        'name' => $autoSubscription,
                        'disabled' => true,
                    ];
                } else {
                    $data = [
                        'package' => $package,
                        'name' => 'Renew Package',
                        'disabled' => false,
                    ];
                }
            } else {
                if (!auth()->user()) {
                    $data = [
                        'package' => $package,
                        'name' => 'Get Started',
                        'disabled' => false,
                    ];
                }
                if (auth()->user()) {
                    $data = [
                        'package' => $package,
                        'name' => 'Subscribe',
                        'disabled' => false,
                    ];
                    if (auth()->user()->nonPaidSubscriptionHistories->count() > 0) {
                        if (
                            in_array(
                                $package->id,
                                auth()
                                    ->user()
                                    ->nonPaidSubscriptionHistories->pluck('subscription_package_id')
                                    ->toArray(),
                            )
                        ) {
                            $data = [
                                'package' => $package,
                                'name' => 'Yet To Approve',
                                'disabled' => false,
                            ];
                        }
                    }
                    if (auth()->user()->subscribeds->count() > 0) {
                        if (
                            in_array(
                                $package->id,
                                auth()->user()->subscribeds->pluck('subscription_package_id')->toArray(),
                            )
                        ) {
                            $data = [
                                'package' => $package,
                                'name' => 'Already Subscribed',
                                'disabled' => true,
                            ];
                        }
                    }
                }
            }
        }

    @endphp
    <ul class="list gap-3 mt-8 mb-4">
        @php
            $packageTemplatesCounter = $package->subscription_package_templates()->count();

        @endphp
        @if ($package->show_open_ai_model == 1)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->show_open_ai_model ?? false" />
                    <span
                        class="d-block fs-14 clr-neutral-80 fw-medium"><strong class="me-1 text-warning">{{ optional($package->openai_model)->name }}</strong>{{ localize('Open AI Model') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_built_in_templates') != '0' && $package->show_built_in_templates != 0)
            <li>
                <div class="d-flex align-items-center gap-2">

                    <x-checkUncheck :status="$package->allow_built_in_templates ?? false" />

                    <a href="#offcanvasExample" onclick="getPackageTemplates({{ $package->id }})"
                        class="link d-block text-decoration-underline fs-14 clr-neutral-80 :clr-primary-50 fw-medium"
                        data-bs-toggle="offcanvas"> <strong class="me-1 text-warning">{{ $packageTemplatesCounter }}</strong>
                        {{ localize('AI Templates') }}</a>
                </div>

            </li>
        @endif
        @if ($package->show_word_tools != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_word_tools ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium"><strong
                            class="me-1 text-warning">{{ $package->allow_unlimited_word == 1 ? localize('Unlimited') : $package->total_words_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Words per month') : localize('Words') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_images') != '0' && $package->show_image_tools != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    @php
                        $images_status = $package->allow_images == 1 || $package->allow_sd_images == 1 ? true : false;
                    @endphp
                    <x-checkUncheck :status="$images_status ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium"><strong
                            class="me-1 text-warning">{{ $package->allow_unlimited_image == 1 ? localize('Unlimited') : $package->total_images_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Images per month') : localize('Images') }}</span>
                </div>
            </li>
        @endif


        @if (getSetting('enable_speech_to_text') != '0' && $package->show_speech_to_text_tools != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_speech_to_text ?? false" />
                    <span
                        class="d-block fs-14 clr-neutral-80 fw-medium"><strong class="me-1 text-warning">{{ $package->allow_unlimited_speech_to_text == 1 ? localize('Unlimited') : $package->total_speech_to_text_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Speech to Text per month') : localize('Speech to Texts') }}</span>
                </div>
            </li>
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="true ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium"> <strong class="me-1 text-warning"> {{ $package->speech_to_text_filesize_limit }}
                        MB</strong>{{ localize('Audio file size limit') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_chat') != '0' && $package->show_ai_chat != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_chat ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI Chat') }}</span>
                </div>
            </li>
            @if($package->show_real_time_data != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_real_time_data ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('Chat Real Time Data') }}</span>
                </div>
            </li>
            @endif
        @endif
        @if (getSetting('enable_ai_rewriter') != '0' && $package->show_ai_rewriter != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_rewriter ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI ReWriter') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_detector') != '0' && $package->show_ai_detector != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_detector ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI Detector') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_plagiarism') != '0' && $package->show_ai_plagiarism != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_plagiarism ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI Plagiarism') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_vision') != '0' && $package->show_ai_vision != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_vision ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI Vision') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_pdf_chat') != '0' && $package->show_ai_pdf_chat != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_pdf_chat ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI PDF CHAT') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_images') != '0' && $package->show_images != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_rewriter ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI Images') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_images') != '0' && $package->show_sd_images != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_sd_images ?? false" />
                    <span
                        class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('Stable Diffusion Images') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_ai_code') != '0' && $package->show_ai_code != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_ai_code ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI Code') }}</span>
                </div>
            </li>
        @endif
        @if ($package->show_blog_wizard != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_blog_wizard ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('AI Blog Wizard') }}</span>
                </div>
            </li>
        @endif
        @if ($package->show_eleven_labs != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_eleven_labs ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('ElevenLabs') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_speech_to_text') != '0' && $package->show_speech_to_text_tools != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_speech_to_text ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('Speech to Text') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_text_to_speech') != '0' && $package->show_text_to_speech != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_text_to_speech ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('Text to Speech') }}</span>
                </div>
            </li>
        @endif
        @if (getSetting('enable_custom_templates') != '0' && $package->show_custom_templates != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->allow_custom_templates ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('Custom Templates') }}</span>
                </div>
            </li>
        @endif
        @if ($package->show_live_support != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->has_live_support ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('Live Support') }}</span>
                </div>
            </li>
        @endif
        @if ($package->show_free_support != 0)
            <li>
                <div class="d-flex align-items-center gap-2">
                    <x-checkUncheck :status="$package->has_free_support ?? false" />
                    <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ localize('Free Support') }}</span>
                </div>
            </li>
        @endif
        @php
            $otherFeatures = explode(',', $package->other_features);
        @endphp
        @if ($package->other_features)
            @foreach ($otherFeatures as $feature)
                <li>
                    <div class="d-flex align-items-center gap-2">
                        <x-checkUncheck :status="true ?? false" />
                        <span class="d-block fs-14 clr-neutral-80 fw-medium">{{ $feature }}</span>
                    </div>
                </li>
            @endforeach
        @endif
    </ul>

    @if ($package->package_type == 'starter')
        @guest
            @include('frontend.theme1.pages.partials.home.subscribe-btn', $data)
        @endguest

        @auth
            @if (!activePackageHistory())
                @include('frontend.theme1.pages.partials.home.subscribe-btn', $data)
            @else
                @include('frontend.theme1.pages.partials.home.subscribe-btn', $data)
            @endif
        @endauth
    @else
        @if (Auth::check() &&
                activePackageHistory() &&
                optional(activePackageHistory())->subscription_package_id == $package->id)
            @include('frontend.theme1.pages.partials.home.subscribe-btn', $data)
        @else
            @guest
                @include('frontend.theme1.pages.partials.home.subscribe-btn', $data)
            @endguest

            @auth
                @include('frontend.theme1.pages.partials.home.subscribe-btn', $data)
            @endauth
        @endif
    @endif
</div>

