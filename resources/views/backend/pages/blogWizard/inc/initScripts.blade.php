<script>
    'use strict';
    var initWizardFormData = {
        activeStep: 1,
        aiBlogWizardId: '',
        keywords: {
            for: 'keywords',
            data: {
                topic: '',
                keywords: '',
                generatedKeywords: []
            }
        },
        titles: {
            for: 'titles',
            data: {
                topic: '',
                keywords: '',
                title: '',
                generatedTitles: []
            }
        },
        images: {
            for: 'images',
            data: {
                title: '',
                generatedImages: []
            }
        },
        outlines: {
            for: 'outlines',
            data: {
                title: '',
                keywords: '',
                generatedOutlines: []
            }
        },
        article: {
            for: 'article',
            data: {
                title: '',
                image: '',
                outlines: [],
                keywords: '',
                langIndex: 0,
                generatedArticle: null
            }
        }
    };


    // is enable image
    var is_enable_image = "{{getSetting('generate_image') == '1' ? true : false}}";
    // this should run on load
    var localFormData = localStorage.getItem("wizardFormData") ? JSON.parse(localStorage.getItem("wizardFormData")) :
        undefined;

    var wizardFormData = localFormData != undefined ? localFormData : initWizardFormData;

    function resetBlogData() {
        wizardFormData = initWizardFormData;
        setLocalWizardData();
        window.location.reload();
    }

    // handleblog wizard id
    function handleBlogWizardId() {
        $('.ai_blog_wizard_id').each(function() {
            $(this).val(wizardFormData.aiBlogWizardId)
        });
    }

    // on selection change show next button
    function handleSelectionClick() {
        $('.selection-clicked').each(function() {
            var $this = this;
            $($this).on('change', function() {
                let activeStep = wizardFormData.activeStep;
                if (activeStep == 2) {
                    wizardFormData.titles.data.title = $($this).data('value');
                    wizardFormData.images.data.title = $($this).data('value');
                } else if (activeStep == 3) {
                    if(is_enable_image == false){
                        wizardFormData.article.data.outlines = $($this).data('value');
                    }else{
                        wizardFormData.article.data.image = $($this).data('value');
                    }

                } else if (activeStep == 4) {
                    wizardFormData.article.data.outlines = $($this).data('value');
                }
                $('.data-next-btn').removeClass('d-none');
            })
        });
    }


    // setLocalWizardData
    function setLocalWizardData() {
        localStorage.setItem('wizardFormData', JSON.stringify(wizardFormData));
    }

    // fillInputFileds
    function fillInputFields() {
        $('#topicStepKeyword').val(wizardFormData.keywords.data.topic);
        $('#keywordsStep').val(wizardFormData.keywords.data.keywords);

        $('#topicStepTitle').val(wizardFormData.keywords.data.topic);
        $('#keywordsStepTitle').val(wizardFormData.keywords.data.keywords);

        $('#imageStepTitle').val(wizardFormData.images.data.title);

        $('#outlineStepTitle').val(wizardFormData.outlines.data.title);
        $('#outlineStepKeywords').val(wizardFormData.outlines.data.keywords);

        $('#articleStepTitle').val(wizardFormData.article.data.title);
        $('#articleImage').val(wizardFormData.article.data.image);
        let html = '';
        if (wizardFormData.article.data.outlines.length == 0) {
            html += `<div class="single-outline d-flex align-items-center mb-2 gap-2">
                                <span>#</span>
                                <input class="form-control" type="text" name="outlines[]" required>
                                <button class="btn btn-secondary btn-icon" type="button"  onclick="addNewOutlineInput(this)"><i data-feather="plus"></i></button>
                            </div>`
        } else {
            wizardFormData.article.data.outlines.forEach((element, index) => {
                html += `<div class="single-outline d-flex align-items-center mb-2 gap-2">
                                <span>#</span>
                                <input class="form-control" type="text" name="outlines[]" value="${element}" required>
                                <button class="btn btn-secondary btn-icon" type="button" onclick="addNewOutlineInput(this)"><i data-feather="plus"></i></button>
                                ${index != 0 ? '<button class="btn btn-icon btn-soft-danger" type="button"  onclick="deleteOutlineInput(this)"><i data-feather="minus"></i></button>': '' }
                            </div>`;
            });
        }
        $('.outlines').empty().html(html);

        initFeather();
        $('#articleStepKeywords').val(wizardFormData.article.data.keywords);
    }

    // data population
    function populateKeywordsData() {
        let data = {
            generatedKeywords: wizardFormData.keywords.data.generatedKeywords
        };
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('blogWizard.populateKeywordsData') }}',
            data: data,
            beforeSend: function() {
                $('.keywords-data').empty();
            },
            success: function(data) {
                $('.keywords-data').html(data.html);
                handleKeywordsOnChange();
            },
            error: function(data) {}
        });
    }

    // populateTitlesData
    function populateTitlesData() {
        let data = {
            generatedTitles: wizardFormData.titles.data.generatedTitles
        };
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('blogWizard.populateTitlesData') }}',
            data: data,
            beforeSend: function() {
                $('.titles-data').empty();
            },
            success: function(data) {
                $('.titles-data').html(data.html);
                handleSelectionClick();
            },
            error: function(data) {}
        });
    }

    // populateImagesData
    function populateImagesData() {
        let data = {
            generatedImages: wizardFormData.images.data.generatedImages
        };
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('blogWizard.populateImagesData') }}',
            data: data,
            beforeSend: function() {
                $('.images-data').empty();
            },
            success: function(data) {
                $('.images-data').html(data.html);
                handleSelectionClick();
            },
            error: function(data) {}
        });
    }

    // populateOutlinesData
    function populateOutlinesData() {
        let data = {
            generatedOutlines: wizardFormData.outlines.data.generatedOutlines
        };
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('blogWizard.populateOutlinesData') }}',
            data: data,
            beforeSend: function() {
                $('.outlines-data').empty();
            },
            success: function(data) {
                $('.outlines-data').html(data.html);
                handleSelectionClick();
            },
            error: function(data) {}
        });
    }

    // populateArticleData
    function populateArticleData(articleId = null) {
        let data = {
            generatedArticle: articleId == null ? wizardFormData.article.data.generatedArticle : articleId
        };
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            method: 'POST',
            url: '{{ route('blogWizard.populateArticleData') }}',
            data: data,
            beforeSend: function() {
                $('.article-data').empty();
            },
            success: function(data) {
                console.log("Streaming Data comes from : ", data);

                $('.article-data').html(data.html);
                if (data.id !== null) {
                    let html = data.contents.replace(/\*\*(.*?)\*\*/g,
                        '<h3 class="mb-0 mt-3 h5">$1</h3>');

                    html = html.replace(/(?:\r\n|\r|\n)/g, '<br>');
                    $('.article-content').empty().html(html);

                    $('.edit-blog-btn').removeClass(
                        'd-none');
                }
            },
            error: function(data) {}
        });
    }

    // handleKeywordsOnChange onchange
    function handleKeywordsOnChange() {
        $('.keyword-checkbox').on('change', function() {
            let keywords = '';
            $('.keyword-checkbox').each(function(index) {
                let length = $('.keyword-checkbox').length;
                let checked = $(this).prop('checked');
                if (checked) {
                    keywords = keywords + $(this).data('key');
                    keywords = keywords + ', ';
                }
            });
            keywords = keywords.slice(0, -2); // removing last comma and space --> -2
            wizardFormData.keywords.data.keywords = keywords;
            fillInputFields();
            setLocalWizardData();
        })
    }
</script>
