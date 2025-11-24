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
            <option value="">{{ localize('None') }}</option>
            <option value="Abstract">{{ localize('Abstract') }}
            </option>
            <option value="Realstic">{{ localize('Realstic') }}
            </option>
            <option value="Cartoon">{{ localize('Cartoon') }}</option>
            <option value="Digital Art">{{ localize('Digital Art') }}
            </option>
            <option value="Illustration">{{ localize('Illustration') }}
            </option>
            <option value="Photography">{{ localize('Photography') }}
            </option>
            <option value="3D Render">{{ localize('3D Render') }}
            </option>
            <option value="Pencil Drawing">
                {{ localize('Pencil Drawing') }}</option>
        </select>
    </div>
</div>

<div class="{{$column}}">
    <div class="form-input mb-3">
        <label for="mood" class="form-label">{{ localize('Mood') }}
            <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                data-bs-title="{{ localize('Mood of the image will be as selected') }}"><i data-feather="help-circle"
                    class="icon-14"></i></span>
        </label>
        <select class="form-select" id="mood" name="mood">
            <option value="">{{ localize('None') }}</option>
            <option value="Angry">{{ localize('Angry') }}
            </option>
            <option value="Agressive">{{ localize('Agressive') }}
            </option>
    
            <option value="Calm">{{ localize('Calm') }}
            </option>
            <option value="Cheerful">{{ localize('Cheerful') }}
            </option>
            <option value="Chilling">{{ localize('Chilling') }}
            </option>
            <option value="Dark">{{ localize('Dark') }}
            </option>
            <option value="Happy">{{ localize('Happy') }}
            </option>
            <option value="Sad">{{ localize('Sad') }}
            </option>
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
            <option value="512x512" selected>{{ localize('Medium [512x512]') }}
            </option>
            <option value="1024x1024" >
                {{ localize('Large [1024x1024]') }}</option>
        </select>
    </div>
</div>
