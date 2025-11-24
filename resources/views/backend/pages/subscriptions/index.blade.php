@extends('backend.layouts.master')


@section('title')
    {{ localize('Subscription Packages') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('contents')
    <section class="tt-section py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Subscription Packages') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a
                                            href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item">{{ localize('Subscriptions') }}</li>
                                </ol>
                            </div>
                            <div class="tt-action">

                                @php
                                    $yearlyCounter = \App\Models\Subscriptionpackage::where('package_type', 'yearly')->count();
                                    $lifetimeCounter = \App\Models\Subscriptionpackage::where('package_type', 'lifetime')->count();
                                    $prepaidCounter = \App\Models\Subscriptionpackage::where('package_type', 'prepaid')->count();
                                @endphp
                                <ul class="list-unstyled list-inline tt-package-switch-list mb-0 z-2 position-relative">

                                    <li class="list-inline-item tt-active">
                                        <input type="radio" name="tt-package-radio" id="tt-monthly" value="monthly"
                                            checked />
                                        <label for="tt-monthly">{{ localize('Monthly') }}</label>
                                    </li>
                                    <li class="list-inline-item {{ $yearlyCounter > 0 ? 'tt-active' : 'tt-inactive' }}">
                                        <input type="radio" name="tt-package-radio" id="tt-yearly" value="yearly" />
                                        <label for="tt-yearly">{{ localize('Yearly') }}</label>
                                    </li>
                                    <li class="list-inline-item {{ $lifetimeCounter > 0 ? 'tt-active' : 'tt-inactive' }}">
                                        <input type="radio" name="tt-package-radio" id="tt-lifetime" value="lifetime" />
                                        <label for="tt-lifetime">{{ localize('Lifetime') }}</label>
                                    </li>
                                    <li class="list-inline-item {{ $prepaidCounter > 0 ? 'tt-active' : 'tt-inactive' }}">
                                        <input type="radio" name="tt-package-radio" id="tt-prepaid" value="prepaid" />
                                        <label for="tt-prepaid">{{ localize('Prepaid') }}</label>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tt-template-heading mb-4 alert bg-soft-primary alert-primary">
                <!-- handle on change  -->
                <p class="mb-0">{{ localize('Selected package type') }}: <span
                        class="active-package-type">{{ localize('Monthly') }}</span></p>
            </div>

            <!-- packages list -->
            <div class="row g-3 justify-content-center subscription-package-wrapper">
                @include('backend.pages.subscriptions.inc.packages-list', ['packages' => $packages])
            </div>

            <div class="text-center please-wait mt-5 d-none">{{ localize('Please wait') }}...</div>

            <!-- templates offcanvas -->
            @include('backend.pages.subscriptions.inc.templates')
        </div>
    </section>
@endsection

@section('modals')
    <!-- Vertically centered scrollable modal -->
    <div class="modal fade modalParentSelect2" id="newOrCopy" tabindex="-1" aria-labelledby="newOrCopyLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content new-or-copy-package">
                {{-- data append via ajax response --}}
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        "use strict";

        // initial value of tt_editable - on click
        TT.selectedValue = null;

        // on ready
        $(window).ready(function() {});

        // get packages on change
        $('[name="tt-package-radio"]').on('change', function() {
            $('[name="tt-package-radio"]').prop('disabled', true);
            $('.active-package-type').empty();

            var value = $('[name="tt-package-radio"]:checked').val();
            $('.active-package-type').html(value.charAt(0).toUpperCase() + value.slice(1));

            getPackages();
        })

        // getPackages
        function getPackages() {
            $('.subscription-package-wrapper').empty();
            $('.please-wait').removeClass('d-none');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'GET',
                url: '{{ route('subscriptions.indexTypePackages') }}',
                data: {
                    type: $('[name="tt-package-radio"]:checked').val()
                },
                success: function(data) {
                    $('.please-wait').addClass('d-none');
                    $('.subscription-package-wrapper').html(data);
                    $('[name="tt-package-radio"]').prop('disabled', false);
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    initFeather();
                    initEditUpdate();
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                    $('[name="tt-package-radio"]').prop('disabled', false)
                }
            });
        }

        // getPackageTemplates
        function getPackageTemplates($this) {
            $('.package-template-contents').empty();
            var packageId = $($this).closest(".package-card").prev().val();
            var data = {
                package_id: packageId,
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'GET',
                url: '{{ route('subscriptions.getTemplates') }}',
                data: data,
                success: function(data) {
                    $('.template-please-wait').addClass('d-none');
                    $('.package-template-contents').html(data);
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });
        }

        // update templates
        $('.subscription-templates-form').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('subscriptions.updateTemplates') }}',
                data: form.serialize(),
                beforeSend: function() {
                    $('.package-template-submit-btn').prop('disabled', true);
                },
                complete: function() {
                    $('.package-template-submit-btn').prop('disabled', false);
                },
                success: function(response) {
                    getPackages();
                    let closeCanvas = $('.offcanvasRightClose');
                    closeCanvas.click();
                    notifyMe('success', '{{ localize('Templates updated successfully') }}');
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });
        })

        // edit - update
        function initEditUpdate() {
            $(".tt_editable").each(function() {
                var $this = this;

                $($this).on("click", async function() {
                    var name = $this.dataset.name;
                    var packageId = $($this).closest(".package-card").prev().val();

                    var changeStatusOnClickFor = [
                        "allow_dall_e_2_image",
                        "allow_dall_e_3_image",
                        "allow_ai_code",
                        "allow_speech_to_text",
                        "allow_ai_chat",
                        "allow_ai_rewriter",
                        "allow_ai_vision",
                        "allow_ai_image_chat",
                        "allow_ai_pdf_chat",
                        "allow_text_to_speech",
                        "allow_custom_templates",
                        "allow_blog_wizard",
                        "allow_eleven_labs",
                        'allow_word_tools',
                        'allow_built_in_templates',
                        'allow_image_tools',
                        'allow_sd_images',
                        "show_open_ai_model",
                        'show_word_tools',
                        'show_built_in_templates',
                        'show_custom_templates',
                        'show_blog_wizard',
                        'show_eleven_labs',
                        'show_ai_chat',
                        'show_ai_rewriter',
                        'show_ai_vision',
                        'show_ai_detector',
                        'show_ai_plagiarism',
                        'show_ai_image_chat',
                        'show_ai_pdf_chat',
                        'show_ai_code',
                        'show_text_to_speech',
                        'show_image_tools',
                        'show_dall_e_2_image',
                        'show_dall_e_3_image',
                        'show_sd_images',
                        'show_speech_to_text_tools',
                        'show_live_support',
                        'show_free_support',
                        'show_real_time_data',
                        "has_live_support",
                        "has_free_support",
                        "is_featured",
                        "is_active",
                        'allow_discount',
                        'allow_ai_detector',
                        'allow_ai_plagiarism',
                        'allow_real_time_data',
                    ]

                    let findFlag = changeStatusOnClickFor.find(item => {
                        return name.includes(item);
                    })

                    if (findFlag === undefined) {
                        TT.selectedValue = $(".tt_update_text[data-name='" + name + "']")[0].innerHTML;
                        $(".tt_update_text[data-name='" + name + "']").attr("contenteditable", "true")
                            .focus();
                    } else {
                        // change status
                        var data = {
                            package_id: packageId,
                            name: name,
                            value: null
                        }
                        await updatePackage(data);
                    }
                });
            });

            $(".tt_update_text").each(function() {
                var $this = this;
                $($this).on("focusout", async function() {
                    var name = $this.dataset.name;

                    var packageId = $($this).closest(".package-card").prev().val();
                    var language_key = $('#language_key').val();
                    var value = $this.innerHTML;

                    var data = {
                        name,
                        package_id: packageId,
                        language_key: language_key,
                        value
                    }
                    var response = await updatePackage(data);
                    if (!response.success) {
                        $this.innerHTML = TT.selectedValue;
                    }
                    if (response.success) {
                        setAmount(packageId, name, value);
                    }
                });
            });

            // additional features
            $('.other_features').each(function() {
                var $this = this;
                $($this).on('focusout', async function() {
                    var value = $($this).val();
                    var packageId = $($this).closest(".package-card").prev().val();
                    var data = {
                        name: "other_features",
                        package_id: packageId,
                        value
                    }
                    await updatePackage(data);
                })
            })

            //  validate day for started package features
            $('.duration').each(function() {
                var $this = this;
                $($this).on('focusout', async function() {
                    var value = $($this).val();
                    var packageId = $($this).closest(".package-card").prev().val();
                    var data = {
                        name: "duration",
                        package_id: packageId,
                        value
                    }
                    await updatePackage(data);
                })
            })


        }
        initEditUpdate();

        // non numeric filter
        function nonNumericFilter(evt) {
            evt = evt || window.event;
            var charCode = evt.which || evt.keyCode;
            var charStr = String.fromCharCode(charCode);
            if (isNaN(charStr) && charCode != 46 || charCode === 32 || charCode === 13 || (charCode === 46 && evt
                    .currentTarget.innerText.includes('.'))) {
                evt.preventDefault()
            };
        }

        // on model change
        async function handleModelChange($this) {
            var openAiModelId = $($this).val();

            var packageId = $($this).closest(".package-card").prev().val();
            var data = {
                name: "openai_model_id",
                package_id: packageId,
                value: openAiModelId
            }
            await updatePackage(data);
        }

        // update package
        async function updatePackage(data) {
            let result = $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('subscriptions.update') }}',
                data: data,
                success: function(response) {
                    if (response.demo) {
                        notifyMe('warning', response.message);
                    }
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });

            return result;
        }

        // toggle Group all
        function toggleGroupAll($this) {
            $($this).parent().parent().parent().parent().find("input:checkbox").prop('checked', $this
                .checked);
        }

        // show new modal
        function showNewModal($this) {
            $($this).prop('disabled', true);
            $('.new-or-copy-package').empty();

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'GET',
                url: '{{ route('subscriptions.copyPackage') }}',
                data: {},
                beforeSend: function() {},
                complete: function() {
                    $($this).prop('disabled', false);
                    $('.select2').select2({
                        dropdownParent: $('.modalParentSelect2')
                    });
                    initCopyPkgForm();
                },
                success: function(response) {
                    $('.new-or-copy-package').html(response);
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });

            $('#newOrCopy').modal('show');
        }

        // copy-package-form
        function initCopyPkgForm() {
            $('.copy-package-form').each(function() {
                var $this = this;
                $($this).on('submit', function(e) {
                    e.preventDefault();
                    var data = $($this).serialize();
                    data += `&type=${$('[name="tt-package-radio"]:checked').val()}`;
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        method: 'POST',
                        url: '{{ route('subscriptions.newPackage') }}',
                        data: data,
                        beforeSend: function() {
                            $('.pkg-submit-btn').prop('disabled', true);
                        },
                        complete: function() {
                            $('.pkg-submit-btn').prop('disabled', false);
                        },
                        success: function(response) {
                            getPackages();
                            if (response.demo) {
                                notifyMe('warning', response.message);
                            } else {
                                notifyMe('success',
                                    '{{ localize('Package created successfully') }}');
                            }

                            $('#newOrCopy').modal('hide');
                        },
                        error: function(data) {
                            notifyMe('error', '{{ localize('Something went wrong') }}');
                        }
                    });
                })
            })
        }

        // discount status on/off
        $(document).on('change', '.allow_discount', function() {
            let package_id = $(this).data('id');

            if ($(this).is(':checked')) {
                var status = true;
                discountActivities(package_id);
            } else {
                var status = false;
            }

            discoutShowHide(package_id, status);
        })

        // on Discount Type change
        async function handleDiscountTypeChange($this) {
            var discount_type = $($this).val();

            var packageId = $($this).closest(".package-card").prev().val();
            var data = {
                name: "discount_type",
                package_id: packageId,
                value: discount_type
            }
            // await updatePackage(data);
            await discountActivities(packageId, discount_type);
        }
        // discount amount
        $('.discount_amount').each(function() {
            var $this = this;
            $($this).on('focusout', async function() {
                var value = $($this).val();
                var packageId = $($this).closest(".package-card").prev().val();
                var discount_type = $('.discount_type_' + packageId).val();
                var amount = $('.package-main-price-' + packageId).val();
                var discount_amount = discountAmount(discount_type, amount, value);

                $('.package-discount-price-' + packageId).removeClass('d-none').html('$' + amount);
                $('.package-price-' + packageId).html(discount_amount);
                var data = {
                    name: "discount_amount",
                    package_id: packageId,
                    discount_type: discount_type,
                    amount: amount,
                    value
                }
                await updatePackage(data);

            })
        })
        // discount function
        function discoutShowHide(package_id, status) {
            var amount = $('.package-main-price-' + package_id).val();
            if (status == true) {
                $('#discount_option_' + package_id).removeClass('d-none');
                $('.package-price-edit-' + package_id).addClass('d-none');

            } else {

                $('#discount_option_' + package_id).addClass('d-none');
                $('.package-price-edit-' + package_id).removeClass('d-none');
                $('.package-discount-price-' + package_id).addClass('d-none');
                $('.package-price-' + package_id).html(amount);
            }

        }

        async function discountActivities(package_id, discount_type = false, amount = false, discount = false) {
            if (discount == false) {
                var discount = $('.package-discount-' + package_id).val();
            }
            if (discount_type == false) {
                var discount_type = $('.discount_type_' + package_id).val();
            }
            if (amount == false) {
                var amount = $('.package-main-price-' + package_id).val();
            }

            if (discount_type && amount > 0 && discount > 0) {
                var discount_amount = discountAmount(discount_type, amount, discount);
                $('.package-discount-price-' + package_id).removeClass('d-none').html('$' + amount);
                $('.package-price-' + package_id).html(discount_amount);
                var data = {
                    name: "discount_amount",
                    package_id: package_id,
                    discount_type: discount_type,
                    amount: amount,
                    value: discount
                }

                await updatePackage(data);
            }


        }
        // discout amount calculate
        function discountAmount(discount_type, amount, discount) {
            var decimal = "{{ getSetting('no_of_decimals') }}";
            if (discount_type == 1) {
                var calcPrice = amount - discount;
                var discountPrice = calcPrice.toFixed();
            } else if (discount_type == 2) {
                var calcPrice = amount - ((amount / 100) * discount);
                var discountPrice = calcPrice.toFixed(decimal);
            } else {
                var discountPrice = amount;
            }
            if (discountPrice < 0) {
                var discountPrice = 0;
            }
            return discountPrice;
        }
        // amount set
        function setAmount(package_id, name, value) {
            if (name == 'package-price-' + package_id) {
                $('.package-main-price-' + package_id).val(value);
            }
        }

        // 

        // unlimited balance
        $(document).on('change', '.unlimited_balance', function() {

            // allow_unlimited_speech_to_text,allow_unlimited_image, allow_unlimited_word
            let data_name = $(this).data('name');
            let id = data_name.split('-')[1];
            let name = data_name.split('-', 1)[0];
            let str = name.replaceAll('_', " ");
          
            var status = false;
            if ($(this).is(':checked')) {
                var status = true;
            }
            var data = {
                name: name,
                package_id: id,
                value: status
            }
            // words
            if (str.includes("word")) {
                if (status == true) {
                    let value = $('#allow_word_text_' + id).text();
                    localStorage.setItem("package-words_" + id, value);
                    $('#allow_word_edit_' + id).addClass('d-none');
                    $('#allow_word_text_' + id).html('Unlimited');
                } else {
                    $('#allow_word_edit_' + id).removeClass('d-none');
                    $('#allow_word_text_' + id).html(localStorage.getItem("package-words_" + id));
                }
                updatePackageInfo(data);
            }
            //image
            if (str.includes("image")) {
                if (status == true) {
                    let value = $('#allow_image_text_' + id).text();
                    localStorage.setItem("package-image_" + id, value);
                    $('#allow_image_edit_' + id).addClass('d-none');
                    $('#allow_image_text_' + id).html('Unlimited');
                } else {
                    $('#allow_image_edit_' + id).removeClass('d-none');
                    $('#allow_image_text_' + id).html(localStorage.getItem("package-image_" + id));
                }
                updatePackageInfo(data);
            }
            //speech to text
            if (str.includes("speech to text")) {
                if (status == true) {
                    let value = $('#allow_speech_to_text_text_' + id).text();
                    localStorage.setItem("package-speech_to_text_" + id, value);
                    $('#allow_speech_to_text_edit_' + id).addClass('d-none');
                    $('#allow_speech_to_text_text_' + id).html('Unlimited');
                } else {
                    $('#allow_speech_to_text_edit_' + id).removeClass('d-none');
                    $('#allow_speech_to_text_text_' + id).html(localStorage.getItem("package-speech_to_text_" +
                        id));
                }
                updatePackageInfo(data);
            }

        })

        function divHideShowForUnlimitedBalance(status, showDiv, textDiv) {
            if (status == true) {
                let value = $('#allow_word_text_' + id).text();
                localStorage.setItem("package-words_" + id, value);
                $('#allow_word_edit_' + id).addClass('d-none');
                $('#allow_word_text_' + id).html('Unlimited');
            } else {
                $('#allow_word_edit_' + id).removeClass('d-none');
                $('#allow_word_text_' + id).html(localStorage.getItem("package-words_" + id));
            }
        }

        function updatePackageInfo(data) {
            let result = $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                method: 'POST',
                url: '{{ route('subscriptions.update') }}',
                data: data,
                success: function(response) {
                    if (response.demo) {
                        notifyMe('warning', response.message);
                    }
                },
                error: function(data) {
                    notifyMe('error', '{{ localize('Something went wrong') }}');
                }
            });

            return result;
        }
    </script>
@endsection
