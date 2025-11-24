<form action="" method="POST" class="content-form">
    @csrf
    <input class="project_id" type="hidden" name="project_id"
        @if (isset($project)) value="{{ $project->id }}" @else value="" @endif>

    <div class="row justify-content-between align-items-center g-2 p-3">
        <div class="col-auto flex-grow-1">
            <input class="form-control border-0 px-2 project-title" type="text" id="title"name="title"
                placeholder="{{ localize('Your project title') }}"
                @if (isset($project)) value="{{ $project->title }}" @else value="" @endif readonly disabled>
        </div>
        <div class="col-auto">
            <button type="button" class="tt-icon-btn tt-icon-info border-0 shadow-sm rounded-circle move_to_folder_btn"
                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{localize('Move to folder')}}"
                onclick="showSaveToFolderModal()"><i data-feather="folder"></i></button>
        </div>

        <div class="col-auto">
            <button type="button" class="tt-icon-btn tt-icon-warning border-0 shadow-sm rounded-circle copyBtn"
                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{localize('Copy Code')}}" data-type="code"><i
                    data-feather="copy"></i></button>
        </div>
    </div>
    <div class="card-body d-flex flex-column h-100 tt-create-content-wrap p-0">
        @if (isset($project))
            <div class="p-3 pt-2">
                <pre class="hljs p-4 my-3 rounded min-h-300"><code id="codetext">{{ $project->content }}</code></pre>
            </div>
        @else
            <div class="p-3 pt-2">
                <div class="codetext-start"></div>
                <pre class="hljs p-4 my-3 rounded min-h-300"><code id="codetext">{{ localize('Your code will appear here') }}</code></pre>
                <div class="codetext-end"></div>
            </div>
        @endif
    </div>
</form>
