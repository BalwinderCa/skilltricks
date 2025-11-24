 <div class="modal-header">
     <h1 class="modal-title fs-5" id="saveToFolderLabel">{{ localize('Move To Folder') }}</h1>
     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>

 <div class="modal-body">
     <form action="" method="POST" class="move-to-folder-form">
         @csrf
         <input type="hidden" name="project_id" value="{{ $project->id }}">
         <div class="form-input">
             <label for="folder_id" class="form-label">{{ localize('Folder') }}</label>
             <select class="form-select modalSelect2" id="folder_id" name="folder_id">
                 <option value="" @if ($project->folder_id == null) selected @endif>{{ localize('All Projects') }}
                 </option>
                 @foreach ($folders as $folder)
                     <option value="{{ $folder->id }}" @if ($project->folder_id == $folder->id) selected @endif>
                         {{ $folder->name }}</option>
                 @endforeach
             </select>
         </div>

         <div class="d-flex justify-content-center pb-3 mt-3">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ localize('Cancel') }}</button>
             <button type="submit"
                 class="btn btn-primary px-4 ms-2 move-project-btn">{{ localize('Move Project') }}</button>
         </div>
     </form>
 </div>
