<?php defined('SYSPATH') or die('No direct script access.');

abstract class Database_Query_Builder extends Database_Query {

	/**
	 * Compiles an array of JOIN statements into an SQL partial.
	 *
	 * @param   object  Database instance
	 * @param   array   join statements
	 * @return  string
	 */
	public static function compile_join(Database $db, array $joins)
	{
		$statements = array();

		foreach ($joins as $join)
		{
			// Compile each of the join statements
			$statements[] = $join->compile($db);
		}

		return implode(' ', $statements);
	}

	/**
	 * Compiles an array of conditions into an SQL partial. Used for WHERE
	 * and HAVING.
	 *
	 * @param   object  Database instance
	 * @param   array   condition statements
	 * @return  string
	 */
	public static function compile_conditions(Database $db, array $conditions)
	{
		$last_condition = NULL;

		$sql = '';
		foreach ($conditions as $group)
		{
			// Process groups of conditions
			foreach ($group as $logic => $condition)
			{
				if ($condition === '(')
				{
					if ( ! empty($sql) AND $last_condition !== '(')
					{
						// Include logic operator
						$sql .= ' '.$logic.' ';
					}

					$sql .= '(';
				}
				elseif ($condition === ')')
				{
					$sql .= ')';
				}
				else
				{
					if ( ! empty($sql) AND $last_condition !== '(')
					{
						// Add the logic operator
						$sql .= ' '.$logic.' ';
					}

					// Split the condition
					list($column, $op, $value) = $condition;

					// Append the statement to the query
					$sql .= $db->quote_identifier($column).' '.strtoupper($op).' '.$db->quote($value);
				}

				$last_condition = $condition;
			}
		}

		return $sql;
	}

	/**
	 * Compiles an array of ORDER BY statements into an SQL partial.
	 * 
	 * @param   object  Database instance
	 * @param   array   sorting columns
	 * @return  string
	 */
	public static function compile_order_by(Database $db, array $columns)
	{
		$sort = array();
		foreach ($columns as $group)
		{
			list ($column, $direction) = $group;

			if ( ! empty($direction))
			{
				// Make the direction uppercase
				$direction = ' '.strtoupper($direction);
			}

			$sort[] = $db->quote_identifier($column).$direction;
		}

		return 'ORDER BY '.implode(', ', $sort);
	}

} // End Database_Query_Builder
