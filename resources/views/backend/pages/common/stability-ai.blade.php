@php
    $column = isset($column) ? 'col-lg-3' : null;
@endphp
<div class="{{ $column }}">
    <div class="form-input mb-3">
        <div class="form-input">
            <label for="style" class="form-label">{{ localize('Image Style') }}
                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="{{ localize('Style of the image will be as selected') }}"><i data-feather="help-circle"
                        class="icon-14"></i></span>
            </label>
            <select class="form-select select2" id="style" name="style">
                <option value='none'>{{ localize('None') }}
                </option>
                <option value='3d-model'>
                    {{ localize('3D Model') }}
                </option>
                <option value='analog-film'>
                    {{ localize('Analog Film') }}
                </option>
                <option value='anime'>{{ localize('Anime') }}
                </option>
                <option value='cinematic'>
                    {{ localize('Cinematic') }}
                </option>
                <option value='comic-book'>
                    {{ localize('Comic Book') }}
                </option>
                <option value='digital-art'>
                    {{ localize('Digital Art') }}
                </option>
                <option value='enhance'>{{ localize('Enhance') }}
                </option>
                <option value='fantasy-art'>
                    {{ localize('Fantasy Art') }}
                </option>
                <option value='isometric'>
                    {{ localize('Isometric') }}
                </option>
                <option value='line-art'>
                    {{ localize('Line Art') }}
                </option>
                <option value='low-poly'>
                    {{ localize('Low Poly') }}
                </option>
                <option value='modeling-compound'>
                    {{ localize('Modeling Compound') }}</option>
                <option value='neon-punk'>
                    {{ localize('Neon Punk') }}
                </option>
                <option value='origami'>{{ localize('Origami') }}
                </option>
                <option value='photographic'>
                    {{ localize('Photographic') }}
                </option>
                <option value='pixel-art'>
                    {{ localize('Pixel Art') }}
                </option>
                <option value='tile-texture'>
                    {{ localize('Tile Texture') }}
                </option>
            </select>
        </div>
    </div>
</div>
<div class="{{ $column }}">
    <div class="form-input mb-3">
        <div class="form-input">
            <label for="diffusion_samples" class="form-label">{{ localize('Image Diffusion Samples') }}
            </label>
            <select class="form-select select2" id="diffusion_samples" name="diffusion_samples">
                <option value='none'>{{ localize('Auto') }}
                </option>
                <option value='DDIM'>{{ localize('DDIM') }}
                </option>
                <option value='DDPM'>{{ localize('DDPM') }}
                </option>
                <option value='K_DPMPP_2M'>
                    {{ localize('K_DPMPP_2M') }}
                </option>
                <option value='K_DPMPP_2S_ANCESTRAL'>
                    {{ localize('K_DPMPP_2S_ANCESTRAL') }}
                </option>
                <option value='K_DPM_2'>{{ localize('K_DPM_2') }}
                </option>
                <option value='K_DPM_2_ANCESTRAL'>
                    {{ localize('K_DPM_2_ANCESTRAL') }}</option>
                <option value='K_EULER'>{{ localize('K_EULER') }}
                </option>
                <option value='K_EULER_ANCESTRAL'>
                    {{ localize('K_EULER_ANCESTRAL') }}</option>
                <option value='K_HEUN'>{{ localize('K_HEUN') }}
                </option>
                <option value='K_LMS'>{{ localize('K_LMS') }}
                </option>
            </select>
        </div>
    </div>
</div>
<div class="{{ $column }}">
    <div class="form-input mb-3">
        <div class="form-input">
            <label for="preset" class="form-label">{{ localize('Clip Guidance Preset') }}
            </label>
            <select class="form-select select2" id="preset" name="preset">
                <option value='NONE' selected>
                    {{ localize('None') }}
                </option>
                <option value='FAST_BLUE'>
                    {{ localize('FAST_BLUE') }}
                </option>
                <option value='FAST_GREEN'>
                    {{ localize('FAST_GREEN') }}
                </option>
                <option value='SIMPLE'>{{ localize('SIMPLE') }}
                </option>
                <option value='SLOW'>{{ localize('SLOW') }}
                </option>
                <option value='SLOWER'>{{ localize('SLOWER') }}
                </option>
                <option value='SLOWEST'>{{ localize('SLOWEST') }}
                </option>
            </select>
        </div>
    </div>
</div>
<div class="{{ $column }}">
    <div class="form-input mb-3">
        <div class="form-input"
            @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"  data-bs-placement="top" data-bs-title="{{ localize('Disabled in demo') }}" @endif>
            <label for="resolution" class="form-label">{{ localize('Image Resolution') }}
                <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="{{ localize('Select image resolution size that you need') }}"><i
                        data-feather="help-circle" class="icon-14"></i></span>
            </label>
            <select class="form-select select2" id="resolution" name="resolution" required
                @if (env('DEMO_MODE') == 'On') disabled @endif>
                @if (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-v1-6')
                    <option value='512x512' selected>
                        {{ localize('Width') }}
                        512 x
                        {{ localize('Height') }} 512</option>
                    <!-- <option value='768x768'>
                    {{ localize('Width') }} 768 x
                    {{ localize('Height') }}
                    768</option> -->
                @elseif (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-512-v2-1')
                    <option value='768x512'>
                        {{ localize('Width') }} 768 x
                        {{ localize('Height') }}
                        512</option>
                    <option value='1024x512'>
                        {{ localize('Width') }} 1024
                        x
                        {{ localize('Height') }}
                        512</option>
                    <option value='512x512' selected>
                        {{ localize('Width') }}
                        512 x
                        {{ localize('Height') }} 512</option>
                    <option value='512x768'>
                        {{ localize('Width') }} 512 x
                        {{ localize('Height') }}
                        768</option>
                    <option value='512x1024'>
                        {{ localize('Width') }} 512 x
                        {{ localize('Height') }}
                        1024</option>
                @elseif (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-768-v2-1')
                    <option value='1344x768'>
                        {{ localize('Width') }} 1344
                        x
                        {{ localize('Height') }}
                        768</option>
                    <option value='1152x768'>
                        {{ localize('Width') }} 1152
                        x
                        {{ localize('Height') }}
                        768</option>
                    <option value='1024x768'>
                        {{ localize('Width') }} 1024
                        x
                        {{ localize('Height') }}
                        768</option>
                    <option value='768x768' selected>
                        {{ localize('Width') }}
                        768 x
                        {{ localize('Height') }} 768</option>
                    <option value='768x1024'>
                        {{ localize('Width') }} 768 x
                        {{ localize('Height') }}
                        1024</option>
                    <option value='768x1152'>
                        {{ localize('Width') }} 768 x
                        {{ localize('Height') }}
                        1152</option>
                    <option value='768x1344'>
                        {{ localize('Width') }} 768 x
                        {{ localize('Height') }}
                        1344</option>
                @elseif (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-xl-beta-v2-2-2')
                    <option value='896x512'>
                        {{ localize('Width') }} 896 x
                        {{ localize('Height') }}
                        512</option>
                    <option value='768x512'>
                        {{ localize('Width') }} 768 x
                        {{ localize('Height') }}
                        512</option>
                    <option value='512x512' selected>
                        {{ localize('Width') }}
                        512 x
                        {{ localize('Height') }} 512</option>
                    <option value='512x768'>
                        {{ localize('Width') }} 512 x
                        {{ localize('Height') }}
                        768</option>
                    <option value='512x896'>
                        {{ localize('Width') }} 512 x
                        {{ localize('Height') }}
                        896</option>
                @elseif (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-xl-1024-v1-0')
                    <option value='1344x768'>
                        {{ localize('Width') }} 1344
                        x
                        {{ localize('Height') }}
                        768</option>
                    <option value='1024x1024' selected>
                        {{ localize('Width') }} 1024 x
                        {{ localize('Height') }} 1024</option>
                    <option value='768x1344'>
                        {{ localize('Width') }} 768
                        x
                        {{ localize('Height') }}
                        1344</option>
                    <option value='640x1536'>
                        {{ localize('Width') }} 640
                        x
                        {{ localize('Height') }}
                        1536</option>
                @elseif (getSetting('image_stable_diffusion_engine') == 'stable-diffusion-xl-1024-v0-9')
                    <option value='1536x640'>
                        {{ localize('Width') }} 1536
                        x
                        {{ localize('Height') }}
                        640</option>
                    <option value='1344x768'>
                        {{ localize('Width') }} 1344
                        x
                        {{ localize('Height') }}
                        768</option>
                    <option value='1024x1024' selected>
                        {{ localize('Width') }} 1024 x
                        {{ localize('Height') }} 1024</option>
                    <option value='768x1344'>
                        {{ localize('Width') }} 768
                        x
                        {{ localize('Height') }}
                        1344</option>
                    <option value='640x1536'>
                        {{ localize('Width') }} 640
                        x
                        {{ localize('Height') }}
                        1536</option>
                @endif
            </select>
        </div>
    </div>
</div>
