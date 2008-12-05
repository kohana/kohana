<?php
/**
 * utf8::rtrim
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function _rtrim($str, $charlist = NULL)
{
	if ($charlist === NULL)
		return rtrim($str);

	if (utf8::is_ascii($charlist))
		return rtrim($str, $charlist);

	$charlist = preg_replace('#[-\[\]:\\\\^/]#', '\\\\$0', $charlist);

	return preg_replace('/['.$charlist.']++$/uD', '', $str);
}