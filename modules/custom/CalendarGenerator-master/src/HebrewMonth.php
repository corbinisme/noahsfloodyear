<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

/**
 * Represents a Hebrew month.
 */
enum HebrewMonth : int {

  case Nisan = 1;
  case Iyar = 2;
  case Sivan = 3;
  case Tammuz = 4;
  case Av = 5;
  case Elul = 6;
  case Tishrei = 7;
  case Cheshvan = 8;
  case Kislev = 9;
  case Tevet = 10;
  case Shevat = 11;
  case AdarI = 12;
  case AdarII = 13;
  case Adar = 14;

  /**
   * Gets the number of days in this month.
   *
   * @param \Drupal\hebrew_calendar_generator\HebrewYearType $yearType
   *   Type of Hebrew year containing month.
   * @param bool $isLeapYear
   *   Whether the Hebrew year containing the month is a leap year.
   */
  public function getNumberOfDays(HebrewYearType $yearType, bool $isLeapYear) : int {
    return match ($this) {
      HebrewMonth::Cheshvan => match($yearType) {
        HebrewYearType::Regular, HebrewYearType::Deficient => 29,
        HebrewYearType::Complete => 30,
      },
      HebrewMonth::Kislev => match($yearType) {
        HebrewYearType::Deficient => 29,
        HebrewYearType::Regular, HebrewYearType::Complete => 30,
      },
      HebrewMonth::AdarI => $isLeapYear ? 30 : 29,
      HebrewMonth::Nisan, HebrewMonth::Sivan, HebrewMonth::Av, HebrewMonth::Tishrei, HebrewMonth::Shevat => 30,
      default => 29,
    };
  }

  /**
   * Converts the month to its ordinal value (1-13).
   */
  public function toInt() : int {
    return $this === HebrewMonth::Adar ? 12 : $this->value;
  }

  public function toString() : string {
    return match($this) {
      HebrewMonth::AdarI => 'Adar I',
      HebrewMonth::AdarII => 'Adar II',
      default => $this->name,
    };
  }

  public static function fromInt(int $month, bool $isLeapYear) : HebrewMonth {
    return match($month) {
      1 => HebrewMonth::Nisan,
      2 => HebrewMonth::Iyar,
      3 => HebrewMonth::Sivan,
      4 => HebrewMonth::Tammuz,
      5 => HebrewMonth::Av,
      6 => HebrewMonth::Elul,
      7 => HebrewMonth::Tishrei,
      8 => HebrewMonth::Cheshvan,
      9 => HebrewMonth::Kislev,
      10 => HebrewMonth::Tevet,
      11 => HebrewMonth::Shevat,
      12 => $isLeapYear ? HebrewMonth::AdarI : HebrewMonth::Adar,
      13 => $isLeapYear ? HebrewMonth::AdarII : throw new \InvalidArgumentException('$month 13 is not valid in a non-leap year'),
      default => throw new \InvalidArgumentException('Invalid ' . $month . ' number.'),
    };
  }

}
