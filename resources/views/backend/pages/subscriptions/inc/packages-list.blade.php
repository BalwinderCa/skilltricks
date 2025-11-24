@foreach ($packages as $package)

    <!-- <div class="col-12 col-lg-4">

        <input type="hidden" value="{{ $package->id }}" class="package_id">



        <div class="card h-100 package-card">

            <div class="card-body">

                <div class="tt-pricing-plan">

                    {{-- name & desc --}}



                    <div class="tt-plan-name">

                        <div class="d-flex align-items-center justify-content-between">

                            <h5 class="mb-0 tt_update_text" data-name="package-name-{{ $package->id }}">

                                {!! html_entity_decode($package->title) !!}

                            </h5>

                            <span class="tt-edit-icon ms-2 text-muted"><i class="tt_editable cursor-pointer icon-14"

                                    data-name="package-name-{{ $package->id }}" data-feather="edit"></i></span>

                        </div>

                        <div class="d-flex align-items-center justify-content-between">

                            <span class="text-muted tt_update_text"

                                data-name="package-description-{{ $package->id }}">{!! html_entity_decode($package->description) !!}</span>

                            <span class="tt-edit-icon ms-2 text-muted"><i class="tt_editable cursor-pointer icon-14"

                                    data-name="package-description-{{ $package->id }}" data-feather="edit"></i></span>

                        </div>

                    </div>



                    {{-- price --}}

                    <div class="tt-price-wrap d-flex align-items-center justify-content-between mt-4 mb-3">

                        @if ($package->package_type == 'starter')

                            <div class="monthly-price fs-1 fw-bold">

                                {{ localize('Free') }}

                            </div>

                        @else

                            <div class="monthly-price fs-1 fw-bold">



                                <input type="hidden" name="package_main_price"

                                    class="package-main-price-{{ $package->id }}" value="{{ $package->price }}">



                                $<span class="tt_update_text package-price-{{ $package->id }}"

                                    onkeypress="nonNumericFilter()"

                                    data-name="package-price-{{ $package->id }}">{{ $package->discount_status && $package->discount_price ? $package->discount_price : $package->price }}</span>



                                <span class="tt_update_text ">

                                    <del

                                        class="package-discount-price-{{ $package->id }}">{{ $package->discount_status && $package->discount_price ? '$' . $package->price : '' }}</del></span>

                                <sup><span class="cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"

                                        data-bs-title="{{ localize('Set $0 to make it free') }}"><i

                                            data-feather="help-circle" class="icon-14"></i></span></sup>





                            </div>

                            <span

                                class="tt-edit-icon ms-2 text-muted package-price-edit-{{ $package->id }} {{ $package->discount_status && $package->discount_price ? 'd-none' : '' }}"><i

                                    class="tt_editable cursor-pointer icon-14"

                                    data-name="package-price-{{ $package->id }}" data-feather="edit"></i></span>

                        @endif

                    </div>



                </div>





                <div class="tt-pricing-feature">

                    <ul class="tt-pricing-feature list-unstyled rounded mb-0">



                        <li class="pt-0">

                            <div class="d-flex align-items-center justify-content-end">

                                <div class="d-flex align-items-center tt-info-icons">



                                    <span class="text-muted px-1" data-bs-toggle="tooltip" data-bs-placement="top"

                                        data-bs-title="{{ localize('If this is enabled, user will be able to use unlimited balance.') }}"><i

                                            data-feather="activity"></i></span>



                                    <span class="text-muted px-1 ms-2" data-bs-toggle="tooltip" data-bs-placement="top"

                                        data-bs-title="{{ localize('If this is checkd, it will be shown in the subscription plan list') }}"><i

                                            data-feather="help-circle"></i></span>



                                    <span class="text-muted px-1 ms-2" data-bs-toggle="tooltip" data-bs-placement="top"

                                        data-bs-title="{{ localize('If this is enabled, user will be able to use the feature.') }}"><i

                                            data-feather="power"></i></span>





                                </div>

                            </div>

                        </li>


                        @if ($package->package_type != 'starter')

                            <li>

                                <div class="d-flex align-items-center justify-content-between">

                                    <div class="d-flex align-items-center">

                                        <span><i data-feather="check-circle"

                                                class="icon-14 me-2 text-success"></i><strong class=""

                                                data-name="package-words-{{ $package->id }}">

                                                {{ localize('Discount') }}</strong>

                                        </span>

                                    </div>



                                    <div class="d-flex align-items-center">

                                        <div class="form-check form-switch">

                                            <input type="checkbox"

                                                class="form-check-input cursor-pointer allow_discount tt_editable"

                                                data-id="{{ $package->id }}" id="allow_discount-{{ $package->id }}"

                                                data-name="allow_discount-{{ $package->id }}"

                                                @if ($package->discount_status == 1) checked @endif>

                                        </div>



                                    </div>

                                </div>



                            </li>

                            <li class="discount_option {{ $package->discount_status != 1 ? 'd-none' : '' }}"

                                id="discount_option_{{ $package->id }}">

                                <div class="d-flex align-items-center justify-content-between">

                                    <div class="d-flex align-items-center">

                                        <select

                                            class="form-select py-1 discount_type cursor-pointer discount_type_{{ $package->id }}"

                                            name="discount_type" onchange="handleDiscountTypeChange(this)">

                                            <option value="1"

                                                {{ $package->discount_type == 1 ? 'selected' : '' }}>

                                                {{ localize('Fixed') }}</option>

                                            <option value="2"

                                                {{ $package->discount_type == 2 ? 'selected' : '' }}>

                                                {{ localize('Percentage') }}</option>



                                        </select>

                                    </div>



                                    <div class="d-flex align-items-center">

                                        <input

                                            class="form-control py-1 discount_amount package-discount-{{ $package->id }}"

                                            type="text" onkeypress="nonNumericFilter()" name="discount"

                                            placeholder="{{ localize('discount') }}"

                                            value="{{ $package->discount }}" />

                                    </div>

                                </div>



                            </li>

                        @endif

                        @if (getSetting('enable_built_in_templates') != '0' ||

                                getSetting('enable_ai_chat') != '0' ||

                                getSetting('enable_ai_code') != '0' ||

                                getSetting('enable_custom_templates') != '0')

                            <li>

                                <div class="d-flex align-items-center justify-content-between">

                                    <div class="d-flex align-items-center">

                                        <span><i data-feather="check-circle"

                                                class="icon-14 me-2 text-success"></i>

                                                <strong class="tt_update_text" id="allow_word_text_{{ $package->id }}"

                                                data-name="package-words-{{ $package->id }}"

                                                onkeypress="nonNumericFilter()">{{ $package->allow_unlimited_word == 1 ? localize('Unlimited') : $package->total_words_per_month }}</strong>

                                            {{ $package->package_type != 'prepaid' && $package->package_type != 'starter' ? localize('Words per month') : localize('Words') }} </span>

                                        <span class="tt-edit-icon ms-2 text-muted {{$package->allow_unlimited_word == 1 ? 'd-none' : ''}}" id="allow_word_edit_{{ $package->id }}"><i

                                                class="tt_editable cursor-pointer icon-14"

                                                data-name="package-words-{{ $package->id }}"

                                                data-feather="edit"></i></span>

                                    </div>



                                    <div class="d-flex align-items-center">

                                        <div class="form-check tt-checkbox">

                                            <input class="form-check-input cursor-pointer  unlimited_balance unlimited_word" type="checkbox"

                                                id="allow_unlimited_word-{{ $package->id }}"

                                                data-name="allow_unlimited_word-{{ $package->id }}"

                                                @if ($package->allow_unlimited_word == 1) checked @endif>

                                        </div>

                                        <div class="form-check tt-checkbox">

                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox"

                                                id="show_word_tools-{{ $package->id }}"

                                                data-name="show_word_tools-{{ $package->id }}"

                                                @if ($package->show_word_tools == 1) checked @endif>

                                        </div>



                                        <div class="form-check form-switch">

                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable"

                                                id="allow_word_tools-{{ $package->id }}"

                                                data-name="allow_word_tools-{{ $package->id }}"

                                                @if ($package->allow_word_tools == 1) checked @endif>

                                        </div>

                                    </div>

                                </div>



                                <ul class="list-unstyled ms-4 my-2">

                                    @if (getSetting('enable_built_in_templates') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <div class="d-flex align-items-center">

                                                @php

                                                    $packageTemplatesCounter = $package->subscription_package_templates()->count();

                                                @endphp

                                                <span>- <strong>{{ $packageTemplatesCounter }}</strong>

                                                    {{ localize('AI Templates') }} </span>

                                                <span class="tt-edit-icon ms-2 text-muted"><i

                                                        class="cursor-pointer icon-14"

                                                        data-name="package-template-{{ $package->id }}"

                                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"

                                                        data-feather="edit"

                                                        onclick="getPackageTemplates(this)"></i></span>

                                            </div>



                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox"

                                                        id="show_built_in_templates-{{ $package->id }}"

                                                        data-name="show_built_in_templates-{{ $package->id }}"

                                                        @if ($package->show_built_in_templates == 1) checked @endif>

                                                </div>



                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        id="allow_built_in_templates-{{ $package->id }}"

                                                        data-name="allow_built_in_templates-{{ $package->id }}"

                                                        @if ($package->allow_built_in_templates == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif





                                    @if (getSetting('enable_ai_chat') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_ai_chat-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('AI Chat') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_ai_chat-{{ $package->id }}"

                                                        data-name="show_ai_chat-{{ $package->id }}"

                                                        @if ($package->show_ai_chat == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        id="allow_ai_chat-{{ $package->id }}"

                                                        data-name="allow_ai_chat-{{ $package->id }}"

                                                        @if ($package->allow_ai_chat == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_real_time_data-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('Chat Real Time Data') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_real_time_data-{{ $package->id }}"

                                                        data-name="show_real_time_data-{{ $package->id }}"

                                                        @if ($package->show_real_time_data == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        id="allow_real_time_data-{{ $package->id }}"

                                                        data-name="allow_real_time_data-{{ $package->id }}"

                                                        @if ($package->allow_real_time_data == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif

                                    @if (getSetting('enable_ai_rewriter') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_ai_rewriter-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('AI ReWriter') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_ai_rewriter-{{ $package->id }}"

                                                        data-name="show_ai_rewriter-{{ $package->id }}"

                                                        @if ($package->show_ai_rewriter == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        id="allow_ai_rewriter-{{ $package->id }}"

                                                        data-name="allow_ai_rewriter-{{ $package->id }}"

                                                        @if ($package->allow_ai_rewriter == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif

                                    @if (getSetting('enable_ai_vision') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_ai_vision-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('AI Vision') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_ai_vision-{{ $package->id }}"

                                                        data-name="show_ai_vision-{{ $package->id }}"

                                                        @if ($package->show_ai_vision == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        id="allow_ai_vision-{{ $package->id }}"

                                                        data-name="allow_ai_vision-{{ $package->id }}"

                                                        @if ($package->allow_ai_vision == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif



                                    @if (getSetting('enable_ai_pdf_chat') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                        <span>- <label for="allow_ai_vision-{{ $package->id }}"

                                                       class="cursor-pointer">{{ localize('AI PDF Chat') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                           type="checkbox" id="show_ai_pdf_chat-{{ $package->id }}"

                                                           data-name="show_ai_pdf_chat-{{ $package->id }}"

                                                           @if ($package->show_ai_pdf_chat == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                           class="form-check-input cursor-pointer tt_editable"

                                                           id="allow_ai_pdf_chat-{{ $package->id }}"

                                                           data-name="allow_ai_pdf_chat-{{ $package->id }}"

                                                           @if ($package->allow_ai_pdf_chat == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif



                                    @if (getSetting('enable_ai_code') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_ai_code-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('AI Code') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_ai_code-{{ $package->id }}"

                                                        data-name="show_ai_code-{{ $package->id }}"

                                                        @if ($package->show_ai_code == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        id="allow_ai_code-{{ $package->id }}"

                                                        data-name="allow_ai_code-{{ $package->id }}"

                                                        @if ($package->allow_ai_code == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif



                                    @if (getSetting('enable_text_to_speech') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_text_to_speech-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('Text to Speech') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_text_to_speech-{{ $package->id }}"

                                                        data-name="show_text_to_speech-{{ $package->id }}"

                                                        @if ($package->show_text_to_speech == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        data-name="allow_text_to_speech-{{ $package->id }}"

                                                        id="allow_text_to_speech-{{ $package->id }}"

                                                        @if ($package->allow_text_to_speech == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif



                                    @if (getSetting('enable_custom_templates') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_custom_templates-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('Custom Templates') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox"

                                                        id="show_custom_templates-{{ $package->id }}"

                                                        data-name="show_custom_templates-{{ $package->id }}"

                                                        @if ($package->show_custom_templates == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        data-name="allow_custom_templates-{{ $package->id }}"

                                                        id="allow_custom_templates-{{ $package->id }}"

                                                        @if ($package->allow_custom_templates == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif



                                    {{-- blog wizard --}}

                                    @if (getSetting('enable_blog_wizard') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_blog_wizard-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('AI Blog Wizard') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_blog_wizard-{{ $package->id }}"

                                                        data-name="show_blog_wizard-{{ $package->id }}"

                                                        @if ($package->show_blog_wizard == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        data-name="allow_blog_wizard-{{ $package->id }}"

                                                        id="allow_blog_wizard-{{ $package->id }}"

                                                        @if ($package->allow_blog_wizard == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif

                                    {{-- blog wizard --}}



                                    {{-- blog wizard --}}

                                    @if (getSetting('enable_eleven_labs') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_eleven_labs-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('ElevenLabs') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_eleven_labs-{{ $package->id }}"

                                                        data-name="show_eleven_labs-{{ $package->id }}"

                                                        @if ($package->show_eleven_labs == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        data-name="allow_eleven_labs-{{ $package->id }}"

                                                        id="allow_eleven_labs-{{ $package->id }}"

                                                        @if ($package->allow_eleven_labs == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif

                                    {{-- blog wizard --}}

                                    {{-- AI ai_plagiarism --}}

                                    @if (getSetting('enable_ai_plagiarism') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_ai_plagiarism-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('Ai Plagiarism') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_ai_plagiarism-{{ $package->id }}"

                                                        data-name="show_ai_plagiarism-{{ $package->id }}"

                                                        @if ($package->show_ai_plagiarism == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        data-name="allow_ai_plagiarism-{{ $package->id }}"

                                                        id="allow_ai_plagiarism-{{ $package->id }}"

                                                        @if ($package->allow_ai_plagiarism == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif

                                    {{-- AI plagiarism --}}



                                    {{-- AI _ai_detector --}}

                                    @if (getSetting('enable_ai_detector') != '0')

                                        <li class="p-0 d-flex justify-content-between align-items-center">

                                            <span>- <label for="allow_ai_detector-{{ $package->id }}"

                                                    class="cursor-pointer">{{ localize('Ai Detector') }}</label></span>

                                            <div class="d-flex align-items-center">

                                                <div class="form-check tt-checkbox">

                                                    <input class="form-check-input cursor-pointer tt_editable"

                                                        type="checkbox" id="show_ai_detector-{{ $package->id }}"

                                                        data-name="show_ai_detector-{{ $package->id }}"

                                                        @if ($package->show_ai_detector == 1) checked @endif>

                                                </div>

                                                <div class="form-check form-switch">

                                                    <input type="checkbox"

                                                        class="form-check-input cursor-pointer tt_editable"

                                                        data-name="allow_ai_detector-{{ $package->id }}"

                                                        id="allow_ai_detector-{{ $package->id }}"

                                                        @if ($package->allow_ai_detector == 1) checked @endif>

                                                </div>

                                            </div>

                                        </li>

                                    @endif

                                    {{-- AI plagiarism --}}

                                </ul>

                            </li>

                        @endif



                        @if (getSetting('enable_ai_images') != '0')

                            <li>

                                <div class="d-flex align-items-center justify-content-between">

                                    <div class="d-flex align-items-center">

                                        <span><i data-feather="check-circle"

                                                class="icon-14 me-2 text-success"></i><strong class="tt_update_text"

                                                data-name="package-images-{{ $package->id }}" id="allow_image_text_{{ $package->id }}"

                                                onkeypress="nonNumericFilter()">{{ $package->allow_unlimited_image == 1 ? localize('Unlimited') : $package->total_images_per_month }}</strong>

                                            {{ $package->package_type != 'prepaid' ? localize('Images per month') : localize('Images') }}</span>

                                        <span class="tt-edit-icon ms-2 text-muted {{$package->allow_unlimited_image == 1 ? 'd-none' : ''}}" id="allow_image_edit_{{ $package->id }}"><i

                                                class="tt_editable cursor-pointer icon-14"

                                                data-name="package-images-{{ $package->id }}"

                                                data-feather="edit"></i></span>

                                    </div>



                                    <div class="d-flex align-items-center">

                                        <div class="form-check tt-checkbox">

                                            <input type="checkbox" class="form-check-input cursor-pointer  unlimited_balance"

                                                data-name="allow_unlimited_image-{{ $package->id }}"

                                                id="allow_unlimited_image-{{ $package->id }}"

                                                @if ($package->allow_unlimited_image == 1) checked @endif>

                                        </div>

                                        <div class="form-check tt-checkbox">

                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox"

                                                id="show_image_tools-{{ $package->id }}"

                                                data-name="show_image_tools-{{ $package->id }}"

                                                @if ($package->show_image_tools == 1) checked @endif>

                                        </div>

                                        <div class="form-check form-switch">

                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable"

                                                data-name="allow_image_tools-{{ $package->id }}"

                                                id="allow_image_tools-{{ $package->id }}"

                                                @if ($package->allow_image_tools == 1) checked @endif>

                                        </div>

                                    </div>

                                </div>



                                <ul class="list-unstyled ms-4 my-2">

                                    

                                    <li class="p-0 d-flex justify-content-between align-items-center">

                                        <span>- <label for="allow_dall_e_2_image-{{ $package->id }}"

                                                class="cursor-pointer">{{ localize('Dall E 2') }}</label></span>

                                        <div class="d-flex align-items-center">



                                            <div class="form-check tt-checkbox">

                                                <input class="form-check-input cursor-pointer tt_editable"

                                                    type="checkbox" id="show_dall_e_2_image-{{ $package->id }}"

                                                    data-name="show_dall_e_2_image-{{ $package->id }}"

                                                    @if ($package->show_dall_e_2_image == 1) checked @endif>

                                            </div>

                                            <div class="form-check form-switch">

                                                <input type="checkbox"

                                                    class="form-check-input cursor-pointer tt_editable"

                                                    id="allow_dall_e_2_image-{{ $package->id }}"

                                                    data-name="allow_dall_e_2_image-{{ $package->id }}"

                                                    @if ($package->allow_dall_e_2_image == 1) checked @endif>

                                            </div>

                                        </div>

                                    </li>

                                    <li class="p-0 d-flex justify-content-between align-items-center">

                                        <span>- <label for="allow_dall_e_3_image-{{ $package->id }}"

                                                class="cursor-pointer">{{ localize('Dall E 3') }}</label></span>

                                        <div class="d-flex align-items-center">



                                            <div class="form-check tt-checkbox">

                                                <input class="form-check-input cursor-pointer tt_editable"

                                                    type="checkbox" id="show_dall_e_3_image-{{ $package->id }}"

                                                    data-name="show_dall_e_3_image-{{ $package->id }}"

                                                    @if ($package->show_dall_e_3_image == 1) checked @endif>

                                            </div>

                                            <div class="form-check form-switch">

                                                <input type="checkbox"

                                                    class="form-check-input cursor-pointer tt_editable"

                                                    id="allow_dall_e_3_image-{{ $package->id }}"

                                                    data-name="allow_dall_e_3_image-{{ $package->id }}"

                                                    @if ($package->allow_dall_e_3_image == 1) checked @endif>

                                            </div>

                                        </div>

                                    </li>



                                    <li class="p-0 d-flex justify-content-between align-items-center">

                                        <span>- <label for="allow_sd_images-{{ $package->id }}"

                                                class="cursor-pointer">{{ localize('Stable Diffusion') }}</label></span>

                                        <div class="d-flex align-items-center">

                                            <div class="form-check tt-checkbox">

                                                <input class="form-check-input cursor-pointer tt_editable"

                                                    type="checkbox" id="show_sd_images-{{ $package->id }}"

                                                    data-name="show_sd_images-{{ $package->id }}"

                                                    @if ($package->show_sd_images == 1) checked @endif>

                                            </div>

                                            <div class="form-check form-switch">

                                                <input type="checkbox"

                                                    class="form-check-input cursor-pointer tt_editable"

                                                    id="allow_sd_images-{{ $package->id }}"

                                                    data-name="allow_sd_images-{{ $package->id }}"

                                                    @if ($package->allow_sd_images == 1) checked @endif>

                                            </div>

                                        </div>

                                    </li>

                                    @if (getSetting('enable_ai_image_chat') != '0')

                                    <li class="p-0 d-flex justify-content-between align-items-center">

                                        <span>- <label for="allow_ai_image_chat-{{ $package->id }}"

                                                class="cursor-pointer">{{ localize('Chat Image') }}</label></span>

                                        <div class="d-flex align-items-center">

                                            <div class="form-check tt-checkbox">

                                                <input class="form-check-input cursor-pointer tt_editable"

                                                    type="checkbox" id="show_ai_image_chat-{{ $package->id }}"

                                                    data-name="show_ai_image_chat-{{ $package->id }}"

                                                    @if ($package->show_ai_image_chat == 1) checked @endif>

                                            </div>

                                            <div class="form-check form-switch">

                                                <input type="checkbox"

                                                    class="form-check-input cursor-pointer tt_editable"

                                                    id="allow_ai_image_chat-{{ $package->id }}"

                                                    data-name="allow_ai_image_chat-{{ $package->id }}"

                                                    @if ($package->allow_ai_image_chat == 1) checked @endif>

                                            </div>

                                        </div>

                                    </li>

                                    @endif

                                </ul>

                            </li>

                        @endif



                        @if (getSetting('enable_speech_to_text') != '0')

                            <li>

                                <div class="d-flex align-items-center justify-content-between">

                                    <div class="d-flex align-items-center">

                                        <span><i data-feather="check-circle"

                                                class="icon-14 me-2 text-success"></i>

                                                <strong class="tt_update_text"

                                                data-name="package-speech-to-text-{{ $package->id }}" id="allow_speech_to_text_text_{{ $package->id }}"

                                                onkeypress="nonNumericFilter()">{{ $package->allow_unlimited_speech_to_text == 1 ? localize('Unlimited') : $package->total_speech_to_text_per_month }}</strong>

                                            {{ $package->package_type != 'prepaid' ? localize('Speech to Text per month') : localize('Speech to Texts') }}</span>

                                        <span class="tt-edit-icon ms-2 text-muted {{$package->allow_unlimited_speech_to_text == 1 ? 'd-none' : ''}}"><i

                                                class="tt_editable cursor-pointer icon-14" id="allow_speech_to_text_edit_{{ $package->id }}"

                                                data-name="package-speech-to-text-{{ $package->id }}"

                                                data-feather="edit"></i></span>

                                    </div>



                                    <div class="d-flex align-items-center">

                                        <div class="form-check tt-checkbox">

                                            <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance"

                                                data-name="allow_unlimited_speech_to_text-{{ $package->id }}"

                                                id="allow_unlimited_speech_to_text-{{ $package->id }}"

                                                @if ($package->allow_unlimited_speech_to_text == 1) checked @endif>

                                        </div>

                                        <div class="form-check tt-checkbox">

                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox"

                                                id="show_speech_to_text_tools-{{ $package->id }}"

                                                data-name="show_speech_to_text_tools-{{ $package->id }}"

                                                @if ($package->show_speech_to_text_tools == 1) checked @endif>

                                        </div>

                                        <div class="form-check form-switch">

                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable"

                                                data-name="allow_speech_to_text-{{ $package->id }}"

                                                id="allow_speech_to_text-{{ $package->id }}"

                                                @if ($package->allow_speech_to_text == 1) checked @endif>

                                        </div>

                                    </div>

                                </div>



                                <ul class="list-unstyled ms-4 my-2">

                                    <li class="p-0 d-flex justify-content-between align-items-center">

                                        <div class="d-flex align-items-center">

                                            <span>- </i><strong class="tt_update_text"

                                                    data-name="package-audio-size-{{ $package->id }}"

                                                    onkeypress="nonNumericFilter()">{{ $package->speech_to_text_filesize_limit }}</strong>

                                                MB {{ localize('Audio file size limit') }}</span>

                                            <span class="tt-edit-icon ms-2 text-muted"><i

                                                    class="tt_editable cursor-pointer icon-14"

                                                    data-name="package-audio-size-{{ $package->id }}"

                                                    data-feather="edit"></i></span>

                                        </div>

                                    </li>

                                </ul>

                            </li>

                        @endif



                        <li class="d-flex justify-content-between align-items-center">

                            <span><i data-feather="check-circle" class="icon-14 me-2 text-success"></i><label

                                    for="is_featured-{{ $package->id }}"

                                    class="cursor-pointer">{{ localize('Is Featured?') }}</label></span>

                            <div class="form-check form-switch ms-2">

                                <input type="checkbox" class="form-check-input cursor-pointer tt_editable"

                                    id="is_featured-{{ $package->id }}"

                                    data-name="is_featured-{{ $package->id }}"

                                    @if ($package->is_featured == 1) checked @endif data-bs-toggle="tooltip"

                                    data-bs-placement="top"

                                    data-bs-title="{{ localize('If this is enabled, a recommend badge will be shown in the subscription plan.') }}">

                            </div>

                        </li>



                        <li class="d-flex justify-content-between align-items-center">

                            <div class="d-flex align-items-center">

                                <span><i data-feather="check-circle" class="icon-14 me-2 text-success"></i><label

                                        for="has_live_support-{{ $package->id }}"

                                        class="cursor-pointer">{{ localize('Live Support') }}</label></span>

                            </div>



                            <div class="d-flex align-items-center">

                                <div class="form-check tt-checkbox">

                                    <input class="form-check-input cursor-pointer tt_editable" type="checkbox"

                                        id="show_live_support-{{ $package->id }}"

                                        data-name="show_live_support-{{ $package->id }}"

                                        @if ($package->show_live_support == 1) checked @endif>

                                </div>



                                <div class="form-check form-switch">

                                    <input type="checkbox" class="form-check-input cursor-pointer tt_editable"

                                        data-name="has_live_support-{{ $package->id }}"

                                        id="has_live_support-{{ $package->id }}"

                                        @if ($package->has_live_support == 1) checked @endif data-bs-toggle="tooltip"

                                        data-bs-placement="top"

                                        data-bs-title="{{ localize('If this is enabled, you have to provide live support to the users.') }}">

                                </div>

                            </div>

                        </li>


                        <li class="d-flex justify-content-between align-items-center">

                            <div class="d-flex align-items-center">

                                <span><i data-feather="check-circle" class="icon-14 me-2 text-success"></i><label

                                        for="has_free_support-{{ $package->id }}"

                                        class="cursor-pointer">{{ localize('Free Support') }}</label></span>

                            </div>



                            <div class="d-flex align-items-center">

                                <div class="form-check tt-checkbox">

                                    <input class="form-check-input cursor-pointer tt_editable" type="checkbox"

                                        id="show_free_support-{{ $package->id }}"

                                        data-name="show_free_support-{{ $package->id }}"

                                        @if ($package->show_free_support == 1) checked @endif>

                                </div>



                                <div class="form-check form-switch">

                                    <input type="checkbox" class="form-check-input cursor-pointer tt_editable"

                                        data-name="has_free_support-{{ $package->id }}"

                                        id="has_free_support-{{ $package->id }}"

                                        @if ($package->has_free_support == 1) checked @endif data-bs-toggle="tooltip"

                                        data-bs-placement="top"

                                        data-bs-title="{{ localize('If this is enabled, you have to provide free support to the users.') }}">

                                </div>

                            </div>

                        </li>



                        <li class="d-flex justify-content-between align-items-center w-100">

                            <div class="d-flex align-items-center flex-grow-1">

                                <i data-feather="check-circle" class="icon-14 me-2 text-success"></i>

                                <select class="form-select py-1 package_open_ai_model" name="openai_model_id"

                                    onchange="handleModelChange(this)">

                                    <option value="" disabled>{{ localize('Select Open AI Model') }}</option>

                                    @foreach ($openAiModels as $openAiModel)

                                        <option value="{{ $openAiModel->id }}"

                                            @if ($package->openai_model_id == $openAiModel->id) selected @endif>

                                            {{ $openAiModel->name }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>



                            <div class="ms-3 d-flex align-items-center justify-content-end">

                                <div class="form-check tt-checkbox" data-bs-toggle="tooltip" data-bs-placement="top"

                                    data-bs-title="{{ localize('If this is checkd, it will be shown in the subscription plan list') }}">

                                    <input class="form-check-input cursor-pointer tt_editable" type="checkbox"

                                        id="show_open_ai_model-{{ $package->id }}"

                                        data-name="show_open_ai_model-{{ $package->id }}"

                                        @if ($package->show_open_ai_model == 1) checked @endif>

                                </div>

                            </div>

                        </li>



                        <li class="d-flex flex-column align-items-start">

                            <div class="w-100 d-flex align-items-center">

                                <i data-feather="check-circle" class="icon-14 me-2 text-success"></i>

                                <input class="form-control py-1 other_features" type="text"

                                    placeholder="{{ localize('Type additional features') }}"

                                    value="{{ $package->other_features }}" />

                            </div>

                            <small class="text-muted ps-4">*

                                {{ localize('Comma separated: Feature A,Feature B') }}</small>

                        </li>

                        {{-- duration add for starter pacakge --}}

                        @if ($package->package_type == 'starter')

                            <li class="d-flex flex-column align-items-start">

                                <div class="w-100 d-flex align-items-center">

                                    <i data-feather="check-circle" class="icon-14 me-2 text-success"></i>

                                    <input class="form-control py-1 duration" type="text"

                                        onkeypress="nonNumericFilter()"

                                        placeholder="{{ localize('30') }}"

                                        value="{{ $package->duration }}" />

                                </div>

                                <small class="text-muted ps-4">*

                                    {{ localize('Expire in number in days for Starter Package') }}</small>

                            </li>

                        @endif

                        {{-- end --}}



                    </ul>

                </div>

            </div>

            <div class="card-footer">

                <div>

                    <div class="d-flex justify-content-between">

                        <div class="d-flex align-items-center">

                            <span class="ms-1"><label for="is_active-{{ $package->id }}"

                                    class="cursor-pointer">{{ localize('Is Active?') }}</label></span>

                            <div class="form-check form-switch ms-2">

                                <input type="checkbox" class="form-check-input cursor-pointer tt_editable"

                                    id="is_active-{{ $package->id }}" data-name="is_active-{{ $package->id }}"

                                    @if ($package->is_active == 1) checked @endif>

                            </div>

                        </div>



                        @if ($package->package_type != 'starter')

                            <div>

                                <i class="text-danger cursor-pointer icon-16" data-feather="trash"

                                    data-bs-toggle="tooltip" data-bs-placement="top"

                                    data-bs-title="{{ localize('Delete this package') }}"

                                    onclick="confirmDelete(this)"

                                    data-href="{{ route('subscriptions.delete', $package->id) }}"></i>

                            </div>

                        @endif



                    </div>

                    @if ($package->package_type == 'starter')

                        <small class="text-muted">*

                            {{ localize('If active, this will be applied to new user\'s registration.') }}

                        </small>

                    @endif

                </div>

            </div>

        </div>

    </div> -->


    <div class="col-12 col-lg-4">
        <input type="hidden" value="1" class="package_id" />

        <div class="card h-100 package-card">
            <div class="card-body">
                <div class="tt-pricing-plan">
                    <div class="tt-plan-name">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 tt_update_text" data-name="package-name-1">
                                Free Trial (60 Days)
                            </h5>
                            
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted tt_update_text" data-name="package-description-1">Get started with our starter package</span>
                            
                        </div>
                    </div>

                    <div class="tt-price-wrap d-flex align-items-center justify-content-between mt-4 mb-3">
                        <div class="monthly-price fs-1 fw-bold">
                            Free
                        </div>
                    </div>
                </div>

                <div class="tt-pricing-feature">
                    <ul class="tt-pricing-feature list-unstyled rounded mb-0">
                        

                        <!-- 1 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg    
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" id="allow_word_text_1" data-name="package-words-1" onkeypress="nonNumericFilter()">1. Chat-Based Interaction</strong>
                                        
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer unlimited_balance unlimited_word" type="checkbox" id="allow_unlimited_word-1" data-name="allow_unlimited_word-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_word_tools-1" data-name="show_word_tools-1" checked="" />
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_word_tools-1" data-name="allow_word_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Guided goal entry with live prompt support </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Copilot tone response with goal validation & clarification  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./1 -->

                        <!-- 2 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">2. Document Scan</strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Auto-scan up to 3 internal documents (uploaded by user)  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Display policy/document excerpts in context    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./2 -->

                        <!-- 3 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">3. Goal Assessment </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Auto-label complexity and resource need   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Strategic aggressiveness tag (Aggressive, Moderate, Easy)  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./3 -->


                        <!-- 4 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">4. Scoring Engine </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Impact / Feasibility / Alignment scoring with summary   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong>  Historical benchmark comparison    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./4 -->

                        <!-- 5 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">5. Decision Map </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> 3-option strategy map based on persona-goal  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked=""/>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong>   Decision simulations with effort/risk overlay    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./5 -->

                        <!-- 6 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">6. Scenario Generation </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> A/B forecasting scenarios (basic format)  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong>  Recommend preferred path    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./6 -->

                        <!-- 7 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">7. Rephrased Goal Mapping </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Up to 3 role-based goal rephrasings   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./7 -->

                        <!-- 8 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Complementary Goals  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Suggested synergy goals (text only)   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./8 -->

                        <!-- 9 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Outcome Summary  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Standardized 1-page export per goal    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./9 -->

                        <!-- 10 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Collaboration Tools  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Save, share, and track goal assessments     </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./10 -->

                        <!-- 11 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Integration & API Access   </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            
                        </li>
                        <!-- ./11 -->


                        <!-- 12 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Analytics Dashboard </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Goal performance tracking and adoption heatmaps </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./12 -->

                        <!-- 13 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Persona Management  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> 1 persona per user  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./13 -->

                        <!-- 14 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Admin & Settings   </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Basic goal log for user   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./14 -->

                        
                     

                        <!-- <li class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-check-circle icon-14 me-2 text-success"
                                    >
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <label for="has_free_support-1" class="cursor-pointer">Free Support</label>
                                </span>
                            </div>

                            <div class="d-flex align-items-center">
                                <div class="form-check tt-checkbox">
                                    <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_free_support-1" data-name="show_free_support-1" checked="" />
                                </div>

                                <div class="form-check form-switch">
                                    <input
                                        type="checkbox"
                                        class="form-check-input cursor-pointer tt_editable"
                                        data-name="has_free_support-1"
                                        id="has_free_support-1"
                                        checked=""
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-title="If this is enabled, you have to provide free support to the users."
                                    />
                                </div>
                            </div>
                        </li>

                        <li class="d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center flex-grow-1">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="feather feather-check-circle icon-14 me-2 text-success"
                                >
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <select class="form-select py-1 package_open_ai_model" name="openai_model_id" onchange="handleModelChange(this)">
                                    <option value="" disabled="">Select Open AI Model</option>
                                    <option value="2">
                                        ChatGPT 3.5
                                    </option>
                                    <option value="3">
                                        ChatGPT 4
                                    </option>
                                    <option value="4">
                                        ChatGPT 3.5 Turbo-16k
                                    </option>
                                    <option value="5" selected="">
                                        Updated GPT 3.5 Turbo
                                    </option>
                                    <option value="6">
                                        ChatGPT 4 Gpt-4-32k
                                    </option>
                                    <option value="7">
                                        GPT-4 Turbo
                                    </option>
                                    <option value="8">
                                        GPT-4o
                                    </option>
                                    <option value="9">
                                        Gpt 4o mini
                                    </option>
                                    <option value="10">
                                        Gpt 4o mini 2024 07 18
                                    </option>
                                    <option value="11">
                                        Chatgpt 4o latest
                                    </option>
                                    <option value="12">
                                        Claude 3 Opus
                                    </option>
                                    <option value="13">
                                        Claude 3.5 Sonnet
                                    </option>
                                    <option value="14">
                                        DeepSeek V3
                                    </option>
                                </select>
                            </div>

                            <div class="ms-3 d-flex align-items-center justify-content-end">
                                <div class="form-check tt-checkbox" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="If this is checkd, it will be shown in the subscription plan list">
                                    <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_open_ai_model-1" data-name="show_open_ai_model-1" checked="" />
                                </div>
                            </div>
                        </li>

                        <li class="d-flex flex-column align-items-start">
                            <div class="w-100 d-flex align-items-center">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="feather feather-check-circle icon-14 me-2 text-success"
                                >
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <input class="form-control py-1 other_features" type="text" placeholder="Type additional features" value="" />
                            </div>
                            <small class="text-muted ps-4">* Comma separated: Feature A,Feature B</small>
                        </li>

                        <li class="d-flex flex-column align-items-start">
                            <div class="w-100 d-flex align-items-center">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="feather feather-check-circle icon-14 me-2 text-success"
                                >
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <input class="form-control py-1 duration" type="text" onkeypress="nonNumericFilter()" placeholder="30" value="30" />
                            </div>
                            <small class="text-muted ps-4">* Expire in number in days for Starter Package</small>
                        </li> -->
                    </ul>
                </div>
            </div>
            <div class="card-footer">
                <div>
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="ms-1"><label for="is_active-1" class="cursor-pointer">Is Active?</label></span>
                            <div class="form-check form-switch ms-2">
                                <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="is_active-1" data-name="is_active-1" checked="" />
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">* If active, this will be applied to new user's registration. </small>
                </div>
            </div>
        </div>
    </div>


    <div class="col-12 col-lg-4">
        <input type="hidden" value="2" class="package_id" />

        <div class="card h-100 package-card">
            <div class="card-body">
                <div class="tt-pricing-plan">
                    <div class="tt-plan-name">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 tt_update_text" data-name="package-name-1">
                                Premium (Post-Trial) 
                            </h5>
                            
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted tt_update_text" data-name="package-description-1">Get started with our starter package</span>
                            
                        </div>
                    </div>

                    <div class="tt-price-wrap d-flex align-items-center justify-content-between mt-4 mb-3">
                        <div class="monthly-price fs-1 fw-bold">
                            Premium
                        </div>
                    </div>
                </div>

                <div class="tt-pricing-feature">
                    <ul class="tt-pricing-feature list-unstyled rounded mb-0">
                        

                        <!-- 1 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg    
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" id="allow_word_text_1" data-name="package-words-1" onkeypress="nonNumericFilter()">1. Chat-Based Interaction</strong>
                                        
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer unlimited_balance unlimited_word" type="checkbox" id="allow_unlimited_word-1" data-name="allow_unlimited_word-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_word_tools-1" data-name="show_word_tools-1" checked="" />
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_word_tools-1" data-name="allow_word_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Guided goal entry with live prompt support </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Copilot tone response with goal validation & clarification  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./1 -->

                        <!-- 2 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">2. Document Scan</strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Auto-scan up to 3 internal documents (uploaded by user)  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Display policy/document excerpts in context    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./2 -->

                        <!-- 3 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">3. Goal Assessment </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Auto-label complexity and resource need   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Strategic aggressiveness tag (Aggressive, Moderate, Easy)  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./3 -->


                        <!-- 4 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">4. Scoring Engine </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Impact / Feasibility / Alignment scoring with summary   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong>  Historical benchmark comparison    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./4 -->

                        <!-- 5 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">5. Decision Map </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> 3-option strategy map based on persona-goal  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked=""/>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong>   Decision simulations with effort/risk overlay    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./5 -->

                        <!-- 6 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">6. Scenario Generation </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> A/B forecasting scenarios (basic format)  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong>  Recommend preferred path    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <!-- ./6 -->

                        <!-- 7 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">7. Rephrased Goal Mapping </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Up to 3 role-based goal rephrasings   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./7 -->

                        <!-- 8 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Complementary Goals  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Suggested synergy goals (text only)   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./8 -->

                        <!-- 9 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Outcome Summary  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Standardized 1-page export per goal    </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./9 -->

                        <!-- 10 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Collaboration Tools  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Save, share, and track goal assessments     </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./10 -->

                        <!-- 11 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Integration & API Access   </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            
                        </li>
                        <!-- ./11 -->


                        <!-- 12 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Analytics Dashboard </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Goal performance tracking and adoption heatmaps </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./12 -->

                        <!-- 13 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Persona Management  </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> 1 persona per user  </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./13 -->

                        <!-- 14 -->
                        <li>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather feather-check-circle icon-14 me-2 text-success"
                                        >
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        <strong class="tt_update_text" data-name="package-images-1" id="allow_image_text_1" onkeypress="nonNumericFilter()">Admin & Settings   </strong>   
                                    </span>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="form-check tt-checkbox">
                                        <input type="checkbox" class="form-check-input cursor-pointer unlimited_balance" data-name="allow_unlimited_image-1" id="allow_unlimited_image-1" />
                                    </div>
                                    <div class="form-check tt-checkbox">
                                        <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_image_tools-1" data-name="show_image_tools-1" checked="" />
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input cursor-pointer tt_editable" data-name="allow_image_tools-1" id="allow_image_tools-1" checked="" />
                                    </div>
                                </div>
                            </div>

                            <ul class="list-unstyled ms-4 my-2">
                                <li class="p-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span><strong> - </strong> Basic goal log for user   </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="form-check tt-checkbox">
                                            <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_built_in_templates-1" data-name="show_built_in_templates-1" checked="" />
                                        </div>

                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="allow_built_in_templates-1" data-name="allow_built_in_templates-1" checked="" />
                                        </div>
                                    </div>
                                </li>
                              
                            </ul>
                        </li>
                        <!-- ./14 -->

                        
                     

                        <!-- <li class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-check-circle icon-14 me-2 text-success"
                                    >
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <label for="has_free_support-1" class="cursor-pointer">Free Support</label>
                                </span>
                            </div>

                            <div class="d-flex align-items-center">
                                <div class="form-check tt-checkbox">
                                    <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_free_support-1" data-name="show_free_support-1" checked="" />
                                </div>

                                <div class="form-check form-switch">
                                    <input
                                        type="checkbox"
                                        class="form-check-input cursor-pointer tt_editable"
                                        data-name="has_free_support-1"
                                        id="has_free_support-1"
                                        checked=""
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-title="If this is enabled, you have to provide free support to the users."
                                    />
                                </div>
                            </div>
                        </li>

                        <li class="d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center flex-grow-1">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="feather feather-check-circle icon-14 me-2 text-success"
                                >
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <select class="form-select py-1 package_open_ai_model" name="openai_model_id" onchange="handleModelChange(this)">
                                    <option value="" disabled="">Select Open AI Model</option>
                                    <option value="2">
                                        ChatGPT 3.5
                                    </option>
                                    <option value="3">
                                        ChatGPT 4
                                    </option>
                                    <option value="4">
                                        ChatGPT 3.5 Turbo-16k
                                    </option>
                                    <option value="5" selected="">
                                        Updated GPT 3.5 Turbo
                                    </option>
                                    <option value="6">
                                        ChatGPT 4 Gpt-4-32k
                                    </option>
                                    <option value="7">
                                        GPT-4 Turbo
                                    </option>
                                    <option value="8">
                                        GPT-4o
                                    </option>
                                    <option value="9">
                                        Gpt 4o mini
                                    </option>
                                    <option value="10">
                                        Gpt 4o mini 2024 07 18
                                    </option>
                                    <option value="11">
                                        Chatgpt 4o latest
                                    </option>
                                    <option value="12">
                                        Claude 3 Opus
                                    </option>
                                    <option value="13">
                                        Claude 3.5 Sonnet
                                    </option>
                                    <option value="14">
                                        DeepSeek V3
                                    </option>
                                </select>
                            </div>

                            <div class="ms-3 d-flex align-items-center justify-content-end">
                                <div class="form-check tt-checkbox" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="If this is checkd, it will be shown in the subscription plan list">
                                    <input class="form-check-input cursor-pointer tt_editable" type="checkbox" id="show_open_ai_model-1" data-name="show_open_ai_model-1" checked="" />
                                </div>
                            </div>
                        </li>

                        <li class="d-flex flex-column align-items-start">
                            <div class="w-100 d-flex align-items-center">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="feather feather-check-circle icon-14 me-2 text-success"
                                >
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <input class="form-control py-1 other_features" type="text" placeholder="Type additional features" value="" />
                            </div>
                            <small class="text-muted ps-4">* Comma separated: Feature A,Feature B</small>
                        </li>

                        <li class="d-flex flex-column align-items-start">
                            <div class="w-100 d-flex align-items-center">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="feather feather-check-circle icon-14 me-2 text-success"
                                >
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <input class="form-control py-1 duration" type="text" onkeypress="nonNumericFilter()" placeholder="30" value="30" />
                            </div>
                            <small class="text-muted ps-4">* Expire in number in days for Starter Package</small>
                        </li> -->
                    </ul>
                </div>
            </div>
            <div class="card-footer">
                <div>
                    <div class="d-flex justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="ms-1"><label for="is_active-1" class="cursor-pointer">Is Active?</label></span>
                            <div class="form-check form-switch ms-2">
                                <input type="checkbox" class="form-check-input cursor-pointer tt_editable" id="is_active-1" data-name="is_active-1" checked="" />
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">* If active, this will be applied to new user's registration. </small>
                </div>
            </div>
        </div>
    </div>



@endforeach



<div class="col-12 col-lg-4 min-h-400 d-none">

    <div class="card h-100 tt-add-more-card justify-content-center">

        <div class="card-body text-center">

            <button type="button" class="btn btn-primary rounded-circle btn-icon" onclick="showNewModal(this)"><i

                    data-feather="plus"></i></button>

        </div>

    </div>

</div>

