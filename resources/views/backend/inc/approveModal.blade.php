<div id="approve-modal" class="modal fade">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ localize('Approve Confirmation') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body text-center">
                <div class="display-4 text-success"> <i data-feather="check"></i></div>
                <h6 class="my-0">{{ localize('Are you sure to Approve this?') }}</h6>

                <div class="justify-content-center pb-3 mt-3">
                    <a href="" id="approve-link" class="btn btn-danger mt-2">{{ localize('Proceed') }}</a>
                    <button type="button" class="btn btn-secondary mt-2"
                        data-bs-dismiss="modal">{{ localize('Cancel') }}</button>
                </div>
            </div>

        </div>
    </div>
</div>
