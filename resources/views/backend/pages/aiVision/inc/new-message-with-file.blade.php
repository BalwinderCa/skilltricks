@php
      $userAvatar =  auth()->user()->avatar ? asset("/public/".auth()->user()->profileImage->media_file)  : staticAsset("/backend/assets/img/avatar/1.jpg");
@endphp
<div class="d-flex justify-content-end mb-4 tt-message-wrap tt-message-me">
    <div class="d-flex flex-column align-items-end">
        <div class="d-flex align-items-start">
            <div class="p-3 me-3 rounded-3 mw-450 tt-message-text tt-message-text-category-wise">
                {{ $prompt }} <br>
                @foreach ($mediaFiles as $mediaFile)
                    <div class="">
                        <img class="" alt="" src="{{ uploadedAsset($mediaFile->id) }}">

                    </div>
                @endforeach
            </div>
            <div class="avatar avatar-md flex-shrink-0">
                <img class="rounded-circle" src="{{$userAvatar}}" alt=" avatar" />
            </div>
        </div>
    </div>
</div>
