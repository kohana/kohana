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
	 * Returns the offset (in seconds) between two time zones.
	 *
	 * @see     http://php.net/timezones
	 * @param   string  timezone that to find the offset of
	 * @param   string  timezone used as the baseline
	 * @return  integer
	 */
	public static function offset($remote, $local = NULL)
	{
		if ($local === NULL)
		{
			// Use the default timezone
			$local = date_default_timezone_get();
		}

		// Set the cache key, matches the method name
		$cache_key = "date::offset({$remote},{$local})";

		if (($offset = Kohana::cache($cache_key)) === NULL)
		{
			// Create timezone objects
			$zone_remote = new DateTimeZone($remote);
			$zone_local  = new DateTimeZone($local);

			// Create date objects from timezones
			$time_remote = new DateTime('now', $zone_remote);
			$time_local  = new DateTime('now', $zone_local);

			// Find the offset
			$offset = $zone_remote->getOffset($time_remote) - $zone_local->getOffset($time_local);

			// Cache the offset
			Kohana::cache($cache_key, $offset);
		}

		return $offset;
	}

	/**
	 * Number of seconds in a minute, incrementing by a step.
	 *
	 * @param   integer  amount to increment each step by, 1 to 30
	 * @param   integer  start value
	 * @param   integer  end value
	 * @return  array    A mirrored (foo => foo) array from 1-60.
	 */
	public static function seconds($step = 1, $start = 0, $end = 60)
	{
		// Always integer
		$step = (int) $step;

		$seconds = array();

		for ($i = $start; $i < $end; $i += $step)
		{
			$seconds[$i] = ($i < 10) ? '0'.$i : $i;
		}

		return $seconds;
	}

	/**
	 * Number of minutes in an hour, incrementing by a step.
	 *
	 * @param   integer  amount to increment each step by, 1 to 30
	 * @return  array    A mirrored (foo => foo) array from 1-60.
	 */
	public static function minutes($step = 5)
	{
		// Because there are the same number of minutes as seconds in this set,
		// we choose to re-use seconds(), rather than creating an entirely new
		// function. Shhhh, it's cheating! ;) There are several more of these
		// in the following methods.
		return date::seconds($step);
	}

	/**
	 * Number of hours in a day.
	 *
	 * @param   integer  amount to increment each step by
	 * @param   boolean  use 24-hour time
	 * @param   integer  the hour to start at
	 * @return  array    A mirrored (foo => foo) array from start-12 or start-23.
	 */
	public static function hours($step = 1, $long = FALSE, $start = NULL)
	{
		// Default values
		$step = (int) $step;
		$long = (bool) $long;
		$hours = array();

		// Set the default start if none was specified.
		if ($start === NULL)
		{
			$start = ($long === FALSE) ? 1 : 0;
		}

		$hours = array();

		// 24-hour time has 24 hours, instead of 12
		$size = ($long === TRUE) ? 23 : 12;

		for ($i = $start; $i <= $size; $i += $step)
		{
			$hours[$i] = $i;
		}

		return $hours;
	}

	/**
	 * Returns AM or PM, based on a given hour.
	 *
	 * @param   integer  number of the hour
	 * @return  string
	 */
	public static function ampm($hour)
	{
		// Always integer
		$hour = (int) $hour;

		return ($hour > 11) ? 'PM' : 'AM';
	}

	/**
	 * Adjusts a non-24-hour number into a 24-hour number.
	 *
	 * @param   integer  hour to adjust
	 * @param   string   AM or PM
	 * @return  string
	 */
	public static function adjust($hour, $ampm)
	{
		$hour = (int) $hour;
		$ampm = strtolower($ampm);

		switch ($ampm)
		{
			case 'am':
				if ($hour == 12)
					$hour = 0;
			break;
			case 'pm':
				if ($hour < 12)
					$hour += 12;
			break;
		}

		return sprintf('%02s', $hour);
	}

	/**
	 * Number of days in month.
	 *
	 * @param   integer  number of month
	 * @param   integer  number of year to check month, defaults to the current year
	 * @return  array    A mirrored (foo => foo) array of the days.
	 */
	public static function days($month, $year = FALSE)
	{
		static $months;

		// Always integers
		$month = (int) $month;
		$year  = (int) $year;

		// Use the current year by default
		$year  = ($year == FALSE) ? date('Y') : $year;

		// We use caching for months, because time functions are used
		if (empty($months[$year][$month]))
		{
			$months[$year][$month] = array();

			// Use date to find the number of days in the given month
			$total = date('t', mktime(1, 0, 0, $month, 1, $year)) + 1;

			for ($i = 1; $i < $total; $i++)
			{
				$months[$year][$month][$i] = $i;
			}
		}

		return $months[$year][$month];
	}

	/**
	 * Number of months in a year
	 *
	 * @return  array  A mirrored (foo => foo) array from 1-12.
	 */
	public static function months()
	{
		return date::hours();
	}

	/**
	 * Returns an array of years between a starting and ending year.
	 * Uses the current year +/- 5 as the max/min.
	 *
	 * @param   integer  starting year
	 * @param   integer  ending year
	 * @return  array
	 */
	public static function years($start = FALSE, $end = FALSE)
	{
		// Default values
		$start = ($start === FALSE) ? date('Y') - 5 : (int) $start;
		$end   = ($end   === FALSE) ? date('Y') + 5 : (int) $end;

		$years = array();

		// Add one, so that "less than" works
		$end += 1;

		for ($i = $start; $i < $end; $i++)
		{
			$years[$i] = $i;
		}

		return $years;
	}

	/**
	 * Returns time difference between two timestamps, in human readable format.
	 *
	 * @param   integer       timestamp
	 * @param   integer       timestamp, defaults to the current time
	 * @param   string        formatting string
	 * @return  string|array
	 */
	public static function span($time1, $time2 = NULL, $output = 'years,months,weeks,days,hours,minutes,seconds')
	{
		// Array with the output formats
		$output = preg_split('/[^a-z]+/', strtolower((string) $output));

		// Invalid output
		if (empty($output))
			return FALSE;

		// Make the output values into keys
		extract(array_flip($output), EXTR_SKIP);

		// Default values
		$time1  = max(0, (int) $time1);
		$time2  = empty($time2) ? time() : max(0, (int) $time2);

		// Calculate timespan (seconds)
		$timespan = abs($time1 - $time2);

		if (isset($years))
		{
			$timespan -= date::YEAR * ($years = (int) floor($timespan / date::YEAR));
		}

		if (isset($months))
		{
			$timespan -= date::MONTH * ($months = (int) floor($timespan / date::MONTH));
		}

		if (isset($weeks))
		{
			$timespan -= date::WEEK * ($weeks = (int) floor($timespan / date::WEEK));
		}

		if (isset($days))
		{
			$timespan -= date::DAY * ($days = (int) floor($timespan / date::DAY));
		}

		if (isset($hours))
		{
			$timespan -= date::HOUR * ($hours = (int) floor($timespan / date::HOUR));
		}

		if (isset($minutes))
		{
			$timespan -= date::MINUTE * ($minutes = (int) floor($timespan / date::MINUTE));
		}

		// Seconds ago, 1
		if (isset($seconds))
		{
			$seconds = $timespan;
		}

		// Remove the variables that cannot be accessed
		unset($timespan, $time1, $time2);

		// Deny access to these variables
		$deny = array_flip(array('deny', 'key', 'difference', 'output'));

		// Return the difference
		$difference = array();
		foreach ($output as $key)
		{
			if (isset($$key) AND ! isset($deny[$key]))
			{
				// Add requested key to the output
				$difference[$key] = $$key;
			}
		}

		// Invalid output formats string
		if (empty($difference))
			return FALSE;

		// If only one output format was asked, don't put it in an array
		if (count($difference) === 1)
			return current($difference);

		// Return array
		return $difference;
	}

	/**
	 * Returns the difference between a time and now in a "fuzzy" way.
	 *
	 * @param   integer  past UNIX timestamp
	 * @return  string
	 */
	public static function fuzzy_span($timestamp)
	{
		// Determine the difference in seconds
		$offset = abs(time() - $timestamp);

		if ($offset <= date::MINUTE)
		{
			$span = 'moments';
		}
		elseif ($offset < (date::MINUTE * 20))
		{
			$span = 'a few minutes';
		}
		elseif ($offset < date::HOUR)
		{
			$span = 'less than an hour';
		}
		elseif ($offset < (date::HOUR * 4))
		{
			$span = 'a couple of hours';
		}
		elseif ($offset < date::DAY)
		{
			$span = 'less than a day';
		}
		elseif ($offset < (date::DAY * 2))
		{
			$span = 'about a day';
		}
		elseif ($offset < (date::DAY * 4))
		{
			$span = 'a couple of days';
		}
		elseif ($offset < date::WEEK)
		{
			$span = 'less than a week';
		}
		elseif ($offset < (date::WEEK * 2))
		{
			$span = 'about a week';
		}
		elseif ($offset < date::MONTH)
		{
			$span = 'less than a month';
		}
		elseif ($offset < (date::MONTH * 2))
		{
			$span = 'about a month';
		}
		elseif ($offset < (date::MONTH * 4))
		{
			$span = 'a couple of months';
		}
		elseif ($offset < date::YEAR)
		{
			$span = 'less than a year';
		}
		elseif ($offset < (date::YEAR * 2))
		{
			$span = 'about a year';
		}
		elseif ($offset < (date::YEAR * 4))
		{
			$span = 'a couple of years';
		}
		elseif ($offset < (date::YEAR * 8))
		{
			$span = 'a few years';
		}
		elseif ($offset < (date::YEAR * 12))
		{
			$span = 'about a decade';
		}
		elseif ($offset < (date::YEAR * 24))
		{
			$span = 'a couple of decades';
		}
		elseif ($offset < (date::YEAR * 64))
		{
			$span = 'several decades';
		}
		else
		{
			$span = 'a long time';
		}

		if ($timestamp <= time())
		{
			// This is in the past
			return $span.' ago';
		}
		else
		{
			// This in the future
			return 'in '.$span;
		}
	}

	/**
	 * Converts a UNIX timestamp to DOS format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  integer
	 */
	public static function unix2dos($timestamp = FALSE)
	{
		$timestamp = ($timestamp === FALSE) ? getdate() : getdate($timestamp);

		if ($timestamp['year'] < 1980)
		{
			return (1 << 21 | 1 << 16);
		}

		$timestamp['year'] -= 1980;

		// What voodoo is this? I have no idea... Geert can explain it though,
		// and that's good enough for me.
		return ($timestamp['year']    << 25 | $timestamp['mon']     << 21 |
		        $timestamp['mday']    << 16 | $timestamp['hours']   << 11 |
		        $timestamp['minutes'] << 5  | $timestamp['seconds'] >> 1);
	}

	/**
	 * Converts a DOS timestamp to UNIX format.
	 *
	 * @param   integer  DOS timestamp
	 * @return  integer
	 */
	public static function dos2unix($timestamp = FALSE)
	{
		$sec  = 2 * ($timestamp & 0x1f);
		$min  = ($timestamp >>  5) & 0x3f;
		$hrs  = ($timestamp >> 11) & 0x1f;
		$day  = ($timestamp >> 16) & 0x1f;
		$mon  = ($timestamp >> 21) & 0x0f;
		$year = ($timestamp >> 25) & 0x7f;

		return mktime($hrs, $min, $sec, $mon, $day, $year + 1980);
	}

} // End date
