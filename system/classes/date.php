<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Date helper.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class date_Core {

	// Second amounts for various time increments
	const YEAR   = 31556926;
	const MONTH  = 2629744;
	const WEEK   = 604800;
	const DAY    = 86400;
	const HOUR   = 3600;
	const MINUTE = 60;

	/**
	 * Returns the difference between two times in a "fuzzy" way.
	 *
	 * @param   integer  past UNIX timestamp
	 * @return  string
	 */
	public static function fuzzy_span($timestamp)
	{
		// Determine the difference in seconds
		$offset = time() - $timestamp;

		if ($offset <= date::MINUTE)
		{
			return 'moments ago';
		}
		elseif ($offset < (date::MINUTE * 20))
		{
			return 'a few minutes ago';
		}
		elseif ($offset < date::HOUR)
		{
			return 'less than an hour ago';
		}
		elseif ($offset < (date::HOUR * 4))
		{
			return 'a couple of hours ago';
		}
		elseif ($offset < date::DAY)
		{
			return 'less than a day ago';
		}
		elseif ($offset < (date::DAY * 2))
		{
			return 'about a day ago';
		}
		elseif ($offset < (date::DAY * 4))
		{
			return 'a couple of days ago';
		}
		elseif ($offset < date::WEEK)
		{
			return 'less than a week ago';
		}
		elseif ($offset < (date::WEEK * 2))
		{
			return 'about a week ago';
		}
		elseif ($offset < date::MONTH)
		{
			return 'less than a month ago';
		}
		elseif ($offset < (date::MONTH * 2))
		{
			return 'about a month ago';
		}
		elseif ($offset < (date::MONTH * 4))
		{
			return 'a couple of months ago';
		}
		elseif ($offset < date::YEAR)
		{
			return 'less than a year ago';
		}
		elseif ($offset < (date::YEAR * 2))
		{
			return 'about a year ago';
		}
		elseif ($offset < (date::YEAR * 9))
		{
			return 'a couple of years ago';
		}
		elseif ($offset < (date::YEAR * 14))
		{
			return 'about a decade ago';
		}
		elseif ($offset < (date::YEAR * 32))
		{
			return 'a couple of decades ago';
		}
		elseif ($offset < (date::YEAR * 64))
		{
			return 'several decades ago';
		}
		else
		{
			return 'a long time ago';
		}
	}

} // End date
