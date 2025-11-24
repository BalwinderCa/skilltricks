<div class="row">

    <div class="col-lg-12 mb-3">
        <div class="form-group">
            <label class="form-label">{{ localize('Purpose of the Training') }}</label>
            <input type="text"
                   class="form-control"
                   name="title"
                   placeholder="{{ localize('Ex. Coding Expert') }}"
                   value="{{ isset($fineTune) ? $fineTune->title : old('title') }}"
            />
            <?= errorName('title') ?>
        </div>
    </div>



    <div class="col-lg-6 mb-3">
        <div class="form-group">
            <label class="form-control jsonFile" for="jsonFile">
                <i data-feather="file"></i>
                {{ localize('Select a JSONL File') }}
                <input type="file"
                       accept=".jsonl"
                       class="form-control d-none"
                       id="jsonFile"
                       name="training_data_file"
                       placeholder="{{ localize('Ex. Coding Expert') }}"
                />
            </label>
            <?= errorName('training_data_file') ?>
        </div>
    </div>


    @php
        $modelEngine = modelEngines();
        $davinci002 = $modelEngine::DAVINCI_002;
        $gpt35Turbo = $modelEngine::GPT_3_5_TURBO;
 @endphp

    <div class="col-lg-6 mb-3">
        <div class="form-group">
            <label class="form-label">{{ localize('Select a JSONL File') }} </label>

            <select name="model_engine" class="form-control" id="">
                <option value="{{ $gpt35Turbo }}" @if(isset($fineTune) && $fineTune->model_engnine == $gpt35Turbo) selected @endif>{{ $gpt35Turbo }}</option>
            </select>
            <?= errorName('model_engine') ?>
        </div>
    </div>



    <div class="col-lg-12 mb-3">
        <div class="form-group">
            <label class="form-label">{{ localize('Describe your training Model') }}</label>
            <textarea
                   class="form-control"
                   rows="5"
                   cols="5"
                   name="description"
                   placeholder="{{ localize('Ex. Coding Expert') }}">{{ isset($fineTune) ? $fineTune->description : old('description') }}</textarea>
            <?= errorName('description') ?>
        </div>
    </div>


    <div class="col-lg-12 mb-3">
        <div class="form-group">

            <button type="submit" class="btn btn-success">
                <i data-feather="save"></i>
                {{ localize("Save Training Model") }}
            </button>
        </div>
    </div>
</div>
