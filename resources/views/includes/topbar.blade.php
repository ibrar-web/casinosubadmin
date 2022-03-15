<div class="topbar">
    <div class="topbar1">
    </div>
    <div class="topbar2" style="cursor: pointer;">
        GRABESTER
    </div>
    <div class="topbar3">
     
        <a title="Logout" style=" margin-right:30px" href="{{ route('logout') }}"
            style="font-size:15px; color:black; float:right; margin-right: 60px"
            onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none; margin-right:10px">
            @csrf
        </form>
           @auth
           <div style=" margin-right:50px">Login : {{ Auth::user()->name }}</div>
        @else

        @endauth
    </div>
</div>
