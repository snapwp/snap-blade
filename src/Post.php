<?php

namespace Snap\Blade;

class Post
{
	public function hey()
	{
		return 'yo';
	}

	public function __get($var)
	{
		if (!isset($this->{$var})) {
			global $post;
			return $post->{$var};
		}
	}

	public function title()
	{
		return get_the_title();
	}
}