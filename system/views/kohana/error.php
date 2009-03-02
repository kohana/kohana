<?php

// JS toggling of the error details
$toggle_details =

	// Get the error details div for this error
	'var d=document.getElementById(\'kohana_error_'.$error_id.'_details\');'.

	// Toggle the display value of the div
	'd.style.display=(d.style.display==\'none\'?\'\':\'none\');'.

	// Do not activate the link
	'return false;'

?>
<div id="kohana_error_<?php echo $error_id ?>" class="kohana_error" style="display:block;position:relative;z-index:1000;background:#cff292;font-size:1em;font-family:sans-serif;text-align:left">
	<div class="message" style="display:block;margin:0;padding:1em;color:#111">
		<p class="text" style="display:block;margin:0;padding:0;color:#111"><a href="#toggle_details" class="toggle_details" style="padding:0color:#39530c;text-decoration:underline;background:transparent" onclick="javascript:<?php echo $toggle_details ?>"><?php echo $type, ' [ ', $code, ' ]' ?></a>: <code style="display:inline;margin:0;padding:0;font-size:1em;background:transparent:color:#111;font-family:sans-serif"><?php echo $message ?></code> <span class="file"><?php echo Kohana::debug_path($file), ' [ ', $line, ' ]' ?></span></p>
	</div>
	<div id="kohana_error_<?php echo $error_id ?>_details" class="details" style="display:none;margin:0;padding:0 1em 1em;color:#111">
		<pre class="source" style="display:block;margin:0;padding:1em;font-family:monospace;background:#efefef;color:#111"><?php echo $source ?></pre>
		<dl class="trace" style="display:block;margin:0;padding:1em 0 0;font-family:monospace;color:#111"><?php echo implode("\n\n", Kohana::trace($trace)) ?></pre>
	</div>
</div>