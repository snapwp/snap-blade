@extends('layout')

@section('main')
	
	@if(have_posts())
		@loop
			@include('post')
		@endloop

		@paginate
	@else
		no posts bro
	@endif

@endsection