<footer class="bg-dark py-5 text-secondary">
	<div class="container">
		<div class="row">

			<div class="col-md-4 mb-3 mb-md-0">
				<ul class="list-unstyled"> 
				    @simplemenu('primary' as $item)
				        <li>
				            <a href="{{ $item->url }}" class="{{ $item->is_active ? 'active' : '' }}">{{ $item->text }} </a>

				            @if( !empty($item->children) )
				                <ul>
				                    @foreach($item->children as $child)

				                        <li><a href="{{ $child->url }}" class="{{ $child->is_active ? 'active' : '' }}">{{ $child->text }} </a></li>
				                    @endforeach
				                </ul>
				            @endif
				        </li>
				    @endsimplemenu
				</ul>
			</div>

			<div class="col-md-4 mb-3 mb-md-0">
				Footer area 2
			</div>

			<div class="col-md-4 mb-3 mb-md-0">
				Footer area 3
			</div>

		</div>

		<div class="mt-5">
			&copy; {{ date('Y')}} {{ get_bloginfo('name') }}.
		</div>
	</div>
</footer>

{{-- Output the wp_footer content - scripts and the admin bar if shown. --}}
@wpfooter

@stack('footer_scripts')