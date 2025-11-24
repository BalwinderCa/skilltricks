<div class="card h-100 rounded-4 package-card">
    <div class="card-body">
        <div class="tt-pricing-plan text-center">
            <div class="tt-plan-name">
                @if ($package->is_featured)
                    <div class="tt-featured-badge text-end">
                        <span class="badge pe-3">{{ localize('Featured') }}</span>
                    </div>
                @endif
                <h5 class="mb-1"> {!! html_entity_decode($package->title) !!}</h5>
                <span class="text-muted"> {!! html_entity_decode($package->description) !!}</span>
            </div>
            <div class="tt-price-wrap d-flex align-items-center justify-content-center my-3">
                @if ($package->package_type == 'starter')
                    <div class="fs-1 fw-bold">
                        {{ localize('Free') }}
                    </div>
                @else
                    <div class="fs-1 fw-bold">
                        @if ((float) $package->price == 0.0)
                            {{ localize('Free') }}
                        @else
                            @if (packageDiscountStatus($package->id))
                                {{ formatPrice($package->discount_price) }}
                                <del class="text-muted h4">{{ formatPrice($package->price) }}</del>
                            @else
                                {{ formatPrice($package->price) }}
                            @endif
                        @endif
                    </div>
                @endif

            </div>
            {{-- button --}}
            <div class="mt-4">

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
                            if (
                                activePackageHistory() &&
                                optional(activePackageHistory())->subscription_package_id == null
                            ) {
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
                @if ($package->package_type == 'starter')
                    @guest
                        @include('frontend.default.pages.partials.home.subscribe-btn', $data)
                    @endguest

                    @auth
                        @if (!activePackageHistory())
                            @include('frontend.default.pages.partials.home.subscribe-btn', $data)
                        @else
                            @include('frontend.default.pages.partials.home.subscribe-btn', $data)
                        @endif
                    @endauth
                @else
                    @if (Auth::check() &&
                            activePackageHistory() &&
                            optional(activePackageHistory())->subscription_package_id == $package->id)
                        @include('frontend.default.pages.partials.home.subscribe-btn', $data)
                    @else
                        @guest
                            @include('frontend.default.pages.partials.home.subscribe-btn', $data)
                        @endguest

                        @auth
                            @include('frontend.default.pages.partials.home.subscribe-btn', $data)
                        @endauth
                    @endif
                @endif
                {{-- action btns --}}
            </div>
            {{-- end button --}}
        </div>

        <div class="tt-pricing-feature">
            <ul class="tt-pricing-feature list-unstyled rounded mb-0">
                @php
                    $packageTemplatesCounter = $package->subscription_package_templates()->count();

                @endphp

                @if ($package->show_open_ai_model == 1)
                    <li><i data-feather="check-circle" class="icon-14 me-2 text-success"></i><strong
                            class="me-1">{{ optional($package->openai_model)->name }}</strong>{{ localize('Open AI Model') }}
                    </li>
                @endif

                @if (getSetting('enable_built_in_templates') != '0' && $package->show_built_in_templates != 0)
                    <li>
                        <i
                            @if ($package->allow_built_in_templates == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>
                        <a href="javascript::void(0);" class="text-underline text-dark" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasRight" onclick="getPackageTemplates({{ $package->id }})">
                            <strong class="me-1">{{ $packageTemplatesCounter }}</strong>
                            {{ localize('AI Templates') }}
                        </a>
                    </li>
                @endif

                @if ($package->show_word_tools != 0)
                    <li><i
                            @if ($package->allow_word_tools == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i><strong
                            class="me-1">{{ $package->allow_unlimited_word == 1 ? localize('unlimited') : $package->total_words_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Words per month') : localize('Words') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_images') != '0' && $package->show_image_tools != 0)
                    <li><i
                            @if ($package->allow_images == 1 || $package->allow_sd_images == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i><strong
                            class="me-1">{{ $package->allow_unlimited_image == 1 ? localize('unlimited') : $package->total_images_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Images per month') : localize('Images') }}
                    </li>
                @endif


                @if (getSetting('enable_speech_to_text') != '0' && $package->show_speech_to_text_tools != 0)
                    <li><i
                            @if ($package->allow_speech_to_text == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i><strong
                            class="me-1">{{ $package->allow_unlimited_speech_to_text == 1 ? localize('unlimited') : $package->total_speech_to_text_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Speech to Text per month') : localize('Speech to Texts') }}
                    </li>

                    <li><i data-feather="check-circle" class="icon-14 me-2 text-success"></i><strong
                            class="me-1">{{ $package->speech_to_text_filesize_limit }}
                            MB</strong>{{ localize('Audio file size limit') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_chat') != '0' && $package->show_ai_chat != 0)
                    <li><i
                            @if ($package->allow_ai_chat == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI Chat') }}
                    </li>
                    @if ($package->show_real_time_data != 0)
                        <li><i
                                @if ($package->allow_real_time_data == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('Chat Real Time Data') }}
                        </li>
                    @endif
                @endif

                @if (getSetting('enable_ai_plagiarism') != '0' && $package->show_ai_plagiarism != 0)
                    <li><i
                            @if ($package->allow_ai_plagiarism == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI Plagiarism') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_detector') != '0' && $package->show_ai_detector != 0)
                    <li><i
                            @if ($package->allow_ai_detector == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI Detector') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_rewriter') != '0' && $package->show_ai_rewriter != 0)
                    <li><i
                            @if ($package->allow_ai_rewriter == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI ReWriter') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_vision') != '0' && $package->show_ai_vision != 0)
                    <li><i
                            @if ($package->allow_ai_vision == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI Vision') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_pdf_chat') != '0' && $package->show_ai_pdf_chat != 0)
                    <li><i
                            @if ($package->allow_ai_pdf_chat == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI PDF CHAT') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_images') != '0' && $package->show_images != 0)
                    <li><i
                            @if ($package->allow_images == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI Images') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_images') != '0' && $package->show_sd_images != 0)
                    <li><i
                            @if ($package->allow_sd_images == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('Stable Diffusion Images') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_code') != '0' && $package->show_ai_code != 0)
                    <li><i
                            @if ($package->allow_ai_code == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI Code') }}
                    </li>
                @endif

                @if ($package->show_blog_wizard != 0)
                    <li><i
                            @if ($package->allow_blog_wizard == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('AI Blog Wizard') }}
                    </li>
                @endif
                @if ($package->show_eleven_labs != 0)
                    <li><i
                            @if ($package->allow_eleven_labs == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('ElevenLabs') }}
                    </li>
                @endif

                @if (getSetting('enable_speech_to_text') != '0' && $package->show_speech_to_text_tools != 0)
                    <li><i
                            @if ($package->allow_speech_to_text == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('Speech to Text') }}
                    </li>
                @endif

                @if (getSetting('enable_text_to_speech') != '0' && $package->show_text_to_speech != 0)
                    <li><i
                            @if ($package->allow_text_to_speech == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('Text to Speech') }}
                    </li>
                @endif

                @if (getSetting('enable_custom_templates') != '0' && $package->show_custom_templates != 0)
                    <li><i
                            @if ($package->allow_custom_templates == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('Custom Templates') }}
                    </li>
                @endif

                @if ($package->show_live_support != 0)
                    <li><i
                            @if ($package->has_live_support == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('Live Support') }}
                    </li>
                @endif

                @if ($package->show_free_support != 0)
                    <li><i
                            @if ($package->has_free_support == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i>{{ localize('Free Support') }}
                    </li>
                @endif

                @php
                    $otherFeatures = explode(',', $package->other_features);
                @endphp
                @if ($package->other_features)
                    @foreach ($otherFeatures as $feature)
                        <li><i data-feather="check-circle" class="icon-14 me-2 text-success"></i>{{ $feature }}
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>


    </div>
</div>
