<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

/**
 * Represents a single day of a calendar.
 */
class CalendarDay {

  public readonly Weekday $dayOfWeek;

  /**
   * Year on Gregorian calendar, with negative years representing B.C. dates (e.g., -1 is 1 B.C.).
   */
  public readonly int $gregorianYear;
  public readonly GregorianMonth $gregorianMonth;

  /**
   * Day of month on Gregorian calendar, starting from 1.
   */
  public readonly int $gregorianDay;

  public readonly int $hebrewYear;
  public readonly HebrewMonth $hebrewMonth;

  /**
   * Day of month on Hebrew calendar, starting from 1.
   */
  public readonly int $hebrewDay;

  public readonly int $solarYear;

  /**
   * Day of year on solar calendar, starting from 1.
   */
  public readonly int $solarDay;

  /**
   * @param \Drupal\hebrew_calendar_generator\Weekday $dayOfWeek 
   * @param int $gregorianYear
   *   Year on Gregorian calendar, with negative years representing B.C. dates.
   * @param \Drupal\hebrew_calendar_generator\GregorianMonth $gregorianMonth
   * @param int $gregorianDay
   *   Day of the month on Gregorian calendar, starting from 1.
   * @param int $hebrewYear
   * @param \Drupal\hebrew_calendar_generator\HebrewMonth $hebrewMonth
   * @param int $hebrewDay
   *   Day of the month on Hebrew calendar, starting from 1.
   * @param int $solarYear
   * @param int $solarCalendarDay
   *   Day of the year on the solar calendar, starting from 1.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $gregorianDay is not between 1 and 31 (inclusive), $hebrewDay is not between 1 and
   *   30 (inclusive), or $solarDay is nonpositive.
   * @throws \InvalidArgumentException
   *   Thrown if $gregorianYear is zero, $hebrewYear is not positive, or $solarYear is not positive.
   */
  public function __construct(Weekday $dayOfWeek,
    int $gregorianYear,
    GregorianMonth $gregorianMonth,
    int $gregorianDay,
    int $hebrewYear,
    HebrewMonth $hebrewMonth,
    int $hebrewDay,
    int $solarYear,
    int $solarDay) {

    if ($gregorianDay < 1 || $gregorianDay > 31) {
      throw new \InvalidArgumentException('$gregorianDay must be between 1 and 31, inclusive.');
    }
    if ($hebrewDay < 1 || $hebrewDay > 30) {
      throw new \InvalidArgumentException('$hebrewDay must be between 1 and 30, inclusive.');
    }
    if ($solarDay <= 0) {
      throw new \InvalidArgumentException('$solarDay must be greater than 0.');
    }
    if ($gregorianYear === 0) {
      throw new \InvalidArgumentException('$gregorianYear cannot be zero.');
    }
    if ($hebrewYear <= 0) {
      throw new \InvalidArgumentException('$hebrewYear must be greater than 0.');
    }
    if ($solarYear <= 0) {
      throw new \InvalidArgumentException('$solarYear must be greater than 0.');
    }

    $this->dayOfWeek = $dayOfWeek;
    $this->gregorianYear = $gregorianYear;
    $this->gregorianMonth = $gregorianMonth;
    $this->gregorianDay = $gregorianDay;
    $this->hebrewYear = $hebrewYear;
    $this->hebrewMonth = $hebrewMonth;
    $this->hebrewDay = $hebrewDay;
    $this->solarYear = $solarYear;
    $this->solarDay = $solarDay;
  }

  public function getFeastDayType() : FeastDayType {
    return match($this->hebrewMonth) {
      HebrewMonth::Nisan => match($this->hebrewDay) {
        14 => FeastDayType::Passover,
        15 => FeastDayType::FirstDayOfUnleavenedBread,
        21 => FeastDayType::LastDayOfUnleavenedBread,
        default => ($this->hebrewDay > 15 && $this->hebrewDay < 21)
          ? FeastDayType::RegularDayOfUnleavenedBread : FeastDayType::None,
      },
      // Pentecost is less tricky than it seems: it always falls on the 5th, 7th, 8th or 10th of
      // Sivan (which spans a six day period, so there are no weekday duplications), and always
      // falls on a Sunday.
      HebrewMonth::Sivan => ($this->dayOfWeek === Weekday::Sunday && match($this->hebrewDay) { 5, 7, 8, 10 => TRUE, default => FALSE})
        ? FeastDayType::Pentecost : FeastDayType::None,
      HebrewMonth::Tishrei => match($this->hebrewDay) {
        1 => FeastDayType::Trumpets,
        10 => FeastDayType::Atonement,
        15 => FeastDayType::FirstDayOfTabernacles,
        22 => FeastDayType::EighthDay,
        default => ($this->hebrewDay > 15 && $this->hebrewDay < 22)
          ? FeastDayType::RegularDayOfTabernacles : FeastDayType::None,
      },
      default => FeastDayType::None,
    };
  }

}
