<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

/**
 * Represents a single calendar week, including information on the Holy Days therein.
 */
class CalendarWeek {

  /**
   * Week number from creation, with 1 being the first week.
   */
  public readonly int $sabbathIdFromCreation;

  /**
   * @var callable(\Drupal\hebrew_calendar_generator\Weekday $dayOfWeek) : \Drupal\hebrew_calendar_generator\CalendarDay
   */
  private $dayGenerator;

  /**
   * @param int $sabbathIdFromCreation
   *   The number of the week since creation, starting from 1.
   * @param callable(\Drupal\hebrew_calendar_generator\Weekday $dayOfWeek) : \Drupal\hebrew_calendar_generator\CalendarDay $dayGenerator
   *   Produces a day of this week, as provided in the argument to the function.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $sabbathIdFromCreation is negative.
   */
  public function __construct(int $sabbathIdFromCreation, callable $dayGenerator) {
    if ($sabbathIdFromCreation < 0) {
      throw new \InvalidArgumentException('$sabbathIdFromCreation cannot be negative.');
    }

    $this->sabbathIdFromCreation = $sabbathIdFromCreation;
    $this->dayGenerator = $dayGenerator;
  }

  /**
   * Gets the day of this week for the given weekday.
   */
  public function getDay(Weekday $dayOfWeek) : CalendarDay {
    return ($this->dayGenerator)($dayOfWeek);
  }

  /**
   * Enumerates the days of this week.
   *
   * Usage:
   * ```
   * foreach ($calendarWeek->enumerateDays() as $day) {
   *   // Do something...
   * }
   * ```
   * 
   * @return iterable<\Drupal\hebrew_calendar_generator\CalendarDay>
   *   Object with which one can iterate through the days. This object can only be iterated over
   *   once.
   */
  public function enumerateDays() : iterable {
    for ($i = 1; $i <= 7; $i++) {
      yield $this->getDay(Weekday::from($i));
    }
  }

}
