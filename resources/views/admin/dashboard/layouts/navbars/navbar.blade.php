@auth()
    @include('admin.dashboard.layouts.navbars.navs.auth')
@endauth
    
@guest()
    @include('admin.dashboard.layouts.navbars.navs.guest')
@endguest