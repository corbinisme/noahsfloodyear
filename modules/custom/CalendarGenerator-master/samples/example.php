<?php

declare(strict_types = 1);

use Drupal\hebrew_calendar_generator\CalendarYearGenerator;

// You can inject this service or pull it directly from the service container (after installing the
// module :)).
$generator = \Drupal::service('hebrew_calendar_generator.generator');
assert($generator instanceof CalendarYearGenerator);

// Before using the generator, you have to load data from the original "chart3new" table.
// You only need to do this once, unless the data in the table changes.
$generator->prepareYears();

// Now you can get an A.M. (what I call "Hebrew") year:
$year = $generator->createYear(2);

// You can then convert it to JSON, if you like:
$yearJson = $year->toJson(TRUE);

// You can also directly access the various public methods and properties on the year object: take a
// look in the class to these methods and their documentation. It might be helpful to install PHP
// Intelephense (and to disable the default PHP auto-suggest) so you can browse these methods more
// easily, and so the documentation pops up for you automatically.

// You can also loop through the weeks in the (extended) year:
foreach ($year->enumerateWeeks() as $week) {
  echo 'Sabbath number since creation: ' . $week->sabbathIdFromCreation . "\n";
  // And you can loop through the days!
  foreach ($week->enumerateDays() as $day) {
    // You can access the public properties and method of $day, e.g.:
    echo 'Hebrew year: ' . $day->hebrewYear . "\n";
    echo 'Hebrew month: ' . $day->hebrewMonth->toString() . "\n";
    echo 'Hebrew day: ' . $day->hebrewDay . "\n";
    echo 'Feast day type: ' . $day->getFeastDayType()->toString() . "\n";
  }
}

// A few notes regarding what to use for the values in the "questions" and "answers" table on the
// calendar pages -- to get these values, you can access various properties/methods on any "year"
// object:

// What 247 year period from creation? A:
echo $year->cycleId247Year; // --or--
echo $yearJson['247-year-cycle-number'];
// Which 19 year time cycle of the 13 in the 247 year period? A:
echo $year->cycleId19Year; // --or--
echo $yearJson['19-year-cycle-number'];
// Which year in the 19 year cycle? A:
echo $year->yearIn19YearCycle; // --or--
echo $yearJson['year-in-19-year-cycle'];
// Number of Hebrew Calendar days? A:
echo $year->hebrewYearDays; // --or--
echo $yearJson['days-in-hebrew-year'];
// Number of solar calendar days? A:
echo $year->solarYearDays; // --or--
echo $yearJson['days-in-solar-year'];
// Last year's difference? A: (NULL if there was no previous year , which is different than the old calendar behavior)
echo $year->diffBetweenSolarAndHebrewDayPreviousYear; // --or--
echo $yearJson['diff-between-solar-and-hebrew-day-previous-year'];
// Difference between solar and Hebrew calendars? A:
echo $year->diffBetweenSolarAndHebrewDay; // --or--
echo $yearJson['diff-between-solar-and-hebrew-day'];
// Difference between last year and present year? A: (0 if there was no previous year, which is different than the old calendar behavior)
echo $year->getDifferenceOfSolarHebrewOffsets(); // --or--
echo $yearJson['diff-of-solar-hebrew-offsets'];
// (Note that the answers to the last two questions appeared to get incorrectly swapped on the
// old calendar pages.)
