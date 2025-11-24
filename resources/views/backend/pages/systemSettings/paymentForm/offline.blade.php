 <!-- Offcanvas -->
 <div class="offcanvas offcanvas-end" id="offcanvasOffline" tabindex="-1">
     <div class="offcanvas-header border-bottom">
         <h5 class="offcanvas-title">{{ localize('Offline Configuration') }}</h5>
         <span
             class="btn btn-outline-danger rounded-circle btn-icon d-inline-flex align-items-center justify-content-center"
             data-bs-dismiss="offcanvas">
             <i data-feather="x"></i>
         </span>
     </div>
     <div class="offcanvas-body" data-simplebar>
         <form action="{{ route('admin.settings.updatePaymentMethods') }}" method="POST" enctype="multipart/form-data">
             @csrf
             <!--Midtrans settings-->
             <input type="hidden" name="payment_methods[]" value="offline">

             <div class="mb-3">
                 <label class="form-label">{{ localize('Enable Offline') }}</label>
                 <select id="enable_offline" class="form-control select2" name="enable_offline" data-toggle="select2">
                     <option value="0" {{ getSetting('enable_offline') == '0' ? 'selected' : '' }}>
                         {{ localize('Disable') }}</option>
                     <option value="1" {{ getSetting('enable_offline') == '1' ? 'selected' : '' }}>
                         {{ localize('Enable') }}</option>
                 </select>
             </div>

             <div class="mb-3">
                 <label class="form-label">{{ localize('Image') }}</label>
                 <input type="hidden" name="types[]" value="offline_image">

                 <div class="tt-image-drop rounded">
                     <span class="fw-semibold">{{ localize('Choose Image') }}</span>
                     <!-- choose media -->
                     <div class="tt-product-thumb show-selected-files mt-3">
                         <div class="avatar avatar-xl cursor-pointer choose-media" data-bs-toggle="offcanvas"
                             data-bs-target="#offcanvasBottom" onclick="showMediaManager(this)" data-selection="single">
                             <input type="hidden" name="offline_image" value="{{ getSetting('offline_image') }}">
                             <div class="no-avatar rounded-circle">
                                 <span><i data-feather="plus"></i></span>
                             </div>
                         </div>
                     </div>
                     <!-- choose media -->
                 </div>

             </div>
             <!--midtrans settings-->
             <div class="mb-3">
                 <button class="btn btn-primary" type="submit">
                     <i data-feather="save" class="me-1"></i> {{ localize('Save Configuration') }}
                 </button>
             </div>
         </form>
     </div>
 </div>
