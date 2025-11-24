@if (@$localLang->is_rtl == 1)
<link href="{{ staticAsset('backend/assets/css/main-rtl.css') }}" rel="stylesheet" type="text/css" />
@else
<link href="{{ staticAsset('backend/assets/css/main.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ staticAsset('backend/assets/css/defaultcustom.css') }}" rel="stylesheet" type="text/css" />

<link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&amp;family=Space+Grotesk:wght@500;700&amp;display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis%401.0.27/bundled/lenis.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ staticAsset('backend/assets/css/index.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ staticAsset('backend/assets/css/custom.css') }}" type="text/css">
@endif


