@extends('layout')

@section('main')
	
	@if(have_posts())
		@loop

			{{-- This is a shortcut to render the current partials.post-type.{get_post_type()} --}}
			@posttypepartial

		@endloop

		@paginate
	@else
		@partial('post-type.none')
	@endif

@endsection