<?php defined('SYSPATH') or die('No direct script access.');

class DB_Core {

	public static function query($type, $sql)
	{
		return new Database_Query($type, $sql);
	}

	public static function select($sql)
	{
		return new Database_Query(Database::SELECT, $sql);
	}

	public static function insert($sql)
	{
		return new Database_Insert($sql);
	}

	public static function update($sql)
	{
		return new Database_Update($sql);
	}

	public static function delete($sql)
	{
		return new Database_Delete($sql);
	}

	public static function create($database)
	{
		return new Database_Create($database);
	}

	public static function alter($table, array $params)
	{
		return new Database_Alter($table, $params);
	}

	public static function drop($database)
	{
		return new Database_Drop($database);
	}

} // End DB