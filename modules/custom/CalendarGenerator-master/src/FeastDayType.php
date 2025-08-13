<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

/**
 * The type of a Feast day, or "None" to indicate that the day is not a Feast day.
 */
enum FeastDayType {

  case None;
  case Passover;
  case FirstDayOfUnleavenedBread;
  case RegularDayOfUnleavenedBread;
  case LastDayOfUnleavenedBread;
  case Pentecost;
  case Trumpets;
  case Atonement;
  case FirstDayOfTabernacles;
  case RegularDayOfTabernacles;
  case EighthDay;

  public function toString() : string {
    return match($this) {
      self::None => 'None',
      self::Passover => 'Passover',
      self::FirstDayOfUnleavenedBread => 'First Day of Unleavened Bread',
      self::RegularDayOfUnleavenedBread => 'Regular Day of Unleavened Bread',
      self::LastDayOfUnleavenedBread => 'Last Day of Unleavened Bread',
      self::Pentecost => 'Pentecost',
      self::Trumpets => 'Trumpets',
      self::Atonement => 'Atonement',
      self::FirstDayOfTabernacles => 'First Day of Tabernacles',
      self::RegularDayOfTabernacles => 'Regular Day of Tabernacles',
      self::EighthDay => 'Eighth Day',
    };
  }

}
