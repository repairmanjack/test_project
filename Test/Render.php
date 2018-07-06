<?php 

namespace Test;

class Render {
	public static function render($file, $vars) {
		extract($vars);
		ob_start();
		ob_implicit_flush(false);
		require $file;
		return ob_get_clean();
	}
}