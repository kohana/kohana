<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Provides simple benchmarking and profiling.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Profiler_Core {

	// Collected benchmarks
	protected static $_marks = array();

	/**
	 * Starts a new benchmark and returns a unique token.
	 *
	 * @param   string  benchmark name
	 * @param   string  group name
	 * @return  string
	 */
	public static function start($name, $group = 'general')
	{
		do
		{
			// Create a unique token and make sure it is not already in use
			$token = uniqid();
		}
		while (isset(Profiler::$_marks[$token]));

		Profiler::$_marks[$token] = array
		(
			'group' => $group,
			'name'  => $name,

			// Start the benchmark
			'start_time'   => microtime(TRUE),
			'start_memory' => memory_get_usage(),

			// Set NULL values
			'stop_time'    => FALSE,
			'stop_memory'  => FALSE,
		);

		return $token;
	}

	/**
	 * Stops a benchmark.
	 *
	 * @param   string  token
	 * @return  void
	 */
	public static function stop($token)
	{
		if ( ! isset(Profiler::$_marks[$token]))
		{
			throw new Kohana_Exception('Profiler token :token is not valid',
				array(':token', $token));
		}

		// Stop the benchmark
		Profiler::$_marks[$token]['stop_time']   = microtime(TRUE);
		Profiler::$_marks[$token]['stop_memory'] = memory_get_usage();
	}

	/**
	 * Deletes a benchmark.
	 * 
	 * @param   string  token
	 * @return  void
	 */
	public static function delete($token)
	{
		// Remove the benchmark
		unset(Profiler::$_marks[$token]);
	}

	/**
	 * Returns all the benchmark tokens by group and name as an array.
	 *
	 * @return  array
	 */
	public static function groups()
	{
		$groups = array();

		foreach (Profiler::$_marks as $token => $mark)
		{
			// Sort the tokens by the group and name
			$groups[$mark['group']][$mark['name']][] = $token;
		}

		return $groups;
	}

	/**
	 * Gets the min, max, average and total of a set of tokens as an array.
	 *
	 * @param   array  profiler tokens
	 * @return  array  min, max, average, total
	 */
	public static function stats(array $tokens)
	{
		// Min and max are unknown by default
		$min = $max = array(
			'time'   => NULL,
			'memory' => NULL,
			);

		// Total values are always integers
		$total = array(
			'time' => 0,
			'memory' => 0);

		foreach ($tokens as $token)
		{
			// Get the total time and memory for this benchmark
			list($time, $memory) = Profiler::total($token);

			if ($max['time'] === NULL OR $time > $max['time'])
			{
				// Set the maximum time
				$max['time'] = $time;
			}

			if ($min['time'] === NULL OR $time < $min['time'])
			{
				// Set the minimum time
				$min['time'] = $time;
			}

			// Incrase the total time
			$total['time'] += $time;

			if ($max['memory'] === NULL OR $memory > $max['memory'])
			{
				// Set the maximum memory
				$max['memory'] = $memory;
			}

			if ($min['memory'] === NULL OR $memory < $min['memory'])
			{
				// Set the minimum memory
				$min['memory'] = $memory;
			}

			// Incrase the total memory
			$total['memory'] += $memory;
		}

		// Determine the number of tokens
		$count = count($tokens);

		// Determine the averages
		$average = array(
			'time' => $total['time'] / $count,
			'memory' => $total['memory'] / $count);

		return array(
			'min' => $min,
			'max' => $max,
			'total' => $total,
			'average' => $average);
	}

	/**
	 * Gets the total execution time and memory usage of a benchmark as a list.
	 *
	 * @param   string  token
	 * @return  array   execution time, memory
	 */
	public static function total($token)
	{
		// Import the benchmark data
		$mark = Profiler::$_marks[$token];

		if ($mark['stop_time'] === FALSE)
		{
			// The benchmark has not been stopped yet,
			// get the current time and memory
			$mark['stop_time']   = microtime(TRUE);
			$mark['stop_memory'] = memory_get_usage();
		}

		return array
		(
			// Total time in seconds
			$mark['stop_time'] - $mark['start_time'],

			// Amount of memory in bytes
			$mark['stop_memory'] - $mark['start_memory'],
		);
	}

} // End Profiler
