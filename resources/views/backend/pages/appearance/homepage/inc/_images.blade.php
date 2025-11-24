<table class="table tt-footable border-top align-middle" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center">{{ localize('S/L') }}</th>
            <th>{{ localize('Project Name') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Created Date') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Type') }}</th>
            <th data-breakpoints="xs sm md" class="text-end">{{ localize('Action') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($projects as $key => $project)
            <tr>
                <td class="text-center fs-sm">
                    {{ $key + 1 + ($projects->currentPage() - 1) * $projects->perPage() }}
                </td>

                <td>
                    <a @if ($project->content_type == 'image') @php
                            $image_path = $project->storage_type == 'aws' ? $project->content : staticAsset($project->content);
                        @endphp 
                        
                        href="{{ $image_path }}"
                        @else 
                        href="{{ route('projects.edit', ['slug' => $project->slug, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}" @endif
                        class="d-flex align-items-center" @if ($newTab || $project->content_type == 'image') target="_blank" @endif>
                        <div class="avatar avatar-sm">
                            <div class="text-center"><span data-feather="{{ getProjectIcon($project->content_type) }}"
                                    class="text-muted icon-16"></span>
                            </div>
                        </div>
                        <div class="ms-1">
                            <h6 class="fs-sm mb-0"> {{ $project->title }}</h6>
                        </div>
                    </a>
                </td>

                <td>
                    <span class="fs-sm">{{ date('d M, Y', strtotime($project->created_at)) }}</span>
                </td>

                <td>
                    <span
                        class="fs-sm text-capitalize">{{ localize(ucwords(str_replace('_', ' ', $project->content_type))) }}</span>
                </td>

              

                <td class="text-end">
                    <div class="dropdown tt-tb-dropdown">
                        <button type="button" class="btn p-0" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="">
                          
                            <a class="dropdown-item" @if ($newTab || $project->content_type == 'image') target="_blank" @endif
                                @if ($project->content_type == 'image') href="{{ staticAsset($project->content) }}"
                        @else 
                        href="{{ route('projects.edit', ['slug' => $project->slug, 'lang_key' => env('DEFAULT_LANGUAGE')]) }}" @endif>
                                <i data-feather="edit-3" class="me-2"></i>{{ localize('View Contents') }}
                            </a>

                            <a href="#" class="dropdown-item confirm-delete"
                                @if ($project->content_type == 'image') data-href="{{ route('images.delete', $project->id) }}" 
                                @else
                                data-href="{{ route('projects.delete', $project->id) }}" @endif
                                title="{{ localize('Delete') }}">
                                <i data-feather="trash-2" class="me-2"></i>
                                {{ localize('Delete') }}
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
           
        @endforeach
    </tbody>
</table>