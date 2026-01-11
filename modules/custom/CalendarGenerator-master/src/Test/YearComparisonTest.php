<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator\Test;

use Drupal\hebrew_calendar_generator\CalendarYearGenerator;
use Drupal\hebrew_calendar_generator\FeastDayType;
use Drupal\hebrew_calendar_generator\GregorianMonth;
use Drupal\hebrew_calendar_generator\HebrewMonth;
use Drupal\hebrew_calendar_generator\Weekday;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests whether the calendar generator output matches expected values.
 */
class YearComparisonTest {

  private readonly CalendarYearGenerator $generator;
  private readonly OutputInterface $output;

  /**
   * @param \Drupal\hebrew_calendar_generator\CalendarYearGenerator $generator
   *   Generator that has already been prepared (prepareYears() should have been called at some
   *   point).
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Used to record test failures. Unlike a normal test, the test does not stop on a single
   *   failure; it is simply logged and the test continues.
   */
  public function __construct(CalendarYearGenerator $generator, OutputInterface $output) {
    $this->generator = $generator;
    $this->output = $output;
  }

  /**
   * Attempts to test every year, from 1 to 6090 A.M.
   *
   * Contingent upon having comparison data for every year. The data files are assumed to be at
   * ../../data/extracted-calendar-data-[year].json by default, but the prefix (up to the [year])
   * part can optionally be overridden.
   *
   * @throws \RuntimeException
   *   Thrown if data files cannot be located, read or converted to JSON, or if they are missing
   *   data.
   * @throws \Drupal\hebrew_calendar_generator\Exception\CorruptDatabaseTableException
   *   Thrown if the calendar prepared data is corrupt.
   * @throws \Drupal\hebrew_calendar_generator\Exception\NoDataForYearException
   *   Thrown if there is no calendar prepared data for the given Hebrew year. This may be because
   *   there was not enough source data, or because prepareYears() was never called.
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Thrown if a database error occurs.
   */
  public function testAllYears(string $dataPrefix = __DIR__ . '/../../data/extracted-calendar-data-') : void {
    for ($year = 1; $year <= 6090; $year++) {
      $dataFile = $dataPrefix . $year . '.json';
      $dataContents = file_get_contents($dataFile);
      if (!$dataContents) {
        throw new \RuntimeException('Data file for year ' . $year . ' could not be read or was empty.');
      }
      $comparisonData = json_decode($dataContents, TRUE);
      if (!is_array($comparisonData)) {
        throw new \RuntimeException('Failed to decode JSON data file for year ' . $year . '.');
      }

      $this->testYear($comparisonData, $year);
    }
  }

  /**
   * Tests the given year with the given comparison data.
   *
   * @param array $comparisonData
   *   Comparison data, usually taken from the JSON data generated from the old site.
   * @param int $amYear
   *   A.M. year to test.
   *
   * @throws \RuntimeException
   *   Thrown if $comparisonData is unexpectedly missing something.
   * @throws \InvalidArgumentException
   *   Thrown if $amYear is less than one.
   * @throws \Drupal\hebrew_calendar_generator\Exception\CorruptDatabaseTableException
   *   Thrown if the calendar prepared data is corrupt.
   * @throws \Drupal\hebrew_calendar_generator\Exception\NoDataForYearException
   *   Thrown if there is no calendar prepared data for the given Hebrew year. This may be because
   *   there was not enough source data, or because prepareYears() was never called.
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Thrown if a database error occurs.
   */
  public function testYear(array $comparisonData, int $amYear) : void {
    if ($amYear < 1) {
      throw new \InvalidArgumentException('$amYear is less than one.');
    }

    $year = $this->generator->createYear($amYear);
    // Throughout this method, we additionally cross-check our JSON output with that produced by
    // directly accessing methods and properties on $year.
    $yearJson = json_decode($year->toJson(TRUE), TRUE);
    if (!is_array($yearJson)) {
      $this->recordTestFailure('Failed to decode JSON output for year ' . $amYear . '.');
      return;
    }

    // Step 1: Check to ensure all the question answers exist in the original data and seem to be
    // correct.

    self::ensureKeyIsArray($comparisonData, 'answers', 'answers', $amYear);
    $comparisonAnswers = $comparisonData['answers'];

    self::ensureKeyIsset($comparisonAnswers, '247-year-cycle-id', 'answers[247-year-cycle-id]', $amYear);
    self::ensureKeyIsset($comparisonAnswers, '19-year-cycle-id', 'answers[19-year-cycle-id]', $amYear);
    self::ensureKeyIsset($comparisonAnswers, 'year-in-19-year-cycle', 'answers[year-in-19-year-cycle]', $amYear);
    self::ensureKeyIsset($comparisonAnswers, 'hebrew-calendar-days', 'answers[hebrew-calendar-days]', $amYear);
    self::ensureKeyIsset($comparisonAnswers, 'solar-calendar-days', 'answers[solar-calendar-days]', $amYear);
    self::ensureKeyIsset($comparisonAnswers, 'diff-btwn-solar-and-hebrew', 'answers[diff-btwn-solar-and-hebrew]', $amYear);
    self::ensureKeyIsset($comparisonAnswers, 'diff-btwn-last-and-present', 'answers[diff-btwn-last-and-present]', $amYear);
    self::ensureKeyIsset($comparisonAnswers, 'last-year-diff', 'answers[last-year-diff]', $amYear);

    // Check the question answers.

    $this->assertEqual($year->cycleId247Year, $yearJson['247-year-cycle-number'], '247-year-cycle-number JSON', $amYear);
    $this->assertEqual($year->cycleId19Year, $yearJson['19-year-cycle-number'], '19-year-cycle-number JSON', $amYear);
    $this->assertEqual($year->yearIn19YearCycle, $yearJson['year-in-19-year-cycle'], 'Year in 19-year-cycle JSON', $amYear);
    $this->assertEqual($year->hebrewYearDays, $yearJson['days-in-hebrew-year'], 'Days in Hebrew year JSON', $amYear);
    $this->assertEqual($year->solarYearDays, $yearJson['days-in-solar-year'], 'Days in solar year JSON', $amYear);
    $this->assertEqual($year->diffBetweenSolarAndHebrewDayPreviousYear, $yearJson['diff-between-solar-and-hebrew-day-previous-year'], 'Solar-Hebrew previous year diff JSON', $amYear);
    $this->assertEqual($year->diffBetweenSolarAndHebrewDay, $yearJson['diff-between-solar-and-hebrew-day'], 'Solar-Hebrew year diff JSON', $amYear);
    $this->assertEqual($year->getDifferenceOfSolarHebrewOffsets(), $yearJson['diff-of-solar-hebrew-offsets'], 'Diff of solar-Hebrew offsets JSON', $amYear);

    $this->assertEqual($comparisonAnswers['247-year-cycle-id'], $year->cycleId247Year, '247-year-cycle-number', $amYear);
    $this->assertEqual($comparisonAnswers['19-year-cycle-id'], $year->cycleId19Year, '19-year-cycle-number', $amYear);
    $this->assertEqual($comparisonAnswers['year-in-19-year-cycle'], $year->yearIn19YearCycle, 'Year in 19-year-cycle', $amYear);
    $this->assertEqual($comparisonAnswers['hebrew-calendar-days'], $year->hebrewYearDays, 'Days in Hebrew year', $amYear);
    $this->assertEqual($comparisonAnswers['solar-calendar-days'], $year->solarYearDays, 'Days in solar year', $amYear);
    $this->assertEqual($comparisonAnswers['last-year-diff'], $year->diffBetweenSolarAndHebrewDayPreviousYear, 'Solar-Hebrew previous year diff', $amYear);
    // Note that the last two answers get swapped in the extracted data, hence our correction...
    $this->assertEqual($comparisonAnswers['diff-btwn-solar-and-hebrew'], $year->getDifferenceOfSolarHebrewOffsets(), 'Solar-Hebrew year diff', $amYear);
    $this->assertEqual($comparisonAnswers['diff-btwn-last-and-present'], $year->diffBetweenSolarAndHebrewDay, 'Diff of solar-Hebrew offsets', $amYear);

    // Step 2: Loop through all days, extract the holy days (and make sure they are not improperly
    // duplicated, etc.), and make comparisons of each day with the JSON data.
    $passoverDay = 0;
    $passoverMonth = 0;
    $unleavenedStartDay = 0;
    $unleavenedStartMonth = 0;
    $unleavenedEndDay = 0;
    $unleavenedEndMonth = 0;
    $pentecostDay = 0;
    $pentecostMonth = 0;
    $trumpetsDay = 0;
    $trumpetsMonth = 0;
    $atonementDay = 0;
    $atonementMonth = 0;
    $tabernaclesStartDay = 0;
    $tabernaclesStartMonth = 0;
    $tabernaclesEndDay = 0;
    $tabernaclesEndMonth = 0;
    $eightDayDay = 0;
    $eightDayMonth = 0;
    $weekId = 0;
    foreach ($year->enumerateWeeks() as $week) {
      $weekJson = $yearJson['weeks'][$weekId];
      $this->assertEqual($week->sabbathIdFromCreation, $weekJson['sabbath-number-from-creation'], 'Week sabbath ID from creation', $amYear);
      $dayId = 0;
      foreach ($week->enumerateDays() as $day) {
        $dayJson = $weekJson['days'][$day->dayOfWeek->toString()];

        $this->assertEqualForDay($day->gregorianYear, $dayJson['gregorian-year'], 'Gregorian year', $amYear, $weekId, $dayId);
        $gregorianMonth = $this->assertIsGregorianMonthAndGet($dayJson['gregorian-month'], $amYear, $weekId, $dayId);
        $this->assertEqualForDay($day->gregorianMonth, $gregorianMonth, 'Gregorian month', $amYear, $weekId, $dayId);
        $this->assertEqualForDay($day->gregorianDay, $dayJson['gregorian-day'], 'Gregorian day', $amYear, $weekId, $dayId);

        $this->assertEqualForDay($day->solarYear, $dayJson['solar-year'], 'Solar year', $amYear, $weekId, $dayId);
        $this->assertEqualForDay($day->solarDay, $dayJson['solar-day'], 'Solar day', $amYear, $weekId, $dayId);

        $this->assertEqualForDay($day->hebrewYear, $dayJson['hebrew-year'], 'Hebrew year', $amYear, $weekId, $dayId);
        $hebrewMonth = $this->assertIsHebrewMonthAndGet($dayJson['hebrew-month'], $amYear, $weekId, $dayId);
        $this->assertEqualForDay($day->hebrewMonth, $hebrewMonth, 'Hebrew month', $amYear, $weekId, $dayId);
        $this->assertEqualForDay($day->hebrewDay, $dayJson['hebrew-day'], 'Hebrew day', $amYear, $weekId, $dayId);

        $feastDayType = $this->assertIsFeastDayTypeAndGet($dayJson['feast-day-type'], $amYear, $weekId, $dayId);
        $this->assertEqualForDay($day->getFeastDayType(), $feastDayType, 'Feast day type', $amYear, $weekId, $dayId);

        switch ($feastDayType) {
          case FeastDayType::Passover:
            $this->assertEqual(0, $passoverDay, 'Duplicate Passover', $amYear);
            $passoverDay = $day->gregorianDay;
            $passoverMonth = $day->gregorianMonth->toInt();
            break;
          case FeastDayType::FirstDayOfUnleavenedBread:
            $this->assertEqual(0, $unleavenedStartDay, 'Duplicate First Day of Unleavened Bread', $amYear);
            $unleavenedStartDay = $day->gregorianDay;
            $unleavenedStartMonth = $day->gregorianMonth->toInt();
            break;
          case FeastDayType::RegularDayOfUnleavenedBread:
            $this->assertNotEqual(0, $unleavenedStartDay, 'Unexpected regular Day of Unleavened Bread', $amYear);
            $this->assertEqual(0, $unleavenedEndDay, 'Unexpected regular Day of Unleavened Bread end', $amYear);
            break;
          case FeastDayType::LastDayOfUnleavenedBread:
            $this->assertEqual(0, $unleavenedEndDay, 'Duplicate Last Day of Unleavened Bread', $amYear);
            $unleavenedEndDay = $day->gregorianDay;
            $unleavenedEndMonth = $day->gregorianMonth->toInt();
            break;
          case FeastDayType::Pentecost:
            $this->assertEqual(0, $pentecostDay, 'Duplicate Pentecost', $amYear);
            $pentecostDay = $day->gregorianDay;
            $pentecostMonth = $day->gregorianMonth->toInt();
            break;
          case FeastDayType::Trumpets:
            $this->assertEqual(0, $trumpetsDay, 'Duplicate Trumpets', $amYear);
            $trumpetsDay = $day->gregorianDay;
            $trumpetsMonth = $day->gregorianMonth->toInt();
            break;
          case FeastDayType::Atonement:
            $this->assertEqual(0, $atonementDay, 'Duplicate Atonement', $amYear);
            $atonementDay = $day->gregorianDay;
            $atonementMonth = $day->gregorianMonth->toInt();
            break;
          case FeastDayType::FirstDayOfTabernacles:
            $this->assertEqual(0, $tabernaclesStartDay, 'Duplicate First Day of Tabernacles', $amYear);
            $tabernaclesStartDay = $day->gregorianDay;
            $tabernaclesStartMonth = $day->gregorianMonth->toInt();
            $tabernaclesEndDay = $tabernaclesStartDay;
            $tabernaclesEndMonth = $tabernaclesStartMonth;
            break;
          case FeastDayType::RegularDayOfTabernacles:
            $this->assertNotEqual(0, $tabernaclesStartDay, 'Unexpected regular Day of Tabernacles', $amYear);
            if ($tabernaclesEndMonth == $day->gregorianMonth->toInt()) {
              $this->assertEqual($tabernaclesEndDay + 1, $day->gregorianDay, 'Non-consecutive regular Day of Tabernacles', $amYear);
            }
            else {
              $this->assertEqual($tabernaclesEndMonth + 1, $day->gregorianMonth->toInt(), 'Non-consecutive regular Day of Tabernacles across month boundary', $amYear);
              $this->assertEqual(1, $day->gregorianDay, 'Non-consecutive regular Day of Tabernacles across month boundary day', $amYear);
            }
            $tabernaclesEndDay = $day->gregorianDay;
            $tabernaclesEndMonth = $day->gregorianMonth->toInt();
            break;
          case FeastDayType::EighthDay:
            $this->assertEqual(0, $eightDayDay, 'Duplicate Eighth Day of Tabernacles', $amYear);
            $eightDayDay = $day->gregorianDay;
            $eightDayMonth = $day->gregorianMonth->toInt();
            break;
        }

        $dayId++;
      }

      $weekId++;
    }

    // Step 3: Compare Holy Day dates with extacted values.

    self::ensureKeyIsset($comparisonData, 'feast_days', 'feast_days', $amYear);
    $feastDaysData = $comparisonData['feast_days'];
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'passover', 'feast_days[passover]', $amYear);
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'doub', 'feast_days[doub]', $amYear);
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'doub-end', 'feast_days[doub-end]', $amYear);
    // Pentecost isn't always present...
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'trumpets', 'feast_days[trumpets]', $amYear);
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'atonement', 'feast_days[atonement]', $amYear);
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'tabernacles', 'feast_days[tabernacles]', $amYear);
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'tabernacles-end', 'feast_days[tabernacles-end]', $amYear);
    self::ensureFeastDayPresentInComparisonData($feastDaysData, 'eighth-day', 'feast_days[eighth-day]', $amYear);

    $this->assertEqual($feastDaysData['passover']['day'], $passoverDay, 'Passover day', $amYear);
    $this->assertEqual($feastDaysData['passover']['month'], $passoverMonth, 'Passover month', $amYear);
    $this->assertEqual($feastDaysData['doub']['day'], $unleavenedStartDay, 'First Day of Unleavened Bread day', $amYear);
    $this->assertEqual($feastDaysData['doub']['month'], $unleavenedStartMonth, 'First Day of Unleavened Bread month', $amYear);
    $this->assertEqual($feastDaysData['doub-end']['day'], $unleavenedEndDay, 'Last Day of Unleavened Bread day', $amYear);
    $this->assertEqual($feastDaysData['doub-end']['month'], $unleavenedEndMonth, 'Last Day of Unleavened Bread month', $amYear);
    if (isset($feastDaysData['pentecost']['day']) && isset($feastDaysData['pentecost']['month'])) {
      $this->assertEqual($feastDaysData['pentecost']['day'], $pentecostDay, 'Pentecost day', $amYear);
      $this->assertEqual($feastDaysData['pentecost']['month'], $pentecostMonth, 'Pentecost month', $amYear);
    }
    $this->assertEqual($feastDaysData['trumpets']['day'], $trumpetsDay, 'Trumpets day', $amYear);
    $this->assertEqual($feastDaysData['trumpets']['month'], $trumpetsMonth, 'Trumpets month', $amYear);
    $this->assertEqual($feastDaysData['atonement']['day'], $atonementDay, 'Atonement day', $amYear);
    $this->assertEqual($feastDaysData['atonement']['month'], $atonementMonth, 'Atonement month', $amYear);
    $this->assertEqual($feastDaysData['tabernacles']['day'], $tabernaclesStartDay, 'First Day of Tabernacles day', $amYear);
    $this->assertEqual($feastDaysData['tabernacles']['month'], $tabernaclesStartMonth, 'First Day of Tabernacles month', $amYear);
    $this->assertEqual($feastDaysData['tabernacles-end']['day'], $tabernaclesEndDay, 'Last Day of Tabernacles day', $amYear);
    $this->assertEqual($feastDaysData['tabernacles-end']['month'], $tabernaclesEndMonth, 'Last Day of Tabernacles month', $amYear);
    $this->assertEqual($feastDaysData['eighth-day']['day'], $eightDayDay, 'Eighth Day day', $amYear);
    $this->assertEqual($feastDaysData['eighth-day']['month'], $eightDayMonth, 'Eighth Day month', $amYear);

    // Step 4: Loop through weeks and ensure the week-associated data matches the extracted data.
    // The starting week of each year should match in both data sets, but the last week may not be
    // the same, and that should not result in a test failure. Hence, we only test while there is
    // data in both datasets.
    $weekId = 0;
    foreach ($year->enumerateWeeks() as $week) {
      if (!isset($comparisonData['weeks'][$weekId])) {
        // We have exhausted the weeks in the comparison data.
        break;
      }
      self::ensureKeyIsArray($comparisonData['weeks'], $weekId, 'weeks[' . $weekId . ']', $amYear);
      $weekData = $comparisonData['weeks'][$weekId];

      // Sometimes the Sabbath count from creation doesn't exist in the comparison data...
      if (isset($weekData['sabbath-number-from-creation'])) {
        $this->assertEqual($weekData['sabbath-number-from-creation'], $week->sabbathIdFromCreation, 'Week ' . $weekId . ' sabbath ID from creation', $amYear);
      }

      self::ensureKeyIsset($weekData, 'gregorian_month', 'weeks[' . $weekId . '][gregorian_month]', $amYear);
      self::ensureKeyIsset($weekData, 'gregorian_day', 'weeks[' . $weekId . '][gregorian_day]', $amYear);
      self::ensureKeyIsset($weekData, 'hebrew_month', 'weeks[' . $weekId . '][hebrew_month]', $amYear);
      self::ensureKeyIsset($weekData, 'hebrew_day', 'weeks[' . $weekId . '][hebrew_day]', $amYear);
      self::ensureKeyIsset($weekData, 'solar_year', 'weeks[' . $weekId . '][solar_year]', $amYear);
      self::ensureKeyIsset($weekData, 'solar_day', 'weeks[' . $weekId . '][solar_day]', $amYear);

      $sabbathDay = $week->getDay(Weekday::Saturday);
      $this->assertEqual($weekData['gregorian_month'], $sabbathDay->gregorianMonth->toInt(), 'Week ' . $weekId . ' sabbath Gregorian month', $amYear);
      $this->assertEqual($weekData['gregorian_day'], $sabbathDay->gregorianDay, 'Week ' . $weekId . ' sabbath Gregorian day', $amYear);
      $this->assertEqual($weekData['hebrew_month'], $sabbathDay->hebrewMonth->toInt(), 'Week ' . $weekId . ' sabbath Hebrew month', $amYear);
      $this->assertEqual($weekData['hebrew_day'], $sabbathDay->hebrewDay, 'Week ' . $weekId . ' sabbath Hebrew day', $amYear);
      $this->assertEqual($weekData['solar_year'], $sabbathDay->solarYear, 'Week ' . $weekId . ' sabbath Solar year', $amYear);
      $this->assertEqual($weekData['solar_day'], $sabbathDay->solarDay, 'Week ' . $weekId . ' sabbath Solar day', $amYear);

      $weekId++;
    }
  }

  private function assertIsFeastDayTypeAndGet(string $feastDayTypeName, int $amYear, int $weekId, int $dayId) : FeastDayType {
    return match($feastDayTypeName) {
      'None' => FeastDayType::None,
      'Passover' => FeastDayType::Passover,
      'First Day of Unleavened Bread' => FeastDayType::FirstDayOfUnleavenedBread,
      'Regular Day of Unleavened Bread' => FeastDayType::RegularDayOfUnleavenedBread,
      'Last Day of Unleavened Bread' => FeastDayType::LastDayOfUnleavenedBread,
      'Pentecost' => FeastDayType::Pentecost,
      'Trumpets' => FeastDayType::Trumpets,
      'Atonement' => FeastDayType::Atonement,
      'First Day of Tabernacles' => FeastDayType::FirstDayOfTabernacles,
      'Regular Day of Tabernacles' => FeastDayType::RegularDayOfTabernacles,
      'Eighth Day' => FeastDayType::EighthDay,
      default => $this->recordTestFailure('Invalid Feast day type name "'
        . $feastDayTypeName
        . '" for '
        . self::getYearMonthDayForAssertionFailure($amYear, $weekId, $dayId)
        . '.'),
    };
  }

  private function assertIsGregorianMonthAndGet(string $monthName, int $amYear, int $weekId, int $dayId) : GregorianMonth {
    return match($monthName) {
      'January' => GregorianMonth::January,
      'February' => GregorianMonth::February,
      'March' => GregorianMonth::March,
      'April' => GregorianMonth::April,
      'May' => GregorianMonth::May,
      'June' => GregorianMonth::June,
      'July' => GregorianMonth::July,
      'August' => GregorianMonth::August,
      'September' => GregorianMonth::September,
      'October' => GregorianMonth::October,
      'November' => GregorianMonth::November,
      'December' => GregorianMonth::December,
      default => $this->recordTestFailure('Invalid Gregorian month name "'
        . $monthName
        . '" for '
        . self::getYearMonthDayForAssertionFailure($amYear, $weekId, $dayId)
        . '.'),
    };
  }

  private function assertIsHebrewMonthAndGet(string $monthName, int $amYear, int $weekId, int $dayId) : HebrewMonth {
    return match($monthName) {
      'Nisan' => HebrewMonth::Nisan,
      'Iyar' => HebrewMonth::Iyar,
      'Sivan' => HebrewMonth::Sivan,
      'Tammuz' => HebrewMonth::Tammuz,
      'Av' => HebrewMonth::Av,
      'Elul' => HebrewMonth::Elul,
      'Tishrei' => HebrewMonth::Tishrei,
      'Cheshvan' => HebrewMonth::Cheshvan,
      'Kislev' => HebrewMonth::Kislev,
      'Tevet' => HebrewMonth::Tevet,
      'Shevat' => HebrewMonth::Shevat,
      'Adar' => HebrewMonth::Adar,
      'Adar I' => HebrewMonth::AdarI,
      'Adar II' => HebrewMonth::AdarII,
      default => $this->recordTestFailure('Invalid Hebrew month name "'
        . $monthName
        . '" for '
        . self::getYearMonthDayForAssertionFailure($amYear, $weekId, $dayId)
        . '.'),
    };
  }

  private function assertEqualForDay(mixed $expected, mixed $actual, string $assertionName, int $year, int $weekId, int $dayId) : void {
    if (!($expected == $actual)) {
      $this->recordTestFailure($assertionName
        . ' assertion failed for '
        . self::getYearMonthDayForAssertionFailure($year, $weekId, $dayId)
        . ': Expected '
        . $expected
        . ', got '
        . $actual
        . '.');
    }
  }

  private function assertEqual(mixed $expected, mixed $actual, string $assertionName, int $year) : void {
    if ($expected != $actual) {
      $this->recordTestFailure($assertionName . ' assertion failed for year ' . $year . ': Expected ' . $expected . ', got ' . $actual . '.');
    }
  }

  private function assertNotEqual(mixed $notExpected, mixed $actual, string $assertionName, int $year) : void {
    if (!($notExpected != $actual)) {
      $this->recordTestFailure($assertionName . ' assertion failed for year ' . $year . ': Did not expect ' . $notExpected . ', but got it anyway.');
    }
  }

  private function recordTestFailure(string $message) : void {
    $this->output->writeln('Failure: ' . $message);
  }

  private static function getYearMonthDayForAssertionFailure(int $year, int $weekId, int $dayId) : string {
    return 'year ' . $year . ', week ' . ($weekId + 1) . ', day ' . ($dayId + 1);
  }

  private static function ensureFeastDayPresentInComparisonData(array $data, string|int $feastDayKey, string $keyName, int $year) : void {
    self::ensureKeyIsset($data, $feastDayKey, $keyName, $year);
    $feastDayData = $data[$feastDayKey];
    self::ensureKeyIsset($feastDayData, 'day', $keyName . '[day]', $year);
    self::ensureKeyIsset($feastDayData, 'month', $keyName . '[month]', $year);
  }

  private static function ensureKeyIsArray(array $data, string|int $key, string $keyName, int $year) : void {
    if (!is_array($data[$key])) {
      throw new \RuntimeException('Expected key "' . $keyName . '" to be an array in comparison data for year ' . $year . '.');
    }
  }

  private static function ensureKeyIsset(array $data, string|int $key, string $keyName, int $year) : void {
    if (!isset($data[$key])) {
      throw new \RuntimeException('Expected key "' . $keyName . '" to be set in comparison data for year ' . $year . '.');
    }
  }

}
