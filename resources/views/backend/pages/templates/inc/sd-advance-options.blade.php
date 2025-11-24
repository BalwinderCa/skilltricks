<div class="row justify-content-between align-items-center pb-3">
    <div class="col-auto flex-grow-1">
        <div class="tt-promt-fild">
            <div class="d-flex align-items-center tt-advance-options cursor-pointer">
                <label for="tt-advance-options"
                    class="form-label cursor-pointer mb-0 btn btn-outline-secondary btn-sm rounded-pill"><span
                        class="fw-bold tt-promot-number fw-bold me-1"><span class="me-1 cursor-pointer"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Choose style, mood, resolution, number of results') }}"><i
                                data-feather="help-circle"
                                class="icon-14"></i></span></span>{{ localize('Advance Options') }}
                    <span><i data-feather="plus" class="icon-16 text-primary ms-2"></i></span></label>
            </div>
        </div>
    </div>
    <div class="col-auto">
        @if (isCustomer())
            @php
                $user = auth()->user();
                $latestPackage = activePackageHistory(auth()->user()->id);
            @endphp
            @if ($latestPackage->new_image_balance != -1)
                <div class="d-flex align-items-center flex-column used-words-percentage">
                    @include('backend.pages.templates.inc.used-images-percentage')
                </div>
            @endif
        @endif
    </div>
</div>

<!-- advance options -->
<div class="card mb-3 tt-advance-options-wrapper" id="tt-advance-options">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-lg-3">
                <div class="form-input">
                    <label for="style" class="form-label">{{ localize('Image Style') }}
                        <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Style of the image will be as selected') }}"><i
                                data-feather="help-circle" class="icon-14"></i></span>
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

            <div class="col-lg-3">
                <div class="form-input">
                    <label for="mood" class="form-label">{{ localize('Mood') }}
                        <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Mood of the image will be as selected') }}"><i
                                data-feather="help-circle" class="icon-14"></i></span>
                    </label>
                    <select class="form-select select2" id="mood" name="mood">
                        <option value="">{{ localize('None') }}
                        </option>
                        <option value="Angry">{{ localize('Angry') }}
                        </option>
                        <option value="Agressive">
                            {{ localize('Agressive') }}
                        </option>

                        <option value="Calm">{{ localize('Calm') }}
                        </option>
                        <option value="Cheerful">
                            {{ localize('Cheerful') }}
                        </option>
                        <option value="Chilling">
                            {{ localize('Chilling') }}
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

            <div class="col-lg-3">
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

            <div class="col-lg-3">
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


            <div class="col-lg-3">
                <div class="form-input"
                    @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"
        data-bs-placement="top"
        data-bs-title="{{ localize('Disabled in demo') }}" @endif>
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

            <div class="col-lg-3">
                <div class="form-input"
                    @if (env('DEMO_MODE') == 'On') data-bs-toggle="tooltip"
            data-bs-placement="top"
            data-bs-title="{{ localize('Disabled in demo') }}" @endif>
                    <label for="num_of_results" class="form-label">{{ localize('Number of Results') }}
                        <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Select how many variations of result you want') }}"><i
                                data-feather="help-circle" class="icon-14"></i></span>
                    </label>
                    <select class="form-select select2" id="num_of_results" name="num_of_results" required
                        @if (env('DEMO_MODE') == 'On') disabled @endif>
                        <option value="1" @if (getSetting('default_number_of_results') == '1') selected @endif>
                            1
                        </option>
                        <option value="2" @if (getSetting('default_number_of_results') == '2') selected @endif>
                            2
                        </option>
                        <option value="3" @if (getSetting('default_number_of_results') == '3') selected @endif>
                            3
                        </option>
                        <option value="4" @if (getSetting('default_number_of_results') == '4') selected @endif>
                            4
                        </option>
                        <option value="5" @if (getSetting('default_number_of_results') == '5') selected @endif>
                            5
                        </option>
                    </select>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-input">
                    <label for="negative_prompts" class="form-label">{{ localize('Negative Prompts') }}
                        <span class="ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="{{ localize('Type negative prompts') }}"><i data-feather="help-circle"
                                class="icon-14"></i></span>
                    </label>
                    <input class="form-control" type="text" id="negative_prompts" name="negative_prompts"
                        placeholder="{{ localize('Type negative prompts') }}">
                </div>
            </div>
        </div>
    </div>
</div>
