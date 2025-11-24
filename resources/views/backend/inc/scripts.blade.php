<!-- bundle -->
<script src="{{ staticAsset('backend/assets/js/vendors/jquery-3.7.0.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/bootstrap.bundle.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/swiper-bundle.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/toastr.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/simplebar.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/footable.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/select2.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/feather.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/summernote-lite.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/flatpickr.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/apexcharts.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/apex-scripts.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/highlight.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/highlight-numbers.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/vendors/magnific-popup.min.js') }}"></script>
<script src="{{ staticAsset('backend/assets/js/app.js') }}"></script>

<!-- localizations & others -->
<script>
    'use strict';

    var TT = TT || {};
    TT.localize = {
        no_data_found: '{{ localize('No data found') }}',
        selected_file: '{{ localize('Selected File') }}',
        selected_files: '{{ localize('Selected Files') }}',
        file_added: '{{ localize('File added') }}',
        files_added: '{{ localize('Files added') }}',
        no_file_chosen: '{{ localize('No file chosen') }}',
        pleaseWait: '{{ localize('Please wait') }}..',
        createContent: '{{ localize('Create Content') }}',
        generateCode: '{{ localize('Generate Code') }}',
        generateImage: '{{ localize('Generate Image') }}',
        moveToFolder: '{{ localize('Move To Folder') }}',
        moveProject: '{{ localize('Move Project') }}',
        saveChanges: '{{ localize('Save Changes') }}',
    };
    TT.baseUrl = '{{ \Request::root() }}';

    // on click delete confirmation -- outside footable
    function confirmDelete(thisLink) {
        var url = $(thisLink).data("href");
        $("#delete-modal").modal("show");
        $("#delete-link").attr("href", url);
    }

    // on click Hidden confirmation -- outside footable
    function confirmHidden(thisLink) {
        var url = $(thisLink).data("href");
        $("#hide-modal").modal("show");
        $("#hide-link").attr("href", url);
    }

    // on click all delete confirmation -- outside footable
    function confirmAllDelete(thisLink) {
        var url = $(thisLink).data("href");
        $("#all-delete-modal").modal("show");
        $("#all-delete-link").attr("href", url);
    }

    // feather icon refresh
    function initFeather() {
        feather.replace();
    }
    initFeather();
</script>

<!-- media-manager scripts -->
@include('backend.inc.media-manager.uppyScripts')

<script>
    "use strict"
    $(function() {

        // footable js
        function initFootable() {
            $("table.tt-footable").footable({
                on: {
                    "ready.ft.table": function(e, ft) {
                        initTooltip();
                        deleteConfirmation();
                        hideConfirmation();
                        approveConfirmation();
                        rejectConfirmation();
                        reSubmitConfirmation();
                    },
                },
            });
        }
        initFootable();

        // tooltip
        function initTooltip() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
        initTooltip();

        // delete confirmation
        function deleteConfirmation() {
            $(".confirm-delete").click(function(e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#delete-modal").modal("show");
                $("#delete-link").attr("href", url);
            });
        }


        // hide confirmation
        function hideConfirmation() {
            $(".confirm-hide").click(function(e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#hide-modal").modal("show");
                $("#hide-link").attr("href", url);
            });
        }

        // approve confirmation
        function approveConfirmation() {
            $(".confirm-approve").click(function(e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#approve-modal").modal("show");
                $("#approve-link").attr("href", url);
            });
        }
        // reject confirmation
        function rejectConfirmation() {
            $(".confirm-reject").click(function(e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#reject-modal").modal("show");
                $("#reject-link").attr("href", url);
            });
        }
        // re-submit confirmation
        function reSubmitConfirmation() {
            $(".confirm-re-submit").click(function(e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#re-submit-modal").modal("show");
                $("#re-submit-link").attr("href", url);
            });
        }

        // select2 js
        $(".select2").select2();
        $(".select2Max3").select2({

            maximumSelectionLength: 3
        });

        // modal select2
        function modalSelect2(parent = '.modalParentSelect2') {
            $('.modalSelect2').select2({

                dropdownParent: $(parent)
            });
        }
        modalSelect2();

        // flatpickr 
        $(".date-picker").each(function(el) {
            var $this = $(this);
            var options = {
                dateFormat: 'm/d/Y'
            };

            var date = $this.data("date");
            if (date) {
                options.defaultDate = date;
            }

            $this.flatpickr(options);
        });

        $(".date-range-picker").each(function(el) {
            var $this = $(this);
            var options = {
                mode: "range",
                showMonths: 2,
                dateFormat: 'm/d/Y'
            };

            var start = $this.data("startdate");
            var end = $this.data("enddate");

            if (start && end) {
                options.defaultDate = [start, end];
            }

            $this.flatpickr(options);
        });

        // summernote
        $(".editor").each(function(el) {
            var $this = $(this);
            var buttons = $this.data("buttons");
            var minHeight = $this.data("min-height");
            var generateContentMinHeight = $this.data("content-min-height");
            var placeholder = $this.attr("placeholder");
            var format = $this.data("format");

            buttons = !buttons ? [
                    ["font", ["bold", "underline", "italic", "clear"]],
                    ['fontname', ['fontname']],
                    ["para", ["ul", "ol", "paragraph"]],
                    ["style", ["style"]],
                    ['fontsize', ['fontsize']],
                    ["color", ["color"]],
                    ["insert", ["link", "picture", "video"]],
                    ["view", ["undo", "redo"]],

                ] :
                buttons;
            placeholder = !placeholder ? "" : placeholder;
            minHeight = !minHeight ? 150 : minHeight;
            minHeight = !generateContentMinHeight ? minHeight : window.innerHeight - 460;

            format = typeof format == "undefined" ? false : format;

            $this.summernote({
                toolbar: buttons,
                placeholder: placeholder,
                height: minHeight,
                codeviewFilter: false,
                codeviewIframeFilter: true,
                disableDragAndDrop: true,
                fontSizes: ['8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18',
                    '19', '20', '21', '22', '23', '24', '36', '48', '64'
                ],
                callbacks: {
                    onImageUpload: function(files) {
                        var url =
                        " {{ route('file-upload') }}"; //path is defined as data attribute for  textarea
                        sendFile(files[0], url, $(this));
                    }
                },

            });

            var nativeHtmlBuilderFunc = $this.summernote(
                "module",
                "videoDialog"
            ).createVideoNode;

            $this.summernote("module", "videoDialog").createVideoNode = function(url) {
                var wrap = $(
                    '<div class="embed-responsive embed-responsive-16by9"></div>'
                );
                var html = nativeHtmlBuilderFunc(url);
                html = $(html).addClass("embed-responsive-item");
                return wrap.append(html)[0];
            };
        });
        // summernot ffile upload
        function sendFile(file, url, editor) {
            var data = new FormData();
            data.append("file", file);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: data,
                type: "POST",
                url: url,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    if(response.status == true){
                        editor.summernote('insertImage', response.file);
                    }else if(response.status == false){
                        console.log(response.msg);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {

                }
            });
        }
        // add more
        $('[data-toggle="add-more"]').each(function() {
            var $this = $(this);
            var content = $this.data("content");
            var target = $this.data("target");

            $this.on("click", function(e) {
                e.preventDefault();
                $(target).append(content);
                $('.select2').select2();
            });
        });

        // remove parent
        $(document).on(
            "click",
            '[data-toggle="remove-parent"]',
            function() {
                var $this = $(this);
                var parent = $this.data("parent");
                $this.closest(parent).remove();
            }
        );

        // language flag select2
        $(".country-flag-select").select2({
            dir: '{{ @$localLang->is_rtl == 1 ? 'rtl' : 'ltr' }}',
            templateResult: countryCodeFlag,
            templateSelection: countryCodeFlag,
            escapeMarkup: function(m) {
                return m;
            },
        });

        function countryCodeFlag(state) {
            var flagName = $(state.element).data("flag");
            if (!flagName) return state.text;
            return (
                "<div class='d-flex align-items-center'><img class='flag me-2' src='" + flagName +
                "' height='14' />" + state.text + "</div>"
            );
        }
    })

    // image gallery
    $(document).ready(function() {
        magnifyPopup();
    });

    function magnifyPopup() {
        $(".tt-image-gallery-magnify").magnificPopup({
            delegate: "a",
            type: "image",
            tLoading: 'Loading image #%curr%...',
            mainClass: "mfp-img-mobile",
            gallery: {
                enabled: true,
                navigateByImgClick: true,
                preload: [0, 1] // Will preload 0 - before current, and 1 after the current image
            },

            image: {
                tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
                titleSrc: function(item) {
                    return item.el.attr("title") + "<small>" + item.el.attr("size") + "</small>";
                }
            }
        });
    }

    function handleEditGroup(id) {
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('chat.editPromptGroup') }}',
            data: {
                id: id
            },
            beforeSend: function() {},
            complete: function() {},
            success: function(data) {
                $('.group-form').empty();
                $('.group-form').html(data.formData);
                var myOffcanvas = document.getElementById('offcanvasForm')
                var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas)
                bsOffcanvas.show()
            },
            error: function(data) {
                notifyMe('error', '{{ localize('Something went wrong') }}');
            }
        });
    }

    //  blog wizard article outlines
    function deleteOutlineInput($this) {
        $($this).closest('.single-outline').remove();
    }

    function addNewOutlineInput($this) {
        let html = `<div class="single-outline d-flex align-items-center mb-2 gap-2">
                                <span>#</span>
                                <input class="form-control" type="text" name="outlines[]" required>
                                <button class="btn btn-secondary btn-icon" type="button" onclick="addNewOutlineInput(this)"><i data-feather="plus"></i></button> 
                                <button class="btn btn-icon btn-soft-danger" type="button" onclick="deleteOutlineInput(this)"><i data-feather="minus"></i></button>
                            </div>`
        $($this).closest('.single-outline').after(html);
        initFeather();
    }
</script>
