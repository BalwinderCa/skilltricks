 <!-- Offcanvas -->
 <div class="offcanvas offcanvas-end" id="offcanvas{{ $theme->slug }}" tabindex="-1">
     <div class="offcanvas-header border-bottom">
         <h5 class="offcanvas-title">{{ $theme->name }}</h5>
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
         


             <div class="mb-4">
                 <label for="name" class="form-label">{{ localize('Name') }} <x-required-star /></label>
                 <input class="form-control" type="text" id="name" name="name"
                     placeholder="{{ localize('Type folder name') }}"  value="{{ $theme->name }}" required>
                 <x-error :name="'name'" />
             </div>
             <div class="mb-3">
                 <label class="form-label">{{ localize('Is Default') }}</label>
                 <select id="is_default" class="form-control select2" name="is_default" data-toggle="select2">
                     <option value="0" {{ $theme->id_default == '0' ? 'selected' : '' }}>
                         {{ localize('Disable') }}</option>
                     <option value="1" {{ $theme->id_default == '1' ? 'selected' : '' }}>
                         {{ localize('Enable') }}</option>
                 </select>
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
