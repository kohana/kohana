<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * File helper class.
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class file_Core {

	/**
	 * Attempt to get the mime type from a file. This method is horribly
	 * unreliable, due to PHP being horribly unreliable when it comes to
	 * determining the mime-type of a file.
	 *
	 * @param   string   filename
	 * @return  string   mime-type, if found
	 * @return  boolean  FALSE, if not found
	 */
	public static function mime($filename)
	{
		// Make sure the file is readable
		if ( ! (is_file($filename) AND is_readable($filename)))
			return FALSE;

		// Get the extension from the filename
		$extension = strtolower(substr(strrchr($filename, '.'), 1));

		if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension))
		{
			// Disable error reporting
			$ER = error_reporting(0);

			// Use getimagesize() to find the mime type on images
			$mime = getimagesize($filename);

			// Turn error reporting back on
			error_reporting($ER);

			// Return the mime type
			if (isset($mime['mime']))
				return $mime['mime'];
		}

		if (function_exists('finfo_open'))
		{
			// Use the fileinfo extension
			$finfo = finfo_open(FILEINFO_MIME);
			$mime  = finfo_file($finfo, $filename);
			finfo_close($finfo);

			// Return the mime type
			return $mime;
		}

		if (ini_get('mime_magic.magicfile') AND function_exists('mime_content_type'))
		{
			// Return the mime type using mime_content_type
			return mime_content_type($filename);
		}

		if ( ! KOHANA_IS_WIN)
		{
			// Attempt to locate use the file command, checking the return value
			if ($command = trim(exec('which file', $output, $return)) AND $return === 0)
			{
				return trim(exec($command.' -bi '.escapeshellarg($filename)));
			}
		}

		if ( ! empty($extension) AND is_array($mime = Kohana::config('mimes.'.$extension)))
		{
			// Return the mime-type guess, based on the extension
			return $mime[0];
		}

		// Unable to find the mime-type
		return FALSE;
	}

	/**
	 * Split a file into pieces matching a specific size.
	 *
	 * @param   string   file to be split
	 * @param   string   directory to output to, defaults to the same directory as the file
	 * @param   integer  size, in MB, for each piece to be
	 * @return  integer  The number of pieces that were created.
	 */
	public static function split($filename, $piece_size = 10)
	{
		// Open the input file
		$file = fopen($filename, 'rb');

		// Change the piece size to bytes
		$piece_size = floor($piece_size * 1024 * 1024);

		// Write files in 8k blocks
		$block_size = 1024 * 8;

		// Total number of peices
		$peices = 0;

		while ( ! feof($file))
		{
			// Create another piece
			$peices += 1;

			// Create a new file piece
			$piece = str_pad($peices, 3, '0', STR_PAD_LEFT);
			$piece = fopen($filename.'.'.$piece, 'wb+');

			// Number of bytes read
			$read = 0;

			do
			{
				// Transfer the data in blocks
				fwrite($piece, fread($file, $block_size));

				// Another block has been read
				$read += $block_size;
			}
			while ($read < $piece_size);

			// Close the piece
			fclose($piece);
		}

		// Close the file
		fclose($file);

		return $peices;
	}

	/**
	 * Join a split file into a whole file.
	 *
	 * @param   string   split filename, without .000 extension
	 * @param   string   output filename, if different then an the filename
	 * @return  integer  The number of pieces that were joined.
	 */
	public static function join($filename)
	{
		// Open the file
		$file = fopen($filename, 'wb+');

		// Read files in 8k blocks
		$block_size = 1024 * 8;

		// Total number of peices
		$pieces = 0;

		while (is_file($piece = $filename.'.'.str_pad($pieces + 1, 3, '0', STR_PAD_LEFT)))
		{
			// Read another piece
			$pieces += 1;

			// Open the piece for reading
			$piece = fopen($piece, 'rb');

			while ( ! feof($piece))
			{
				// Transfer the data in blocks
				fwrite($file, fread($piece, $block_size));
			}

			// Close the peice
			fclose($piece);
		}

		return $pieces;
	}

} // End file
