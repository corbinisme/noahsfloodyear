<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

/**
 * Represents a Gregorian month.
 */
enum GregorianMonth : int {

  case January = 1;
  case February = 2;
  case March = 3;
  case April = 4;
  case May = 5;
  case June = 6;
  case July = 7;
  case August = 8;
  case September = 9;
  case October = 10;
  case November = 11;
  case December = 12;

  /**
   * Gets the numeric value (1-12) of the given month.
   */
  public function toInt(): int {
    return $this->value;
  }

  public function toString() : string {
    return $this->name;
  }

}
