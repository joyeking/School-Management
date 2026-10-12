<?php
/**
 * Ethiopian Calendar functions
 *
 * RosarioSIS stores every date as a Gregorian (ISO) value in the database.
 * Ethiopian schools however work with the Ethiopian calendar (Amete Mihret,
 * ዓ.ም. — 13 months). This file converts between the two so the Ethiopian
 * calendar can be shown as a *display* layer without touching stored data.
 *
 * Enable / disable:
 *   - Site wide:  $EthiopianCalendar = true;  in config.inc.php
 *   - Per user:   Users > My Preferences > Display Options
 *                 (falls back to the site wide setting)
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Julian Day Number of 1 Meskerem 1 E.C. (Ethiopian epoch).
 */
define( 'ETHIOPIC_EPOCH_JDN', 1724221 );

/**
 * Is the Ethiopian calendar display enabled?
 *
 * Reads the per-user preference first (ETHIOPIAN_CALENDAR = Y/N),
 * then falls back to the site wide $EthiopianCalendar config.
 *
 * @return boolean
 */
function EthiopianCalendarEnabled()
{
	global $EthiopianCalendar;

	if ( function_exists( 'Preferences' ) )
	{
		$pref = Preferences( 'ETHIOPIAN_CALENDAR' );

		if ( $pref === 'Y' )
		{
			return true;
		}

		if ( $pref === 'N' )
		{
			return false;
		}
	}

	return ! empty( $EthiopianCalendar );
}


/**
 * Gregorian date to Julian Day Number.
 *
 * @param  int $year  Gregorian year.
 * @param  int $month Gregorian month (1-12).
 * @param  int $day   Gregorian day.
 *
 * @return int Julian Day Number.
 */
function GregorianToJDN( $year, $month, $day )
{
	$a = intdiv( 14 - $month, 12 );
	$y = $year + 4800 - $a;
	$m = $month + 12 * $a - 3;

	return $day
		+ intdiv( 153 * $m + 2, 5 )
		+ 365 * $y
		+ intdiv( $y, 4 )
		- intdiv( $y, 100 )
		+ intdiv( $y, 400 )
		- 32045;
}


/**
 * Julian Day Number to Gregorian date.
 *
 * @param  int $jdn Julian Day Number.
 *
 * @return array ['year' => int, 'month' => int, 'day' => int]
 */
function JDNToGregorian( $jdn )
{
	$a = $jdn + 32044;
	$b = intdiv( 4 * $a + 3, 146097 );
	$c = $a - intdiv( 146097 * $b, 4 );
	$d = intdiv( 4 * $c + 3, 1461 );
	$e = $c - intdiv( 1461 * $d, 4 );
	$m = intdiv( 5 * $e + 2, 153 );

	return [
		'day' => $e - intdiv( 153 * $m + 2, 5 ) + 1,
		'month' => $m + 3 - 12 * intdiv( $m, 10 ),
		'year' => 100 * $b + $d - 4800 + intdiv( $m, 10 ),
	];
}


/**
 * Days elapsed from the Ethiopian epoch to 1 Meskerem of the given year.
 *
 * @param  int $year Ethiopian year.
 *
 * @return int
 */
function EthiopianDaysToYear( $year )
{
	return 365 * ( $year - 1 ) + intdiv( $year, 4 );
}


/**
 * Ethiopian date to Julian Day Number.
 *
 * @param  int $year  Ethiopian year.
 * @param  int $month Ethiopian month (1-13).
 * @param  int $day   Ethiopian day (1-30, 1-6 for month 13).
 *
 * @return int Julian Day Number.
 */
function EthiopianToJDN( $year, $month, $day )
{
	return ETHIOPIC_EPOCH_JDN
		+ EthiopianDaysToYear( (int) $year )
		+ 30 * ( (int) $month - 1 )
		+ ( (int) $day - 1 );
}


/**
 * Julian Day Number to Ethiopian date.
 *
 * @param  int $jdn Julian Day Number.
 *
 * @return array ['year' => int, 'month' => int, 'day' => int]
 */
function JDNToEthiopian( $jdn )
{
	$n = $jdn - ETHIOPIC_EPOCH_JDN;

	// Approximate year then correct.
	$year = (int) floor( $n / 365.25 ) + 1;

	while ( EthiopianDaysToYear( $year + 1 ) <= $n )
	{
		$year++;
	}

	while ( EthiopianDaysToYear( $year ) > $n )
	{
		$year--;
	}

	$day_of_year = $n - EthiopianDaysToYear( $year ); // 0 based.

	return [
		'month' => intdiv( $day_of_year, 30 ) + 1,
		'day' => ( $day_of_year % 30 ) + 1,
		'year' => $year,
	];
}


/**
 * Convert a Gregorian date to an Ethiopian date array.
 *
 * @param  int $year  Gregorian year.
 * @param  int $month Gregorian month.
 * @param  int $day   Gregorian day.
 *
 * @return array ['year' => int, 'month' => int, 'day' => int]
 */
function EthiopianFromGregorian( $year, $month, $day )
{
	return JDNToEthiopian( GregorianToJDN( (int) $year, (int) $month, (int) $day ) );
}


/**
 * Convert an Ethiopian date to a Gregorian date array.
 *
 * @param  int $year  Ethiopian year.
 * @param  int $month Ethiopian month.
 * @param  int $day   Ethiopian day.
 *
 * @return array ['year' => int, 'month' => int, 'day' => int]
 */
function GregorianFromEthiopian( $year, $month, $day )
{
	return JDNToGregorian( EthiopianToJDN( (int) $year, (int) $month, (int) $day ) );
}


/**
 * Ethiopian month names (13 months).
 *
 * @param  boolean $short Return short names.
 *
 * @return array Month number => name.
 */
function EthiopianMonthNames( $short = false )
{
	$long = [
		1 => 'Meskerem',
		2 => 'Tikimt',
		3 => 'Hidar',
		4 => 'Tahsas',
		5 => 'Tir',
		6 => 'Yekatit',
		7 => 'Megabit',
		8 => 'Miazia',
		9 => 'Ginbot',
		10 => 'Sene',
		11 => 'Hamle',
		12 => 'Nehase',
		13 => 'Pagume',
	];

	if ( ! $short )
	{
		return $long;
	}

	$short_names = [];

	foreach ( $long as $number => $name )
	{
		$short_names[ $number ] = mb_substr( $name, 0, 3 );
	}

	return $short_names;
}


/**
 * Format a Gregorian ISO date as an Ethiopian calendar string.
 *
 * @param  string $gregorian_date Gregorian ISO date (Y-m-d).
 * @param  string $length         long|short Month name length (optional).
 *
 * @return string e.g. "1 Meskerem 2016 E.C." or "1 Mes 2016 E.C."
 */
function EthiopianDate( $gregorian_date, $length = 'long' )
{
	$exploded = ( function_exists( 'ExplodeDate' ) )
		? ExplodeDate( (string) $gregorian_date )
		: [ 'year' => date( 'Y', strtotime( $gregorian_date ) ), 'month' => date( 'm', strtotime( $gregorian_date ) ), 'day' => date( 'd', strtotime( $gregorian_date ) ) ];

	if ( empty( $exploded['month'] ) )
	{
		return '';
	}

	$ethiopian = EthiopianFromGregorian(
		(int) $exploded['year'],
		(int) $exploded['month'],
		(int) $exploded['day']
	);

	$months = EthiopianMonthNames( $length === 'short' );

	return $ethiopian['day'] . ' ' . $months[ $ethiopian['month'] ] . ' ' . $ethiopian['year'] . ' E.C.';
}


/**
 * Ethiopian ISO-like date (Y-m-d) for export / machine use.
 *
 * @param  string $gregorian_date Gregorian ISO date.
 *
 * @return string e.g. "2016-09-01".
 */
function EthiopianDateISO( $gregorian_date )
{
	$exploded = ( function_exists( 'ExplodeDate' ) )
		? ExplodeDate( (string) $gregorian_date )
		: [ 'year' => date( 'Y', strtotime( $gregorian_date ) ), 'month' => date( 'm', strtotime( $gregorian_date ) ), 'day' => date( 'd', strtotime( $gregorian_date ) ) ];

	if ( empty( $exploded['month'] ) )
	{
		return '';
	}

	$ethiopian = EthiopianFromGregorian(
		(int) $exploded['year'],
		(int) $exploded['month'],
		(int) $exploded['day']
	);

	return sprintf( '%04d-%02d-%02d', $ethiopian['year'], $ethiopian['month'], $ethiopian['day'] );
}
