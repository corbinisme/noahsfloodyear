<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

/**
 * Day of the week.
 */
enum Weekday : int {

  case Sunday = 1;
  case Monday = 2;
  case Tuesday = 3;
  case Wednesday = 4;
  case Thursday = 5;
  case Friday = 6;
  case Saturday = 7;

  /**
   * Get the ordinal value of the weekday (1-7).
   */
  public function toInt() : int {
    return $this->value;
  }

  public function toString() : string {
    return $this->name;
  }

}
