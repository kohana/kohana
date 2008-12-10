<?php

/**
 * Modules are additional resource paths. Any file that can be placed within
 * the application or system directories can also be placed in a module.
 * All modules are relative or absolute paths to directories.
 * 
 * @see  http://docs.kohanaphp.com/modules
 */
$config['modules'] = array
(
	MODPATH.'database',
	MODPATH.'forms',
	MODPATH.'email',
);

/**
 * Caching is an effective way to make your application scale better, at the
 * cost of updates being visibly "delayed". Enable this when your are no
 * longer making changes to the filesystem and file paths will be cached
 * between requests.
 * 
 * @see  http://docs.kohanaphp.com/deployment
 */
$config['save_cache'] = TRUE;

/**
 * Character set of your application. Using anything besides UTF-8 may result
 * in decreased compatibility and is strongly discouraged.
 *
 * @see  http://docs.kohanaphp.com/i18n
 * @see  http://php.net/iconv
 */
$config['charset'] = 'UTF-8';

/**
 * Locale of your application. Note that even if you are not using a POSIX
 * system, the first locale must be POSIX `xx_XX` locale name for internal i18n
 * support to work properly.
 *
 * @see  http://docs.kohanaphp.com/i18n
 * @see  http://php.net/setlocale
 * @see  http://msdn.microsoft.com/en-us/library/39cwe7zf(VS.80).aspx
 */
$config['locale'] = array('en_US.UTF-8', 'english-us');

/**
 * Time zone of your application. If you do not specify a time zone, the
 * default system time zone will be used instead.
 *
 * @see  http://php.net/manual/timezones.php
 * @see  http://php.net/manual/datetime.configuration.php
 */
$config['timezone'] = NULL;

return $config;
