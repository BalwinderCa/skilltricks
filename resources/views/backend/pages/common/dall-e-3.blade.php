@php
    $column = isset($column) ? 'col-lg-3': null;
@endphp
<div class="{{$column}}">
    <div class="form-input mb-3">
        <label for="style" class="form-label">{{ localize('Image Style') }}
            <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-title="{{ localize('Style of the image will be as selected') }}"><i data-feather="help-circle"
                    class="icon-14"></i></span>
        </label>
        <select class="form-select" id="style" name="style">
            <option value="vivid">{{ localize('Vivid') }}</option>
            <option value="natural">{{ localize('Natural') }}
            </option>
        </select>
    </div>
</div>

<div class="{{$column}}">
    <div class="form-input mb-3">
        <label for="quality" class="form-label">{{ localize('Quality') }}
            <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-title="{{ localize('Quality of the image will be as selected') }}"><i data-feather="help-circle"
                    class="icon-14"></i></span>
        </label>
        <select class="form-select" id="quality" name="quality">
            <option value="standard">{{ localize('Standard ') }}</option>
            <option value="hd">{{ localize('HD') }}</option>
        </select>
    </div>
</div>
<div class="{{$column}}">
    <div class="form-input mb-3"
        @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                data-bs-title="{{ localize('Disabled in demo') }}" @endif>
        <label for="resolution" class="form-label">{{ localize('Resolution') }}
            <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-title="{{ localize('Select image resolution size that you need') }}"><i data-feather="help-circle"
                    class="icon-14"></i></span>
        </label>
        <select class="form-select" id="resolution" name="resolution" required
            @if (env('DEMO_MODE') == 'On') disabled @endif>
    
                <option value="1024x1024">{{ localize(' [1024x1024]') }}</option>                                                              
                <option value="1024x1792">{{ localize(' [1024x1792]') }} </option>
                <option value="1792x1024">{{ localize(' [1792x1024]') }}</option>
        </select>
    </div>
</div>
