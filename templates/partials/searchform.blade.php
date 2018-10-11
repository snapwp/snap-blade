<form role="search" method="get" class="search-form" action="{{ esc_url( home_url( '/' ) ) }}">
	<div class="input-group">
		<label for="{{ $searchform_id }}">
			<span class="sr-only">{{ _x( 'Search for:', 'label' ) }}</span>
		</label>
		
		<input type="text" id="{{ $searchform_id }}" class="form-control" placeholder="Search for..." name="s" aria-label="Search for..." required>
		
		<div class="input-group-append">
			<button class="btn btn-secondary" type="submit">{{ esc_attr_x( 'Search', 'submit button' ) }}</button>
		</div>
	</div>
</form>