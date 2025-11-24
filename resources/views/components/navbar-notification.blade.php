
<li class="nav-item dropdown">
    <a class="nav-link position-relative tt-notification" href="#" role="button" id="notificationDropdown"
      data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-auto-close="outside">
      <i data-feather="bell"></i>
      <span class="tt-notification-dot tt-notification-number bg-danger rounded-circle"> {{$countMesage}}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end py-0 shadow border-0" aria-labelledby="notificationDropdown">
      <div class="card position-relative border-0">
        <div class="card-body p-0 border-bottom-0">
          <div class="scrollbar-overlay">
            @if ($notifications->count() > 0)
                @foreach ($notifications as $msg)                
                <div class="p-3 position-relative border-bottom">
                    <a href="{{route('admin.notifications.index', ['id'=>$msg->id, 'is_read'=> 1])}}">
                      <div class="d-flex">               
                          <div class="me-2 flex-1">
                          <h4 class="small mb-0">{{$msg->title}}</h4>
                          <span class="text-muted small">{{date('d-M-Y', strtotime($msg->created_at))}}</span>
                          </div>
                      </div>
                   </a>
                </div>
                @endforeach
            @else  
            <div class="p-3 position-relative border-bottom">
                <div class="d-flex">               
                    <div class="me-2 flex-1">
                    <h4 class="small">{{localize('No Notification found')}}</h4>
                    
                    </div>
                </div>
            </div>    
            @endif
           
          </div>
        </div>
        <div class="card-footer py-0 px-3  border-0 d-flex justify-content-between">
          <a class="fw-bolder my-2 text-center d-block small" href="{{route('admin.notifications.read-all')}}">{{localize('Read All')}}</a>
          <a class="fw-bolder my-2 text-center d-block small" href="{{route('admin.notifications.index')}}">{{localize('Go History')}}</a>
        </div>
      </div>
    </div>
  </li>