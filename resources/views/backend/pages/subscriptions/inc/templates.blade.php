<!-- Offcanvas -->
<form class="offcanvas offcanvas-end subscription-templates-form" data-bs-backdrop="static" id="offcanvasRight"
    tabindex="-1">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex justify-content-between w-100 align-items-center">
            <div>
                <h5 class="offcanvas-title">{{ localize('All Templates') }}</h5>
            </div>
            <div>
                <span class="btn btn-soft-danger offcanvasRightClose" data-bs-dismiss="offcanvas">
                    {{ localize('Close') }}
                </span>
                <button class="btn btn-primary package-template-submit-btn"
                    type="submit">{{ localize('Save Changes') }}</button>
            </div>
        </div>
    </div>
    <div class="offcanvas-body" data-simplebar>
        <div class="text-center template-please-wait mt-5">{{ localize('Please wait') }}...</div>
        <div class="package-template-contents"></div>
    </div>
</form> <!-- offcanvas template end-->
