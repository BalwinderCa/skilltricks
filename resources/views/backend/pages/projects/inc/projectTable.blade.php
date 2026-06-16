<table class="table tt-footable border-top align-middle" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center">{{ localize('S/L') }}</th>
            <th>{{ localize('Project Name') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Created Date') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Type') }}</th>
            <th data-breakpoints="xs sm md">{{ localize('Words/Size') }}</th>
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
                        href="{{ route('projects.edit', ['slug' => $project->slug, 'lang_key' => config('custom.default_language')]) }}" @endif
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

                <td>
                    <span class="fs-sm fw-bold">
                        @if ($project->content_type == 'image')
                            {{ $project->resolution }}
                        @else
                            {{ $project->words }}
                        @endif
                    </span>
                </td>

                <td class="text-end">
                    <div class="dropdown tt-tb-dropdown">
                        <button type="button" class="btn p-0" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="">
                            @if ($project->content_type == 'image')
                                <a href="javascript(void);" class="dropdown-item" data-bs-toggle="modal"
                                    data-bs-target="#publishedProject{{ $project->id }}">
                                    <i data-feather="send" class="me-2"></i>{{ $project->is_published == 1 ?  localize('UnPublish to Homepage'):localize('Publish to Homepage') }}
                                </a>
                            @endif
                            <a class="dropdown-item" @if ($newTab || $project->content_type == 'image') target="_blank" @endif
                                @if ($project->content_type == 'image') href="{{ staticAsset($project->content) }}"
                        @else 
                        href="{{ route('projects.edit', ['slug' => $project->slug, 'lang_key' => config('custom.default_language')]) }}" @endif>
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
            @if (isAdmin())
                <div class="modal fade" id="publishedProject{{ $project->id }}" tabindex="-1"
                    aria-labelledby="publishedModal" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('projects.publish-to-homepage', $project->id) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        {{ localize('Confirmation') }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <h6 class="my-0 mb-2">{{ localize('Are you sure to published this Image?') }}</h6>
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="0" name="status" id="1"  {{ $project->is_published == 0 || is_null($project->is_published) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="1">
                                                {{ localize('UnPublished') }}
                                            </label>
                                          </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="1" name="status" id="2" {{ $project->is_published == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="2">
                                                {{ localize('Published') }} 
                                            </label>
                                          </div>
                                       
                                    </div> 

                                    <div class="justify-content-center pb-3">
                                        <button type="button" class="btn btn-secondary mt-2"
                                            data-bs-dismiss="modal">{{ localize('Cancel') }}</button>
                                        <button type="submit"
                                            class="btn btn-success mt-2">{{ localize('Proceed') }}</button>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </tbody>
</table>
