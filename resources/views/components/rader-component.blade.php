
@isset($featureCategories)
<div class="radar">
 <div class="animated-text-wrapper">
     <p class="cd-headline slide mb-0">
         <span class="cd-words-wrapper">
             @foreach ($featureCategories as $item)                          
                 <b class="{{ $loop->first ? 'is-visible' : '' }}">
                     {{ $item->collectLocalization('name') }}</b>
             @endforeach

         </span>
     </p>
 </div>
</div>
@endisset