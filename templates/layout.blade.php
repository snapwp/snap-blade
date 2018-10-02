<!doctype html>
<html {{ language_attributes() }} class="no-js">

	@include('partials.header')

	<body itemscope itemtype="http://schema.org/WebPage" {{ body_class() }}>
		
		@include('partials.nav')

		<div class="container">
			<div class="row">

				<main class="col-lg-8" role="main" itemscope itemprop="mainContentOfPage">
					@yield('main')
				</main>

				<aside class="col-lg-4" role="complementary" itemscope itemtype="http://schema.org/WPSideBar">
					@section('sidebar')
			            @sidebar('sidebar-blog')
			        @show
				</aside>

			</div>
		</div>

		@include('partials.footer')

	</body>
</html>