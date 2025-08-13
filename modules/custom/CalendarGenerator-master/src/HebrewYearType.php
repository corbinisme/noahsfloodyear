<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

/**
 * The type of the Hebrew year (depends on the number of days in the year).
 */
enum HebrewYearType {
  case Regular;
  case Deficient;
  case Complete;
}
