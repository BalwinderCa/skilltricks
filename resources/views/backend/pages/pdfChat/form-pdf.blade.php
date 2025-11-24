<div class="mt-auto text-center border-top">


    <form class="p-3 d-block w-100" id="pdfChatForm" enctype="multipart/form-data">
        <textarea class="form-control border-0"
                  rows="2"
                  name="prompt"
                  id="prompt"
                  placeholder="{{ localize('Write a content based on pdf ') }}.."></textarea>
        <div class="tt-product-thumb mt-2 text-start tt-vision-thumb" id="vision_image">

        </div>

        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap flex-lg-nowrap gap-2">
            <div class="d-flex align-items-center">
                <label for="pdfFile"
                       class="btn pdfFile rounded-pill btn-secondary btn-sm py-2 align-items-center d-flex">


                    <i data-feather="paperclip" class="icon-14 me-1"></i>
                    <span class="d-none d-lg-block lh-base">{{ localize('Select PDF File') }}</span>
                    <input type="file"
                           name="pdfFile"
                           id="pdfFile"
                           accept=".pdf"
                           class="d-none"
                    />
                </label>

            </div>
            <div class="d-flex align-items-center">
                <button class="btn btn-primary btn-sm rounded-pill tt-send-btn msg-send-btn" type="submit">
                    {{ localize('Send') }}<i data-feather="send" class="ms-1"></i>
                </button>

                <button class="btn rounded-pill btn-secondary btn-sm py-2 btn-stop-content ms-3" disabled>
                    <span>{{ localize('Stop') }} <i data-feather="stop-circle" class="ms-1"></i></span>
                </button>
            </div>

        </div>
    </form>
</div>
