<div class="card h-100 rounded-4 package-card">
    <div class="card-body">
        <div class="tt-pricing-plan text-center">
            <div class="tt-plan-name">
                @if ($package->is_featured)
                    <div class="tt-featured-badge text-end">
                        <span class="badge pe-3">{{ localize('Featured') }}</span>
                    </div>
                @endif
                <h5 class="mb-1">{{ $package->title }}</h5>
                <span class="text-muted">{{ $package->description }}</span>
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
                            {{ formatPrice($package->price) }}
                        @endif
                    </div>
                @endif

            </div>
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
                            class="me-1">{{ $package->total_words_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Words per month') : localize('Words') }}
                    </li>
                @endif

                @if (getSetting('enable_ai_images') != '0' && $package->show_image_tools != 0)
                    <li><i
                            @if ($package->allow_images == 1 || $package->allow_sd_images == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i><strong
                            class="me-1">{{ $package->total_images_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Images per month') : localize('Images') }}
                    </li>
                @endif


                @if (getSetting('enable_speech_to_text') != '0' && $package->show_speech_to_text_tools != 0)
                    <li><i
                            @if ($package->allow_speech_to_text == 1) data-feather="check-circle" class="icon-14 me-2 text-success" @else  data-feather="x-circle" class="icon-14 me-2 text-danger" @endif></i><strong
                            class="me-1">{{ $package->total_speech_to_text_per_month }}</strong>{{ $package->package_type != 'prepaid' ? localize('Speech to Text per month') : localize('Speech to Texts') }}
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
