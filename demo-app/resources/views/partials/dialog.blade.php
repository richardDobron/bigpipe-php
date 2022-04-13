<div class="modal-dialog @yield('size')">
    <div class="modal-content">
        @hasSection('title')
            <div class="modal-header">
                <h4 class="modal-title">@yield('title')</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
        @endif

        @hasSection('content')
            @yield('content')
        @else
            <div class="modal-body">
                @yield('body')
            </div>

            <div class="modal-footer">
                @yield('footer')
            </div>
        @endif
    </div>
</div>
