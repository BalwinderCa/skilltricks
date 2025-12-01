@extends('backend.layouts.master')

@section('title')
    {{ localize('Documents') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    <section class="tt-section pt-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tt-page-header">
                        <div class="d-lg-flex align-items-center justify-content-lg-between">
                            <div class="tt-page-title mb-3 mb-lg-0">
                                <h1 class="h4 mb-lg-1">{{ localize('Documents') }}</h1>
                                <ol class="breadcrumb breadcrumb-angle text-muted">
                                    <li class="breadcrumb-item"><a href="{{ route('writebot.dashboard') }}">{{ localize('Dashboard') }}</a></li>
                                    <li class="breadcrumb-item">{{ localize('Documents') }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom-0">
                            <div class="row justify-content-between align-items-center g-3">
                                <div class="col-auto flex-grow-1">
                                    <h5 class="mb-lg-0">{{ localize('Upload Documents') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Upload Form --}}
                            <form action="{{ route('documents.upload') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                                @csrf
                                <div class="file-drop-area file-upload text-center rounded-3 py-4">
                                    <input type="file" class="file-drop-input" name="document" id="document" accept=".pdf,.doc,.docx,.xlsx,.xls,.pptx,.ppt" required />
                                    <div class="file-drop-icon ci-cloud-upload">
                                        <i data-feather="file-text"></i>
                                    </div>
                                    <p class="text-dark fw-bold mb-2 mt-3">
                                        {{ localize('Drop your document here or') }}
                                        <a href="javascript:void(0);" class="text-primary" onclick="document.getElementById('document').click();">{{ localize('Browse') }}</a>
                                    </p>
                                    <p class="mb-0 file-name text-muted">
                                        <small>* {{ localize('Allowed file types: PDF, DOC, DOCX, XLSX, XLS, PPTX, PPT (Max 10MB)') }}</small>
                                    </p>
                                </div>
                                @if ($errors->has('document'))
                                    <span class="text-danger">{{ $errors->first('document') }}</span>
                                @endif
                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i data-feather="upload" class="me-2"></i>{{ localize('Upload Document') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom-0">
                            <div class="row justify-content-between align-items-center g-3">
                                <div class="col-auto flex-grow-1">
                                    <h5 class="mb-lg-0">{{ localize('My Documents') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Documents List --}}
                            @if($documents->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ localize('Name') }}</th>
                                                <th>{{ localize('Size') }}</th>
                                                <th>{{ localize('Uploaded') }}</th>
                                                <th class="text-end">{{ localize('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($documents as $document)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($document->file_type == 'pdf')
                                                                <i data-feather="file-text" class="me-2 text-danger"></i>
                                                            @elseif($document->file_type == 'doc')
                                                                <i data-feather="file-text" class="me-2 text-primary"></i>
                                                            @elseif($document->file_type == 'xlsx')
                                                                <i data-feather="file-text" class="me-2 text-success"></i>
                                                            @elseif($document->file_type == 'ppt')
                                                                <i data-feather="file-text" class="me-2 text-warning"></i>
                                                            @else
                                                                <i data-feather="file-text" class="me-2 text-secondary"></i>
                                                            @endif
                                                            <span>{{ $document->name }}</span>
                                                            <span class="badge bg-secondary ms-2">{{ strtoupper($document->file_type) }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($document->file_size)
                                                            {{ number_format($document->file_size / 1024, 2) }} KB
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $document->created_at->format('M d, Y') }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ asset($document->file_path) }}" target="_blank" class="btn btn-sm btn-secondary me-2" title="{{ localize('View Document') }}" download>
                                                            <i data-feather="eye" class="icon-14"></i>
                                                        </a>
                                                        @if($document->parsed_text)
                                                            <button type="button" class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#parsedTextModal{{ $document->id }}" title="{{ localize('View Parsed Text') }}">
                                                                <i data-feather="file-text" class="icon-14"></i>
                                                            </button>
                                                        @else
                                                            <a href="{{ route('documents.parse', $document->id) }}" class="btn btn-sm btn-warning me-2" title="{{ localize('Parse Document') }}">
                                                                <i data-feather="refresh-cw" class="icon-14"></i>
                                                            </a>
                                                        @endif
                                                        <a href="{{ route('documents.delete', $document->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('{{ localize('Are you sure you want to delete this document?') }}')" title="{{ localize('Delete') }}">
                                                            <i data-feather="trash-2" class="icon-14"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                
                                                {{-- Parsed Text Modal --}}
                                                @if($document->parsed_text)
                                                    <div class="modal fade" id="parsedTextModal{{ $document->id }}" tabindex="-1" aria-labelledby="parsedTextModalLabel{{ $document->id }}" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="parsedTextModalLabel{{ $document->id }}">{{ localize('Parsed Text') }} - {{ $document->name }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">{{ localize('Extracted Text') }}</label>
                                                                        <textarea class="form-control" rows="15" readonly style="font-family: monospace; font-size: 12px;">{{ $document->parsed_text }}</textarea>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <small class="text-muted">{{ localize('Character count') }}: {{ mb_strlen($document->parsed_text) }}</small>
                                                                        <button type="button" class="btn btn-sm btn-primary" onclick="copyParsedText({{ $document->id }})">
                                                                            <i data-feather="copy" class="icon-14 me-1"></i>{{ localize('Copy Text') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ localize('Close') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between px-4 pb-4">
                                    <span>{{ localize('Showing') }}
                                        {{ $documents->firstItem() ?? 0 }}-{{ $documents->lastItem() ?? 0 }}
                                        {{ localize('of') }}
                                        {{ $documents->total() }} {{ localize('results') }}</span>
                                    <nav>
                                        {{ $documents->appends(request()->input())->links() }}
                                    </nav>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i data-feather="file-text" class="icon-48 text-muted mb-3"></i>
                                    <p class="text-muted">{{ localize('No documents uploaded yet') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        function copyParsedText(documentId) {
            const textarea = document.querySelector('#parsedTextModal' + documentId + ' textarea');
            const text = textarea.value;
            
            // Use modern Clipboard API
            navigator.clipboard.writeText(text).then(function() {
                // Show feedback
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i data-feather="check" class="icon-14 me-1"></i>{{ localize('Copied!') }}';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-primary');
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                    // Re-initialize feather icons
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy text: ', err);
                // Fallback to old method
                textarea.select();
                document.execCommand('copy');
            });
        }
    </script>
@endsection

