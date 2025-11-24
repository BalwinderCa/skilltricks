@php
$supportRoutes = ['support.index', 'support.category.index',  'support.ticket.index', 'support.priority.index'];

@endphp
@canany(['support_ticket', 'support_priority', 'support_ticket'])
<li class="side-nav-item nav-item {{ areActiveRoutes($supportRoutes, 'tt-menu-item-active') }}">
    <a data-bs-toggle="collapse" href="#affiliate"
        aria-expanded="{{ areActiveRoutes($supportRoutes, 'true') }}" aria-controls="affiliate"
        class="side-nav-link tt-menu-toggle">
        <span class="tt-nav-link-icon"><i data-feather="life-buoy"></i></span>
        <span class="tt-nav-link-text">{{ localize('Support Ticket') }}</span>
    </a>
    <div class="collapse {{ areActiveRoutes($supportRoutes, 'show') }}" id="affiliate">
        <ul class="side-nav-second-level">
            @can('support_category')
            <li class="{{ areActiveRoutes(['support.category.index'], 'tt-menu-item-active') }}">
                <a href="{{ route('support.category.index') }}">{{ localize('Category') }}</a>
            </li>
            @endcan
                    
            @can('support_priority')
            <li class="{{ areActiveRoutes(['support.priority.index'], 'tt-menu-item-active') }}">
                <a href="{{ route('support.priority.index') }}">{{ localize('Priority') }}</a>
            </li>
            @endcan           
            
            @can('support_ticket')
                <li class="{{ areActiveRoutes(['support.ticket.index'], 'tt-menu-item-active') }}">
                    <a href="{{ route('support.ticket.index') }}">{{ localize('Tickets') }}</a>
                </li>
            @endcan
        </ul>
    </div>
</li>
@endcan

