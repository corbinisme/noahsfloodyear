<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

use Drupal\hebrew_calendar_generator\Helper\DateHelpers;

/**
 * Represents a "year" from week of first Sabb. in Jan. to first Sab. of next Heb. year.
 *
 * For A.M. year 1, the year does not start with the week of the first Sabbath of January, but
 * rather with the week of the first Sabbath of the A.M. year.
 *
 * Note: This class does not include the "difference between the last and the present year" in its
 * properties, as I didn't understand what that meant.
 */
class CalendarYear {

  public const CREATION_START_GREGORIAN_DAY = 13;
  public const CREATION_START_GREGORIAN_YEAR = -4046;

  /**
   * Primary Hebrew (A.M.) year, starting at 1.
  */
  public readonly int $hebrewYear;

  /**
   * Primary Gregorian year, with negative numbers representing B.C. years (-1 is 1 B.C.).
   */
  public readonly int $gregorianYear;

  /**
   * Cycle number from creation of the current 19-year cycle, starting from 1.
   */
  public readonly int $cycleId19Year;

  /**
   * Cycle number from creation of the current 247-year cycle, starting from 1.
   */
  public readonly int $cycleId247Year;

  /**
   * Number of days in the primary Hebrew year for this object.
   */
  public readonly int $hebrewYearDays;

  /**
   * Number of days in the primary solar year for this object.
   */
  public readonly int $solarYearDays;

  /**
   * If applicable, number of days in the *previous* solar year until first Sabbath of this year.
   */
  public readonly ?int $solarYearDaysToFirstGregorianSabbath;

  /**
   * Difference between the solar and Hebrew calendars.
   */
  public readonly int $diffBetweenSolarAndHebrewDay;

  /**
   * If applicable, the difference between the solar and Hebrew calendars in the *previous* year.
   */
  public readonly ?int $diffBetweenSolarAndHebrewDayPreviousYear;

  /**
   * Number of days in primary Gregorian year.
   */
  public readonly int $gregorianYearDays;

  /**
   * Number (1-19) in the current 19-year cycle of this A.M. year.
   */
  public readonly int $yearIn19YearCycle;

  private readonly int $weekIdSinceCreationForFirstSabbath;

  private readonly HebrewMonth $firstHebrewMonth;
  private readonly int $firstHebrewDay;
  private readonly int $firstSolarYear;
  private readonly int $firstSolarDay;

  private readonly ?int $numDaysInPreviousHebrewYear;
  private readonly ?int $numDaysInPreviousSolarYear;

  /**
   * @param int $hebrewYear
   *   Primary Hebrew (A.M.) year, starting at 1.
   * @param int $gregorianYear
   *   Primary Gregorian year, with negative numbers representing B.C. years.
   * @param int $cycleId19Year
   *  Cycle ID since creation for this year's 19-year cycle, with 1 being the first cycle.
   * @param int $cycleId247Year
   *   Cycle ID since creation for this year's 247-year cycle, with 1 being the first cycle.
   * @param int $hebrewYearDays
   *   Number of days in the primary Hebrew year.
   * @param int $solarYearDays
   *   Number of days in the primary solar year.
   * @param ?int $solarYearDaysToFirstGregorianSabbath
   *   Number of days in the previous solar year until the first Sabbath of the primary Gregorian
   *   year, or NULL if not applicable.
   * @param int $diffBetweenSolarAndHebrewDay
   *   Difference in days between the solar and Hebrew calendar for primary Hebrew year.
   * @param ?int $numDaysInPreviousHebrewYear
   *   If applicable, the number of days in the year before the A.M. year for this calendar year
   *   object.
   * @param ?int $numDaysInPreviousSolarYear
   *   If applicable, the number of days in the year before the solar year for this calendar year
   *   object.
   * @param int $yearIn19YearCycle
   *   Year in the 19-year cycle, with 1 being the first year of the cycle.
   * @param int $weekIdSinceCreationForFirstSabbath
   *   Week ID since creation for the first Sabbath of this year, with 1 being the first week.
   * @param \Drupal\hebrew_calendar_generator\HebrewMonth $firstHebrewMonth
   *   Hebrew month of the first day of this calendar year.
   * @param int $firstHebrewDay
   *   Day of the Hebrew month of the first day of this calendar year, starting from 1.
   * @param int $firstSolarYear
   *   The solar year corresponding to the start of this calendar year, starting from 1.
   * @param int $firstSolarDay
   *   The solar day of the year corresponding to the start of this calendar year, starting from 1.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $hebrewYear is not positive.
   * @throws \InvalidArgumentException
   *   Thrown if $gregorianYear is zero.
   * @throws \InvalidArgumentException
   *   Thrown if $cycleId19Year or $cycleId247Year is not positive.
   * @throws \InvalidArgumentException
   *   Thrown if $hebrewYearDays is invalid.
   * @throws \InvalidArgumentException
   *   Thrown if $solarYearDays is not 365 nor 366.
   * @throws \InvalidArgumentException
   *   Thrown if $solarYearDaysToFirstGregorianSabbath is non-null and nonpositive.
   * @throws \InvalidArgumentException
   *   Thrown if $yearIn19YearCycle is not between 1 and 19, inclusive.
   * @throws \InvalidArgumentException
   *   Thrown if $weekIdSinceCreationForFirstSabbath is not positive.
   * @throws \InvalidArgumentException
   *   Thrown if $firstHebrewDay is not between 1 and 30, inclusive.
   * @throws \InvalidArgumentException
   *   Thrown if $firstSolarYear is not positive.
   * @throws \InvalidArgumentException
   *   Thrown if $firstSolarDay is not positive.
   * @throws \InvalidArgumentException
   *   Thrown if $numDaysInPreviousHebrewYear or $numDaysInPreviousSolarYear is NULL or invalid,
   *   when $hebrewYear is greater than one.
   */
  public function __construct(int $hebrewYear,
    int $gregorianYear,
    int $cycleId19Year,
    int $cycleId247Year,
    int $hebrewYearDays,
    int $solarYearDays,
    ?int $solarYearDaysToFirstGregorianSabbath,
    int $diffBetweenSolarAndHebrewDay,
    ?int $diffBetweenSolarAndHebrewDayPreviousYear,
    ?int $numDaysInPreviousHebrewYear,
    ?int $numDaysInPreviousSolarYear,
    int $yearIn19YearCycle,
    int $weekIdSinceCreationForFirstSabbath,
    HebrewMonth $firstHebrewMonth,
    int $firstHebrewDay,
    int $firstSolarYear,
    int $firstSolarDay) {

    if ($hebrewYear <= 0) {
      throw new \InvalidArgumentException('$hebrewYear must be greater than 0.');
    }
    if ($gregorianYear == 0) {
      throw new \InvalidArgumentException('$gregorianYear cannot be 0.');
    }
    if ($cycleId19Year <= 0) {
      throw new \InvalidArgumentException('$cycleId19Year must be greater than 0.');
    }
    if ($cycleId247Year <= 0) {
      throw new \InvalidArgumentException('$cycleId247Year must be greater than 0.');
    }
    if (!DateHelpers::isValidNumberOfDaysInHebrewYear($hebrewYearDays)) {
      throw new \InvalidArgumentException('$hebrewYearDays must be greater than 0.');
    }
    if (!DateHelpers::isValidNumberOfDaysInSolarYear($solarYearDays)) {
      throw new \InvalidArgumentException('$solarYearDays must be 365 or 366.');
    }
    if ($solarYearDaysToFirstGregorianSabbath !== NULL && $solarYearDaysToFirstGregorianSabbath <= 0) {
      throw new \InvalidArgumentException('$solarYearDaysToFirstGregorianSabbath must be greater than 0.');
    }

    if ($yearIn19YearCycle <= 0 || $yearIn19YearCycle > 19) {
      throw new \InvalidArgumentException('$yearIn19YearCycle must be between 1 and 19, inclusive.');
    }
    if ($weekIdSinceCreationForFirstSabbath <= 0) {
      throw new \InvalidArgumentException('$weekIdSinceCreationForFirstSabbath must be greater than 0.');
    }
    if ($firstHebrewMonth < 1 || $firstHebrewDay > 30) {
      throw new \InvalidArgumentException('$firstGregSabHebrewDay must be between 1 and 30, inclusive.');
    }
    if ($firstSolarYear <= 0) {
      throw new \InvalidArgumentException('$solarYear must be greater than 0.');
    }
    if ($firstSolarDay <= 0) {
      throw new \InvalidArgumentException('$firstSolarDay must be greater than 0.');
    }
    if ($hebrewYear !== 1) {
      if ($numDaysInPreviousHebrewYear === NULL || !DateHelpers::isValidNumberOfDaysInHebrewYear($numDaysInPreviousHebrewYear)) {
        throw new \InvalidArgumentException('$numDaysInPreviousHebrewYear must a valid integer for all years past year 1');
      }
      if ($numDaysInPreviousSolarYear === NULL || !DateHelpers::isValidNumberOfDaysInSolarYear($numDaysInPreviousSolarYear)) {
        throw new \InvalidArgumentException('$numDaysInPreviousSolarYear must a valid integer for all years past year 1.');
      }
    }

    $this->hebrewYear = $hebrewYear;
    $this->gregorianYear = $gregorianYear;
    $this->cycleId19Year = $cycleId19Year;
    $this->cycleId247Year = $cycleId247Year;
    $this->hebrewYearDays = $hebrewYearDays;
    $this->solarYearDays = $solarYearDays;
    $this->solarYearDaysToFirstGregorianSabbath = $solarYearDaysToFirstGregorianSabbath;
    $this->diffBetweenSolarAndHebrewDay = $diffBetweenSolarAndHebrewDay;
    $this->diffBetweenSolarAndHebrewDayPreviousYear = $diffBetweenSolarAndHebrewDayPreviousYear;
    $this->numDaysInPreviousHebrewYear = $numDaysInPreviousHebrewYear;
    $this->numDaysInPreviousSolarYear = $numDaysInPreviousSolarYear;
    $this->yearIn19YearCycle = $yearIn19YearCycle;
    $this->weekIdSinceCreationForFirstSabbath = $weekIdSinceCreationForFirstSabbath;
    $this->firstHebrewDay = $firstHebrewDay;
    $this->firstHebrewMonth = $firstHebrewMonth;
    $this->firstSolarYear = $firstSolarYear;
    $this->firstSolarDay = $firstSolarDay;

    // Calculate the number of days in the Gregorian year using the well-known leap year rules.
    $gregorianYearWithZero = $gregorianYear < 0 ? ($gregorianYear + 1) : $gregorianYear;
    if ($gregorianYearWithZero % 4 === 0 &&
      ($gregorianYearWithZero % 100 !== 0 || $gregorianYearWithZero % 400 === 0)) {
      $this->gregorianYearDays = 366;
    } else {
      $this->gregorianYearDays = 365;
    }
  }

  /**
   * Enumerates the weeks in this year, in order.
   *
   * Usage:
   * ```
   * // Assume $calendarYear is a CalendarYear object.
   * foreach ($calendarYear->enumerateWeeks() as $week) {
   *   // Do something week $week.
   * } // Easy!
   * ```
   *
   * @return iterable<\Drupal\hebrew_calendar_generator\CalendarWeek>
   *   An iterable yielding the weeks in this year. This object can only be iterated through once.
   */
  public function enumerateWeeks() : iterable {
    $numDaysInCurrentHebrewYear = NULL;
    $numDaysInCurrentSolarYear = NULL;

    // The calendar starts at a different spot for A.M. 1, so:
    if ($this->hebrewYear === 1) {
      $currentDay = new CalendarDay(Weekday::Sunday,
        $this->gregorianYear,
        self::getCreationStartGregorianMonth(),
        $currentGregorianDay = self::CREATION_START_GREGORIAN_DAY,
        1,
        HebrewMonth::Nisan,
        1,
        1,
        1);
      $numDaysInCurrentHebrewYear = $this->hebrewYearDays;
      $numDaysInCurrentSolarYear = $this->solarYearDays;
    }
    else {
      $currentGregorianYear = 0;
      $currentGregorianDay = 0;
      $currentGregorianMonth = GregorianMonth::January;
      self::getStartDateForGregorianYear($this->gregorianYear,
        $currentGregorianYear,
        $currentGregorianMonth,
        $currentGregorianDay);

      $currentDay = new CalendarDay(Weekday::Sunday,
        $currentGregorianYear,
        $currentGregorianMonth,
        $currentGregorianDay,
        $this->hebrewYear - 1,
        $this->firstHebrewMonth,
        $this->firstHebrewDay,
        $this->firstSolarYear,
        $this->firstSolarDay);
      $numDaysInCurrentHebrewYear = $this->numDaysInPreviousHebrewYear;
      $numDaysInCurrentSolarYear = $this->numDaysInPreviousSolarYear;
    }
    $sabbathWeekIdSinceCreation = $this->weekIdSinceCreationForFirstSabbath;

    do {
      $dayGeneratorFromOffset = function (int $dayOfWeekOffset)
        use ($currentDay, $numDaysInCurrentHebrewYear, $numDaysInCurrentSolarYear) : CalendarDay {

        if ($dayOfWeekOffset === 0) {
          return $currentDay;
        }

        $newGregorianDate = \DateTimeImmutable::createFromFormat('x-n-j', ($currentDay->gregorianYear < 0 ? $currentDay->gregorianYear + 1 : $currentDay->gregorianYear)
          . '-' . $currentDay->gregorianMonth->toInt()
          . '-' . ($currentDay->gregorianDay + $dayOfWeekOffset));

        $newSolarDay = $currentDay->solarDay + $dayOfWeekOffset;
        if ($newSolarDay > $numDaysInCurrentSolarYear) {
          $newSolarDay -= $numDaysInCurrentSolarYear;
          $newSolarYear = $currentDay->solarYear + 1;
        } else {
          $newSolarYear = $currentDay->solarYear;
        }

        $newHebrewYear = 0;
        $newHebrewMonth = HebrewMonth::Nisan;
        $newHebrewDay = 0;
        DateHelpers::bumpHebrewDate($dayOfWeekOffset,
          $currentDay->hebrewYear, $currentDay->hebrewMonth, $currentDay->hebrewDay,
          $newHebrewYear, $newHebrewMonth, $newHebrewDay,
          $numDaysInCurrentHebrewYear);

        $newGregorianYear = (int) $newGregorianDate->format('Y');
        if ($newGregorianYear <= 0) $newGregorianYear--;
        return new CalendarDay(Weekday::from(($dayOfWeekOffset % 7) + 1),
          $newGregorianYear,
          GregorianMonth::from((int) $newGregorianDate->format('n')),
          (int) $newGregorianDate->format('j'),
          $newHebrewYear,
          $newHebrewMonth,
          $newHebrewDay,
          $newSolarYear,
          $newSolarDay);
      };
      yield new CalendarWeek($sabbathWeekIdSinceCreation, fn (Weekday $wd) : CalendarDay
        => $dayGeneratorFromOffset($wd->toInt() - 1));

      // Jump to the next week, if need be! We only go one week in, at most, to the next year.
      if ($currentDay->hebrewYear > $this->hebrewYear) break;

      $newDay = $dayGeneratorFromOffset(7);
      if ($newDay->hebrewYear > $currentDay->hebrewYear) {
        $numDaysInCurrentHebrewYear = $this->hebrewYearDays;
        $numDaysInCurrentSolarYear = $this->solarYearDays;
      }
      $currentDay = $newDay;
      $sabbathWeekIdSinceCreation++;
    } while ($newDay->hebrewYear <= $this->hebrewYear || ($newDay->hebrewMonth === HebrewMonth::Nisan && $newDay->hebrewDay === 1));
  }

  /**
   * Gets the difference *of current and last year differences* between solar and Hebrew days.
   *
   * If there was no previous difference (i.e., this is year 1), this returns zero. Otherwise, it
   * returns the difference between the solar and Hebrew day for this year *minus* the same quantity
   * for the previous year. This should be used for the "Last Year's Difference" value in the
   * user-facing calendar output.
   */
  public function getDifferenceOfSolarHebrewOffsets() : int {
    return $this->diffBetweenSolarAndHebrewDayPreviousYear === NULL
      ? 0 : ($this->diffBetweenSolarAndHebrewDay - $this->diffBetweenSolarAndHebrewDayPreviousYear);
  }

  /**
   * Converts this calendar year into JSON form.
   *
   * @param bool $includeAllDays
   *   TRUE to include all days of all weeks in the output JSON; FALSE to only include the Sabbath
   *   of each week.
   */
  public function toJson(bool $includeAllDays = TRUE) : string {
    $weeks = [];
    foreach ($this->enumerateWeeks() as $week) {
      $days = [];
      if ($includeAllDays) {
        foreach ($week->enumerateDays() as $day) {
          $days[$day->dayOfWeek->toString()] = self::dayToArray($day);
        }
      }
      else {
        $days['Saturday'] = self::dayToArray($week->getDay(Weekday::Saturday));
      }
      $weeks[] = [
        'sabbath-number-from-creation' => $week->sabbathIdFromCreation,
        'days' => $days,
      ];
    }

    return json_encode([
      'hebrew-year' => $this->hebrewYear,
      'gregorian-year' => $this->gregorianYear,
      '19-year-cycle-number' => $this->cycleId19Year,
      '247-year-cycle-number' => $this->cycleId247Year,
      'days-in-hebrew-year' => $this->hebrewYearDays,
      'days-in-solar-year' => $this->solarYearDays,
      'solar-year-days-to-first-gregorian-sabbath' => $this->solarYearDaysToFirstGregorianSabbath,
      'diff-between-solar-and-hebrew-day' => $this->diffBetweenSolarAndHebrewDay,
      'diff-between-solar-and-hebrew-day-previous-year' => $this->diffBetweenSolarAndHebrewDayPreviousYear,
      'diff-of-solar-hebrew-offsets' => $this->getDifferenceOfSolarHebrewOffsets(),
      'days-in-gregorian-year' => $this->gregorianYearDays,
      'year-in-19-year-cycle' => $this->yearIn19YearCycle,
      'weeks' => $weeks,
    ]);
  }

  /**
   * Gets the Gregorian creation start month.
   */
  public static function getCreationStartGregorianMonth() : GregorianMonth {
    return GregorianMonth::March;
  }

  /**
   * Gets the Sunday before the first Sabbath in January for the given Gregorian year.
   *
   * @param int $gregorianYear
   *   Input Gregorian year.
   * @param int $startDateGregorianYear
   *   (output) Start date year.
   * @param GregorianMonth $startDateGregorianMonth
   *   (output) Start date month.
   * @param int $startDateGregorianDay
   *   (output) Start date day of month.
   */
  public static function getStartDateForGregorianYear(int $gregorianYear,
    int &$startDateGregorianYear,
    GregorianMonth &$startDateGregorianMonth,
    int &$startDateGregorianDay) : void {

    if ($gregorianYear === self::CREATION_START_GREGORIAN_YEAR) {
      $startDateGregorianYear = self::CREATION_START_GREGORIAN_YEAR;
      $startDateGregorianMonth = self::getCreationStartGregorianMonth();
      $startDateGregorianDay = self::CREATION_START_GREGORIAN_DAY;
    } else {
      // Calculate the day of the first Sabbath in January, and go back six days to find the
      // preceeding Sunday.
      // The first day of the week should be calculated with Saturday = 7.
      $dayOfWeekJan1 = (int) (\DateTimeImmutable::createFromFormat('x-n-j', ($gregorianYear < 0 ? $gregorianYear + 1 : $gregorianYear) . '-1-1'))->format('N') + 1;
      if ($dayOfWeekJan1 === 8) $dayOfWeekJan1 = 1;
      $currentGregorianSabbath = 8 - $dayOfWeekJan1;
      // Subtract six days:
      if ($currentGregorianSabbath === 7) {
        $startDateGregorianDay = 1;
        $startDateGregorianMonth = GregorianMonth::January;
        $startDateGregorianYear = $gregorianYear;
      } else {
        $startDateGregorianDay = 31 - (6 - $currentGregorianSabbath);
        $startDateGregorianMonth = GregorianMonth::December;
        $startDateGregorianYear = $gregorianYear === 1 ? -1 : $gregorianYear - 1;
      }
    }
  }

  private static function dayToArray(CalendarDay $day) : array {
    return [
      'gregorian-year' => $day->gregorianYear,
      'gregorian-month' => $day->gregorianMonth->toString(),
      'gregorian-day' => $day->gregorianDay,
      'hebrew-year' => $day->hebrewYear,
      'hebrew-month' => $day->hebrewMonth->toString(),
      'hebrew-day' => $day->hebrewDay,
      'solar-year' => $day->solarYear,
      'solar-day' => $day->solarDay,
      'feast-day-type' => $day->getFeastDayType()->toString(),
    ];
  }

}
