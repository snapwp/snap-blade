<!doctype html>
<html {{ language_attributes() }} class="no-js">

	@partial('header')

	<body itemscope itemtype="http://schema.org/WebPage" {{ body_class() }}>
		
		@partial('navigation')

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

		@partial('footer')

	</body>
</html>