<div class="row">
    <div class="col-12">
        <div class="card flex-column h-100">
            <div class="card h-100 flex-column">
                <div class="card-body d-flex flex-column h-100">
                    <span class="text-muted"></span>
                    <h4 class="fw-bold">{{ localize('Scan Report') }}</h4>
                    <div id="donutChat"></div>
                    @isset($content_detector)                        
                        <li>{{localize('Homen Writing')}} : <strong>{{$content_detector->human}}</strong> </li>
                        <li>{{localize('AI Writing')}} :  <strong>{{$content_detector->ai}}</strong></li>
                    @endisset
                </div>
            </div>
        </div>
    </div>
</div>
