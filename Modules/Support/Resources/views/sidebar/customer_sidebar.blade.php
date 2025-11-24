
@php
$supportRoutes = ['support.index', 'support.category.index', 'support.priority.index'];

@endphp

<li class="side-nav-item nav-item {{ areActiveRoutes($supportRoutes, 'tt-menu-item-active') }}">
    <a data-bs-toggle="collapse" href="#supprot-ticket"
        aria-expanded="{{ areActiveRoutes($supportRoutes, 'true') }}" aria-controls="supprot-ticket"
        class="side-nav-link tt-menu-toggle">
        <span class="tt-nav-link-icon"><i data-feather="life-buoy"></i></span>
        <span class="tt-nav-link-text">{{ localize('Support Ticket') }}</span>
    </a>
    <div class="collapse {{ areActiveRoutes($supportRoutes, 'show') }}" id="supprot-ticket">
        <ul class="side-nav-second-level">
        
           
            <li  class="{{ areActiveRoutes(['support.ticket.create'], 'tt-menu-item-active') }}">
                <a href="{{ route('support.ticket.create') }}">             
                    <span class="tt-nav-link-text">{{ localize('Create Ticket') }}</span>
                </a>
            </li>
               
            
        
                <li class="{{ areActiveRoutes(['support.ticket.index'], 'tt-menu-item-active') }}">
                    <a href="{{ route('support.ticket.index') }}">{{ localize('Tickets') }}</a>
                </li>
         
        </ul>
    </div>
</li>
