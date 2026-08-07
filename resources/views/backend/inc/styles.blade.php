<link href="{{ staticAsset('backend/assets/css/highlight.css') }}" rel="stylesheet" type="text/css" />
@if (@$localLang->is_rtl == 1)
{{-- staticAsset() versions on APP_VERSION, which only moves on a release, so theme
     edits stay behind the CDN's year-long cache until then. &m= is the file's own
     mtime. APP_VERSION is not usable for this: it doubles as the product version
     reported to the license and update checks. --}}
<link href="{{ staticAsset('backend/assets/css/main-rtl.css') }}&m={{ filemtime(public_path('backend/assets/css/main-rtl.css')) }}" rel="stylesheet" type="text/css" />
@else
<link href="{{ staticAsset('backend/assets/css/main.css') }}&m={{ filemtime(public_path('backend/assets/css/main.css')) }}" rel="stylesheet" type="text/css" />
<link href="{{ staticAsset('backend/assets/css/backendcustom.css') }}&m={{ filemtime(public_path('backend/assets/css/backendcustom.css')) }}" rel="stylesheet" type="text/css" />

@endif

