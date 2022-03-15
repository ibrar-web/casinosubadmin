<header>
    <div class="topbar-left">
        <button class="menu-bar"><i class="fas fa-bars"></i></button>
        <p class="logo">
            <i class="fas fa-angle-left"></i><i class="fas fa-angle-left"></i>
            <span> River</span> <i class="fas fa-angle-right"></i><i class="fas fa-angle-right"></i>
        </p>
        <p>Reports</p>
    </div>
    <div class="topbar-right">
        You logged in as
        <div class="user">
            @auth
                <div style=" margin-right:50px"> {{ Auth::user()->name }}</div>
            @else

            @endauth
        </div>
        <i class="fas fa-sort-down"></i>
    </div>
</header>
