<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator\Helper;

use Drupal\hebrew_calendar_generator\HebrewMonth;
use Drupal\hebrew_calendar_generator\HebrewYearType;

/**
 * General helper functions dealing with dates.
 *
 * @static
 */
final class DateHelpers {

  private function __construct() {}

  /**
   * Adds days to a Hebrew date (day/month/year).
   *
   * If $numDaysInNextHebrewYear is provided, can generate a final date all the way into $hebrewYear
   * + 1 and part of the way into $hebrewYear + 2 (up to the point at which the variableness of the
   * months) becomes an issue. If only $numDaysInCurrentHebrewYear is provided, can generate a final
   * date in $hebrewYear and part of the way into $hebrewYear + 1.
   *
   * @param int $daysToAdd
   *   Number of days to add.
   * @param int $hebrewYear
   *   Hebrew year of date to which days are being added.
   * @param HebrewMonth $hebrewMonth
   *   Hebrew month of date to which days are being added.
   * @param int $hebrewDay
   *   Hebrew month day of date to which days are being added.
   * @param int $newHebrewYear
   *   (output) Final Hebrew year.
   * @param \Drupal\hebrew_calendar_generator\HebrewMonth $newHebrewMonth
   *   (output) Final Hebrew month.
   * @param int $newHebrewDay
   *   (output) Final Hebrew day.
   * @param int $numDaysInCurrentHebrewYear
   *   Num days in Hebrew year $hebrewYear.
   * @param int|null $numDaysInNextHebrewYear
   *   If available, num days in Hebrew year $hebrewYear + 1.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $daysToAdd is negative.
   * @throws \InvalidArgumentException
   *   Thrown if $hebrewYear is nonpositive or if $hebrewDay is not between 1 and 30, inclusive.
   * @throws \InvalidArgumentException
   *   Thrown if $numDaysInCurrentHebrewYear is not between 1 and 30, inclusive.
   * @throws \InvalidArgumentException
   *   Thrown if $daysToAdd is too large for the values of $numDaysInCurrentHebrewYear and
   *   $numDaysInNextHebrewYear.
   */
  public static function bumpHebrewDate(int $daysToAdd,
    int $hebrewYear,
    HebrewMonth $hebrewMonth,
    int $hebrewDay,
    int &$newHebrewYear,
    HebrewMonth &$newHebrewMonth,
    int &$newHebrewDay,
    int $numDaysInCurrentHebrewYear,
    ?int $numDaysInNextHebrewYear = NULL) : void {

    if ($daysToAdd < 0) {
      throw new \InvalidArgumentException('$daysToAdd must be non-negative.');
    }
    if ($hebrewYear <= 0) {
      throw new \InvalidArgumentException('$hebrewYear must be positive.');
    }

    $yearDay = self::getHebrewYearDay($hebrewMonth, $hebrewDay, $numDaysInCurrentHebrewYear);
    $newYearDay = $yearDay + $daysToAdd;
    $newHebrewYear = $hebrewYear;
    if ($newYearDay > $numDaysInCurrentHebrewYear) {
      $newHebrewYear++;
      $newYearDay -= $numDaysInCurrentHebrewYear;

      if ($numDaysInNextHebrewYear === NULL) {
        // Since we don't know what type of year this is, we can only go up to the minimum possible
        // value for the number of days up to and including the first variable month (Cheshvan).
        $maxDays = self::getDaysUpToAndIncludingMonth(HebrewMonth::Cheshvan, FALSE, HebrewYearType::Deficient) - 1;
        if ($newYearDay > $maxDays) {
          throw new \InvalidArgumentException('The date bump goes too far into the next Hebrew year, and $numDaysInNextHebrewYear was not provided.');
        }
        // Just use the smallest possible number of days in the Hebrew year, as it doesn't matter...
        self::hebrewYearDayToDay($newYearDay, 353, $newHebrewMonth, $newHebrewDay);
      }
      else {
        if ($newYearDay > $numDaysInNextHebrewYear) {
          $newHebrewYear++;
          $newYearDay -= $numDaysInNextHebrewYear;

          $maxDays = self::getDaysUpToAndIncludingMonth(HebrewMonth::Cheshvan, FALSE, HebrewYearType::Deficient) - 1;
          if ($newYearDay > $maxDays) {
            throw new \InvalidArgumentException('The date bump goes too far into the third Hebrew year.');
          }
          self::hebrewYearDayToDay($newYearDay, 353, $newHebrewMonth, $newHebrewDay);
        }
        self::hebrewYearDayToDay($newYearDay, $numDaysInNextHebrewYear, $newHebrewMonth, $newHebrewDay);
      }
    }
    else self::hebrewYearDayToDay($newYearDay, $numDaysInCurrentHebrewYear, $newHebrewMonth, $newHebrewDay);
  }

  /**
   * Gets the number of days in the Hebrew year up to an including the given month.
   */
  public static function getDaysUpToAndIncludingMonth(HebrewMonth $hebrewMonth, bool $isLeapYear, HebrewYearType $yearType) : int {
    return match ($hebrewMonth) {
      HebrewMonth::Nisan => 30,
      HebrewMonth::Iyar => 30 + 29,
      HebrewMonth::Sivan => 30 + 29 + 30,
      HebrewMonth::Tammuz => 30 + 29 + 30 + 29,
      HebrewMonth::Av => 30 + 29 + 30 + 29 + 30,
      HebrewMonth::Elul => 30 + 29 + 30 + 29 + 30 + 29,
      HebrewMonth::Tishrei => 30 + 29 + 30 + 29 + 30 + 29 + 30,
      HebrewMonth::Cheshvan => 30 + 29 + 30 + 29 + 30 + 29 + 30 + ($yearType === HebrewYearType::Complete ? 30 : 29),
      HebrewMonth::Kislev => 30 + 29 + 30 + 29 + 30 + 29 + 30 + self::getTotalDaysVariableMonths($yearType),
      HebrewMonth::Tevet => 30 + 29 + 30 + 29 + 30 + 29 + 30 + self::getTotalDaysVariableMonths($yearType) + 29,
      HebrewMonth::Shevat => 30 + 29 + 30 + 29 + 30 + 29 + 30 + self::getTotalDaysVariableMonths($yearType) + 29 + 30,
      HebrewMonth::Adar => 30 + 29 + 30 + 29 + 30 + 29 + 30 + self::getTotalDaysVariableMonths($yearType) + 29 + 30 + 29,
      HebrewMonth::AdarI => 30 + 29 + 30 + 29 + 30 + 29 + 30 + self::getTotalDaysVariableMonths($yearType) + 29 + 30 + 30,
      HebrewMonth::AdarII => 30 + 29 + 30 + 29 + 30 + 29 + 30 + self::getTotalDaysVariableMonths($yearType) + 29 + 30 + 30 + 29,
    };
  }

  /**
   * Gets the year day (day in year, starting from 1) for a Hebrew month and day.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $hebrewDay is not between 1 and 30, inclusive.
   * @throws \InvalidArgumentException
   *   Thrown if $numDaysInCurrentHebrewYear is invalid.
   */
  public static function getHebrewYearDay(HebrewMonth $hebrewMonth, int $hebrewDay, int $numDaysInCurrentHebrewYear) : int {
    if ($hebrewDay < 1 || $hebrewDay > 30) {
      throw new \InvalidArgumentException('$hebrewDay must be between 1 and 30, inclusive.');
    }

    if ($hebrewMonth === HebrewMonth::Nisan) return $hebrewDay;

    $isLeapYear = FALSE;
    $yearType = HebrewYearType::Regular;
    self::getHebrewYearInformation($numDaysInCurrentHebrewYear, $isLeapYear, $yearType);
    return self::getDaysUpToAndIncludingMonth(HebrewMonth::fromInt($hebrewMonth->toInt() - 1, $isLeapYear), $isLeapYear, $yearType) + $hebrewDay;
  }

  /**
   * Gets leap year status and year type for a Hebrew year.
   * 
   * @param int $numDaysInCurrentHebrewYear
   *   Number of days in Hebrew year for which information is sought.
   * @param bool $isLeapYear
   *   (output) Whether the Hebrew year is a leap year.
   * @param \Drupal\hebrew_calendar_generator\HebrewYearType $yearType
   *   (output) Type of the Hebrew year.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $numDaysInCurrentHebrewYear is invalid.
   */
  public static function getHebrewYearInformation(int $numDaysInCurrentHebrewYear, bool &$isLeapYear, HebrewYearType &$yearType) {
    $isLeapYear = self::isHebrewLeapYear($numDaysInCurrentHebrewYear);
    if ($isLeapYear) $yearType = match($numDaysInCurrentHebrewYear) {
      383 => HebrewYearType::Deficient,
      384 => HebrewYearType::Regular,
      385 => HebrewYearType::Complete,
      default => throw new \InvalidArgumentException('A Hebrew leap year must have 383, 384, or 385 days.'),
    };
    else $yearType = match($numDaysInCurrentHebrewYear) {
      353 => HebrewYearType::Deficient,
      354 => HebrewYearType::Regular,
      355 => HebrewYearType::Complete,
      default => throw new \InvalidArgumentException('A Hebrew non-leap year must have 353, 354, or 355 days.'),
    };
  }

  /**
   * Converts given Hebrew year day (day of year, starting from 1) to a month and day of month.
   *
   * @param int $hebrewYearDay
   *   Day of Hebrew year.
   * @param int $numDaysInCurrentHebrewYear
   *   Number of days in Hebrew year for which information is sought.
   * @param \Drupal\hebrew_calendar_generator\HebrewMonth $hebrewMonth
   *   (output) Month of the Hebrew year.
   * @param int $hebrewMonthDay
   *   (output) Day of the month in the Hebrew calendar.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $hebrewYearDay is less than one or greater than $numDaysInCurrentHebrewYear.
   * @throws \InvalidArgumentException
   *   Thrown if $numDaysInCurrentHebrewYear is invalid.
   */
  public static function hebrewYearDayToDay(int $hebrewYearDay, int $numDaysInCurrentHebrewYear, HebrewMonth &$hebrewMonth, int &$hebrewMonthDay) : void {
    if ($hebrewYearDay < 1) {
      throw new \InvalidArgumentException('$hebrewYearDay must be greater than 0.');
    }

    $isLeapYear = FALSE;
    $yearType = HebrewYearType::Regular;
    self::getHebrewYearInformation($numDaysInCurrentHebrewYear, $isLeapYear, $yearType);

    if ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Nisan, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Nisan;
      $hebrewMonthDay = $hebrewYearDay;
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Iyar, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Iyar;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Nisan, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Sivan, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Sivan;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Iyar, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Tammuz, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Tammuz;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Sivan, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Av, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Av;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Tammuz, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Elul, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Elul;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Av, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Tishrei, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Tishrei;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Elul, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Cheshvan, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Cheshvan;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Tishrei, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Kislev, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Kislev;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Cheshvan, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Tevet, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Tevet;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Kislev, $isLeapYear, $yearType);
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Shevat, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Shevat;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Tevet, $isLeapYear, $yearType);
    }
    elseif ($isLeapYear) {
      if ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::AdarI, $isLeapYear, $yearType)) {
        $hebrewMonth = HebrewMonth::AdarI;
        $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Shevat, $isLeapYear, $yearType);
      }
      elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::AdarII, $isLeapYear, $yearType)) {
        $hebrewMonth = HebrewMonth::AdarII;
        $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::AdarI, $isLeapYear, $yearType);
      }
      else {
        throw new \InvalidArgumentException('$hebrewYearDay is greater than the number of days in the Hebrew year.');
      }
    }
    elseif ($hebrewYearDay <= self::getDaysUpToAndIncludingMonth(HebrewMonth::Adar, $isLeapYear, $yearType)) {
      $hebrewMonth = HebrewMonth::Adar;
      $hebrewMonthDay = $hebrewYearDay - self::getDaysUpToAndIncludingMonth(HebrewMonth::Shevat, $isLeapYear, $yearType);
    }
    else {
      throw new \InvalidArgumentException('$hebrewYearDay is greater than the number of days in the Hebrew year.');
    }
  }

  /**
   * Determines whether a given Hebrew year is a leap year.
   *
   * @param int $numDaysInHebrewYear
   *   Number of days in Hebrew year for which information is sought.
   */
  public static function isHebrewLeapYear(int $numDaysInHebrewYear) : bool {
    return $numDaysInHebrewYear > 360;
  }

  /**
   * Tells whether the given value is a valid number of days in a Hebrew year.
   */
  public static function isValidNumberOfDaysInHebrewYear(int $numDaysInHebrewYear) : bool {
    return match($numDaysInHebrewYear) {
      353, 354, 355, 383, 384, 385 => TRUE,
      default => FALSE,
    };
  }

  /**
   * Tells whether the given value is a valid number of days in a solar year.
   */
  public static function isValidNumberOfDaysInSolarYear(int $numDaysInSolarYear) : bool {
    return match($numDaysInSolarYear) {
      365, 366 => TRUE,
      default => FALSE,
    };
  }

  private static function getTotalDaysVariableMonths(HebrewYearType $yearType) : int {
    return match($yearType) {
      HebrewYearType::Complete => 30 + 30,
      HebrewYearType::Regular => 29 + 30,
      HebrewYearType::Deficient => 29 + 29,
    };
  }

}
