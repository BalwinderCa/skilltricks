<table class="table tt-footable align-middle" data-use-parent-width="true">
    <thead>
        <tr>
            <th class="text-center">{{ localize('S/L') }}</th>
            <th width="40%">{{ localize('Title') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Language') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Date') }}</th>
            <th data-breakpoints="xs sm">{{ localize('Play') }}</th>
            <th data-breakpoints="xs sm" class="text-end">{{ localize('Action') }}
            </th>
        </tr>
    </thead>
    <tbody>
        @if(!empty($voiceLists))
            @foreach ($voiceLists as $key => $voice)
                <tr>
                    <td class="text-center">
                        {{ $key + 1 + ($voiceLists->currentPage() - 1) * $voiceLists->perPage() }}</td>
                    <td width="40%">{{ $voice->title }}</td>
                    <td>{{ $voice->language }}</td>
                    <td>{{ date('Y-m-d', strtotime($voice->created_at)) }}</td>
                    <td>
                        @if ($voice->storage_type == 'aws')
                            <audio controls class="h-35px">
                                <source src="{{ $voice->file_path }}" type="audio/ogg">
                            </audio>
                        @else
                            <audio controls class="h-35px">
                                <source src="{{ staticAsset('voice/audio/' . $voice->speech) }}" type="audio/ogg">
                            </audio>
                        @endif
                    </td>


                    <td class="text-end">
                        <div class="dropdown tt-tb-dropdown">
                            <button type="button" class="btn p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                <i data-feather="more-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow">
                                @if (file_exists(public_path('voice/audio/' . $voice->speech)))
                                    <a href="{{ staticAsset('voice/audio/' . $voice->speech) }}" class="dropdown-item"
                                        title="{{ localize('Download') }}" download="{{ $voice->title }}">
                                        <i data-feather="download" class="me-2"></i>
                                        {{ localize('Download') }}
                                    </a>
                                @elseif($voice->storage_type == 'aws')
                                    <a href="{{ $voice->file_path }}" class="dropdown-item"
                                        title="{{ localize('Download') }}" download="{{ $voice->title }}">
                                        <i data-feather="download" class="me-2"></i>
                                        {{ localize('Download') }}
                                    </a>
                                @endif
                                <a href="#" class="dropdown-item confirm-delete"
                                    data-href="{{ route('t2s.generate.delete', $voice->id) }}"
                                    title="{{ localize('Delete') }}">
                                    <i data-feather="trash-2" class="me-2"></i>
                                    {{ localize('Delete') }}
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
<!--pagination start-->
<div class="d-flex align-items-center justify-content-between px-4 pb-4">
    <span>{{ localize('Showing') }}
        {{ $voiceLists->firstItem() }}-{{ $voiceLists->lastItem() }} {{ localize('of') }}
        {{ $voiceLists->total() }} {{ localize('results') }}</span>
    <nav>
        {{ $voiceLists->appends(request()->input())->links() }}
    </nav>
</div>
<!--pagination end-->
