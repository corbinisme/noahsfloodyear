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
