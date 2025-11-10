<?php

declare(strict_types = 1);

namespace Drupal\hebrew_calendar_generator;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\hebrew_calendar_generator\Exception\CorruptDatabaseTableException;
use Drupal\hebrew_calendar_generator\Exception\NoDataForYearException;
use Drupal\hebrew_calendar_generator\Helper\DateHelpers;

/**
 * Used to generate calendar year objects.
 *
 * Before generating a calendar year with createYear(), you must call prepareYears() to assemble the
 * data used to generate the years. You do not need to repeat this call unless 1) your Drupal
 * database becomes corrupted, or 2) the source data changes.
 */
class CalendarYearGenerator {

  /**
   * @var string
   */
  private const ASSEMBLED_YEAR_DATA_TABLE = 'hebrew_calendar_generator_assembled_years';

  /**
   * @var string
   */
  private const YEAR_OBJECT_CACHE_KEY = 'calendar_year_generator_year_object_cache';

  /**
   * Max number of years to store in the cache.
   */
  private const MAXIMUM_YEARS_IN_CACHE = 1000;

  private readonly Connection $mainConnection;

  /**
   * @param \Drupal\Core\Database\Connection $mainConnection
   *   Connection to main Drupal database.
   */
  public function __construct(Connection $mainConnection) {
    $this->mainConnection = $mainConnection;
  }

  /**
   * Gets (and creates, if necessary) a calendar year object.
   *
   * prepareYears() must have been called at some point in the past.
   *
   * @param int $hebrewYear
   *   Hebrew (A.M.) year to get, starting from 1.
   *
   * @throws \InvalidArgumentException
   *   Thrown if $hebrewYear is less than 1.
   * @throws \Drupal\hebrew_calendar_generator\Exception\CorruptDatabaseTableException
   *   Thrown if the prepared data is corrupt.
   * @throws \Drupal\hebrew_calendar_generator\Exception\NoDataForYearException
   *   Thrown if there is no prepared data for the given Hebrew year. This may be because there was
   *   not enough source data, or because prepareYears() was never called.
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Thrown if a database error occurs.
   */
  public function createYear(int $hebrewYear) : CalendarYear {
    if ($hebrewYear < 1) {
      throw new \InvalidArgumentException('$hebrewYear must be greater than zero.');
    }

    $yearCache =& drupal_static(self::YEAR_OBJECT_CACHE_KEY, []);
    assert(is_array($yearCache));
    if (isset($yearCache[$hebrewYear])) {
      assert($yearCache[$hebrewYear] instanceof CalendarYear);
      return $yearCache[$hebrewYear];
    }

    $previousYear = $hebrewYear - 1;
    $havePreviousCachedYear = isset($yearCache[$previousYear]);

    $numDaysInPreviousHebrewYear = NULL;
    $numDaysInPreviousSolarYear = NULL;
    $diffBetweenSolarAndHebrewDayPrevious = NULL;

    // Grab information about the desired year and the previous year, if needed and applicable. We
    // only need a bit of info from the previous year, but it's likely better to make one query than
    // two, even if we are returning a little extra data.
    $query = $this->mainConnection->select(self::ASSEMBLED_YEAR_DATA_TABLE, 't')
      ->fields('t');
    if ($hebrewYear === 1 || $havePreviousCachedYear) {
      $query->condition('hebrew_year', $hebrewYear);
      $fields = $query->execute()->fetchAssoc();
      if (empty($fields)) self::throwNoPreparedDataForYearException($hebrewYear);

      if ($hebrewYear > 1) {
        $previousYearObj = $yearCache[$previousYear];
        assert($previousYearObj instanceof CalendarYear);
        $numDaysInPreviousHebrewYear = $previousYearObj->hebrewYearDays;
        $numDaysInPreviousSolarYear = $previousYearObj->solarYearDays;
        $diffBetweenSolarAndHebrewDayPrevious = $previousYearObj->diffBetweenSolarAndHebrewDay;
      }
    }
    else {
      $query->condition('hebrew_year', [$previousYear, $hebrewYear], 'IN');
      $records = $query->execute()->fetchAllAssoc('hebrew_year', \PDO::FETCH_ASSOC);
      if (!isset($records[$hebrewYear])) self::throwNoPreparedDataForYearException($hebrewYear);
      if (!isset($records[$hebrewYear - 1])) self::throwNoPreparedDataForYearException($hebrewYear - 1);

      $previousFields = $records[$previousYear];
      $numDaysInPreviousHebrewYear = self::getAndValidatePositiveIntFieldPreparedData($previousFields, 'days_in_hebrew_year', $previousYear);
      $numDaysInPreviousSolarYear = self::getAndValidatePositiveIntFieldPreparedData($previousFields, 'days_in_solar_year', $previousYear);
      $diffBetweenSolarAndHebrewDayPrevious = self::getAndValidateIntFieldPreparedData($previousFields, 'diff_between_solar_and_hebrew_day', $previousYear);

      $fields = $records[$hebrewYear];
    }
    assert(is_array($fields));

    $gregorianYear = self::getAndValidateIntFieldPreparedData($fields, 'gregorian_year', $hebrewYear);
    if ($gregorianYear === 0) {
      self::throwInvalidFieldExceptionPreparedData('gregorian_year', $hebrewYear);
    }

    $cycleId19Year = self::getAndValidatePositiveIntFieldPreparedData($fields, 'cycle_id_19_year', $hebrewYear);
    $cycleId247Year = self::getAndValidatePositiveIntFieldPreparedData($fields, 'cycle_id_247_year', $hebrewYear);
    $numDaysInHebrewYear = self::getAndValidatePositiveIntFieldPreparedData($fields, 'days_in_hebrew_year', $hebrewYear);
    if (!DateHelpers::isValidNumberOfDaysInHebrewYear($numDaysInHebrewYear)) {
      self::throwInvalidFieldExceptionPreparedData('days_in_hebrew_year', $hebrewYear);
    }
    $numDaysInSolarYear = self::getAndValidateIntFieldPreparedData($fields, 'days_in_solar_year', $hebrewYear);
    if (!DateHelpers::isValidNumberOfDaysInSolarYear($numDaysInSolarYear)) {
      self::throwInvalidFieldExceptionPreparedData('days_in_solar_year', $hebrewYear);
    }
    $solarYearDaysToFirstGregorianSabbath = self::getAndValidateNullablePositiveIntFieldPreparedData($fields, 'solar_year_days_to_first_gregorian_sabbath', $hebrewYear);
    $diffBetweenSolarAndHebrewDay = self::getAndValidateIntFieldPreparedData($fields, 'diff_between_solar_and_hebrew_day', $hebrewYear);
    $yearIn19YearCycle = self::getAndValidatePositiveIntFieldPreparedData($fields, 'year_in_19_year_cycle', $hebrewYear);
    $weekIdSinceCreationForFirstSabbath = self::getAndValidatePositiveIntFieldPreparedData($fields, 'week_id_since_creation_for_first_sabbath', $hebrewYear);

    $firstHebrewMonthNumber = self::getAndValidateIntFieldPreparedData($fields, 'first_hebrew_month', $hebrewYear);
    $isLeapYear = DateHelpers::isHebrewLeapYear($numDaysInHebrewYear);
    if ($firstHebrewMonthNumber < 1 || $firstHebrewMonthNumber > ($isLeapYear ? 13 : 12)) {
      self::throwInvalidFieldExceptionPreparedData('first_hebrew_month', $hebrewYear);
    }
    $firstHebrewMonth = HebrewMonth::fromInt($firstHebrewMonthNumber, $isLeapYear);

    $firstHebrewDay = self::getAndValidateIntFieldPreparedData($fields, 'first_hebrew_day', $hebrewYear);
    if ($firstHebrewDay < 1 || $firstHebrewDay > 30) {
      self::throwInvalidFieldExceptionPreparedData('first_hebrew_day', $hebrewYear);
    }

    $firstSolarYear = self::getAndValidatePositiveIntFieldPreparedData($fields, 'first_solar_year', $hebrewYear);
    $firstSolarDay = self::getAndValidatePositiveIntFieldPreparedData($fields, 'first_solar_day', $hebrewYear);

    $year = new CalendarYear(
      $hebrewYear,
      $gregorianYear,
      $cycleId19Year,
      $cycleId247Year,
      $numDaysInHebrewYear,
      $numDaysInSolarYear,
      $solarYearDaysToFirstGregorianSabbath,
      $diffBetweenSolarAndHebrewDay,
      $diffBetweenSolarAndHebrewDayPrevious,
      $numDaysInPreviousHebrewYear,
      $numDaysInPreviousSolarYear,
      $yearIn19YearCycle,
      $weekIdSinceCreationForFirstSabbath,
      $firstHebrewMonth,
      $firstHebrewDay,
      $firstSolarYear,
      $firstSolarDay
    );

    // Remove the first item from the cache if it's too big
    if (count($yearCache) >= self::MAXIMUM_YEARS_IN_CACHE) {
      array_shift($yearCache);
    }

    $yearCache[$hebrewYear] = $year;
    return $year;
  }

  /**
   * Loads source data and uses it to populate an internal database table for year generation.
   *
   * @param \Drupal\Core\Database\Connection|null $sourceDataDbConnection
   *   Connection used to access the source data, or NULL to use the default "calendar" connection.
   * @param string $sourceTable
   *   Source table ("chart3new" by default).
   *
   * @throws \InvalidArgumentException
   *   Thrown if $sourceTable is empty.
   * @throws \Drupal\hebrew_calendar_generator\Exception\CorruptDatabaseTableException
   *   Thrown if the source data is corrupt.
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   *   Thrown if a database error occurs.
   * @throws \RuntimeException
   *   Thrown if a query was indicated to be invalid.
   */
  public function prepareYears(?Connection $sourceDataDbConnection = NULL, string $sourceTable = 'chart3new') : void {
    if ($sourceTable === '') {
      throw new \InvalidArgumentException('$sourceTable cannot be an empty string.');
    }
    if ($sourceDataDbConnection === NULL) {
      $sourceDataDbConnection = Database::getConnection('calendar');
    }

    $transaction = NULL;
    try {
      $transaction = $this->mainConnection->startTransaction();

      // Don't TRUNCATE, as that may break transasctional integrity.
      $this->mainConnection->delete(self::ASSEMBLED_YEAR_DATA_TABLE)->execute();

      $result = $sourceDataDbConnection->select($sourceTable, 't')
        ->fields('t', ['amyr', 'days', 'mly', 'd', 'c19', 'c247', 'a2g', 'gyr'])
        ->orderBy('amyr', 'ASC')
        ->execute();
      if (!$result) {
        throw new \RuntimeException('Failed to read data from source table. Query is invalid.');
      }

      $fields = $result->fetchAssoc();
      if (!$fields) {
        throw new CorruptDatabaseTableException('No relevant data found in source table.');
      }

      $lastHebrewYear = NULL;
      $hebrewYear = NULL;
      $numDaysInHebrewYear = NULL;
      $numDaysInSolarYear = NULL;
      $numDaysInPreviousHebrewYear = NULL;
      $numDaysInPreviousSolarYear = NULL;
      $numDaysTwoHebrewYearsBack = NULL;
      $numDaysTwoSolarYearsBack = NULL;
      $yearIn19YearCycle = NULL;
      $firstDayOfYear = NULL;
      $weekIdSinceCreationForFirstSabbath = NULL;

      do {
        $lastHebrewYear = $hebrewYear;
        $hebrewYear = self::getAndValidateIntFieldSourceData($fields, 'amyr', NULL);

        if ($lastHebrewYear === NULL && $hebrewYear !== 1) {
            throw new CorruptDatabaseTableException('Source table Hebrew year does not start at year 1.');
        }
        elseif ($lastHebrewYear !== NULL && $hebrewYear !== ($lastHebrewYear + 1)) {
          throw new CorruptDatabaseTableException('Source table Hebrew year is not sequential at year ' . $hebrewYear . '.');
        }

        $numDaysTwoHebrewYearsBack = $numDaysInPreviousHebrewYear;
        $numDaysTwoSolarYearsBack = $numDaysInPreviousSolarYear;
        $numDaysInPreviousHebrewYear = $numDaysInHebrewYear;
        $numDaysInHebrewYear = self::getAndValidateIntFieldSourceData($fields, 'mly', $hebrewYear);
        if (!DateHelpers::isValidNumberOfDaysInHebrewYear($numDaysInHebrewYear)) {
          self::throwInvalidFieldExceptionSourceData('mly', $hebrewYear);
        }
        $numDaysInPreviousSolarYear = $numDaysInSolarYear;
        $numDaysInSolarYear = self::getAndValidateIntFieldSourceData($fields, 'days', $hebrewYear);
        if (!DateHelpers::isValidNumberOfDaysInSolarYear($numDaysInSolarYear)) {
          self::throwInvalidFieldExceptionSourceData('days', $hebrewYear);
        }

        $diffBetweenSolarAndHebrewDay = self::getAndValidateIntFieldSourceData($fields, 'd', $hebrewYear);
        $cycleId19Year = self::getAndValidatePositiveIntFieldSourceData($fields, 'c19', $hebrewYear);
        $cycleId247Year = self::getAndValidatePositiveIntFieldSourceData($fields, 'c247', $hebrewYear);
        $solarYearDaysToFirstGregorianSabbath = self::getAndValidateNullablePositiveIntFieldSourceData($fields, 'a2g', $hebrewYear);
        $gregorianYear = self::getAndValidateIntFieldSourceData($fields, 'gyr', $hebrewYear);

        if ($lastHebrewYear === NULL) {
          $yearIn19YearCycle = 1;
          $weekIdSinceCreationForFirstSabbath = 1;
          $firstDayOfYear = new CalendarDay(Weekday::Sunday,
            -4046, CalendarYear::getCreationStartGregorianMonth(), CalendarYear::CREATION_START_GREGORIAN_DAY,
            1, HebrewMonth::Nisan, 1,
            1, 1);
        }
        else {
          assert($firstDayOfYear instanceof CalendarDay);

          // We have to calculate three things for the next iteration: the first day of the next
          // year, the week ID since creation for the first Sabbath of the next year, and the year
          // in the 19-year cycle for the next year.

          // Easiest first!
          $yearIn19YearCycle++;
          if ($yearIn19YearCycle > 19) $yearIn19YearCycle = 1;

          // Then the next hardest...
          $startDateGregorianYear = 0;
          $startDateGregorianDay = 0;
          $startDateGregorianMonth = GregorianMonth::January;
          CalendarYear::getStartDateForGregorianYear($gregorianYear,
            $startDateGregorianYear,
            $startDateGregorianMonth,
            $startDateGregorianDay);
          $oldStartDate = \DateTimeImmutable::createFromFormat('x-n-j', ($firstDayOfYear->gregorianYear < 0 ? $firstDayOfYear->gregorianYear + 1 : $firstDayOfYear->gregorianYear) . '-' . $firstDayOfYear->gregorianMonth->toInt() . '-' . $firstDayOfYear->gregorianDay);
          $newStartDate = \DateTimeImmutable::createFromFormat('x-n-j', ($startDateGregorianYear < 0 ? $startDateGregorianYear + 1 : $startDateGregorianYear) . '-' . $startDateGregorianMonth->toInt() . '-' . $startDateGregorianDay);
          $interval = $oldStartDate->diff($newStartDate);
          $weekIdSinceCreationForFirstSabbath += ($interval->days / 7);

          // Then the hardest (well, the hardness is mostly hidden in YearHelpers :)):
          $startDateHebrewYear = 0;
          $startDateHebrewMonth = HebrewMonth::Nisan;
          $startDateHebrewDay = 0;
          if ($firstDayOfYear->hebrewYear === $lastHebrewYear) {
            $numDaysInHebrewYearOfPrevStartDate = $numDaysInPreviousHebrewYear;
            $numDaysInNextHebrewYearAfterPrevStartDate = $numDaysInHebrewYear;
            $numDaysInSolarYearOfPrevStartDate = $numDaysInPreviousSolarYear;
            $numDaysInNextSolarYearAfterPrevStartDate = $numDaysInSolarYear;
          }
          else {
            assert($firstDayOfYear->hebrewYear === ($lastHebrewYear - 1));
            assert(isset($numDaysTwoHebrewYearsBack));
            assert(isset($numDaysTwoSolarYearsBack));
            $numDaysInHebrewYearOfPrevStartDate = $numDaysTwoHebrewYearsBack;
            $numDaysInNextHebrewYearAfterPrevStartDate = $numDaysInPreviousHebrewYear;
            $numDaysInSolarYearOfPrevStartDate = $numDaysTwoSolarYearsBack;
            $numDaysInNextSolarYearAfterPrevStartDate = $numDaysInPreviousSolarYear;
          }
          assert(isset($numDaysInHebrewYearOfPrevStartDate));
          DateHelpers::bumpHebrewDate($interval->days,
            $firstDayOfYear->hebrewYear,
            $firstDayOfYear->hebrewMonth,
            $firstDayOfYear->hebrewDay,
            $startDateHebrewYear,
            $startDateHebrewMonth,
            $startDateHebrewDay,
            $numDaysInHebrewYearOfPrevStartDate,
            $numDaysInNextHebrewYearAfterPrevStartDate);
          $newSolarDay = $firstDayOfYear->solarDay + $interval->days;
          if ($newSolarDay > $numDaysInSolarYearOfPrevStartDate) { 
            $newSolarDay -= $numDaysInPreviousSolarYear;
            if ($newSolarDay > $numDaysInNextSolarYearAfterPrevStartDate) {
              throw new \RuntimeException('Solar year offset error.');
            }
            $newSolarYear = $firstDayOfYear->solarYear + 1;
          }
          else {
            $newSolarYear = $firstDayOfYear->solarYear;
          }

          $firstDayOfYear = new CalendarDay(Weekday::Sunday,
            $startDateGregorianYear,
            $startDateGregorianMonth,
            $startDateGregorianDay,
            $startDateHebrewYear,
            $startDateHebrewMonth,
            $startDateHebrewDay,
            $newSolarYear,
            $newSolarDay);
        }

        $this->mainConnection->insert(self::ASSEMBLED_YEAR_DATA_TABLE)
          ->fields([
            'hebrew_year' => $hebrewYear,
            'gregorian_year' => $gregorianYear,
            'cycle_id_19_year' => $cycleId19Year,
            'cycle_id_247_year' => $cycleId247Year,
            'days_in_hebrew_year' => $numDaysInHebrewYear,
            'days_in_solar_year' => $numDaysInSolarYear,
            'solar_year_days_to_first_gregorian_sabbath' => $solarYearDaysToFirstGregorianSabbath,
            'diff_between_solar_and_hebrew_day' => $diffBetweenSolarAndHebrewDay,
            'year_in_19_year_cycle' => $yearIn19YearCycle,
            'week_id_since_creation_for_first_sabbath' => $weekIdSinceCreationForFirstSabbath,
            'first_hebrew_month' => $firstDayOfYear->hebrewMonth->toInt(),
            'first_hebrew_day' => $firstDayOfYear->hebrewDay,
            'first_solar_year' => $firstDayOfYear->solarYear,
            'first_solar_day' => $firstDayOfYear->solarDay,
          ])->execute();
      } while($fields = $result->fetchAssoc());

      // Clear out the cache!
      $yearCache =& drupal_static(self::YEAR_OBJECT_CACHE_KEY, []);
      $yearCache = [];
    }
    catch (\Throwable $e) {
      if ($transaction !== NULL) {
        // Of course, this might never be reached if there is something like an OOM error, but
        // fixing this would require fundamental changes to Drupal's transaction handling.
        $transaction->rollBack();
      }
      throw $e;
    }
  }

  public static function getTablesSchema() : array {
    return [self::ASSEMBLED_YEAR_DATA_TABLE => [
      'fields' => [
        'hebrew_year' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'The main Hebrew (A.M.) year',
        ],
        'gregorian_year' => [
          'type' => 'int',
          'not null' => TRUE,
          'description' => 'The main Gregorian year',
        ],
        'cycle_id_19_year' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => '19-year cycle number since creation, starting at 1',
        ],
        'cycle_id_247_year' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => '247-year cycle number since creation, starting at 1',
        ],
        'days_in_hebrew_year' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'Number of days in main Hebrew year',
        ],
        'days_in_solar_year' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'Number of days in main solar year',
        ],
        'solar_year_days_to_first_gregorian_sabbath' => [
          'type' => 'int',
          'not null' => FALSE,
          'unsigned' => TRUE,
          'description' => 'Number of days in solar year to first Gregorian Sabbath',
        ],
        'diff_between_solar_and_hebrew_day' => [
          'type' => 'int',
          'not null' => TRUE,
          'description' => 'Difference in days between solar and Hebrew calendar',
        ],
        'year_in_19_year_cycle' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'Year number within 19-year cycle, starting at 1',
        ],
        'week_id_since_creation_for_first_sabbath' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'Week number since creation for first Sabbath in Gregorian year',
        ],
        'first_hebrew_month' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'The first Hebrew month in the year (1-12 or 13)',
        ],
        'first_hebrew_day' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'The first Hebrew day in the year (1-30)',
        ],
        'first_solar_year' => [
          'type' => 'int',
          'not null' => TRUE,
          'description' => 'The first solar year in the year',
        ],
        'first_solar_day' => [
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'description' => 'The first solar day in the year',
        ],
      ],
      'primary key' => ['hebrew_year'],
    ]];
  }

  private static function getAndValidateIntFieldPreparedData(array $arr, string $fieldName, int $hebrewYear) : int {
    self::throwExceptionIfFieldMissingPreparedData($arr, $fieldName, $hebrewYear);
    return (int) $arr[$fieldName];
  }

  private static function getAndValidateIntFieldSourceData(array $arr, string $fieldName, ?int $hebrewYear) : int {
    self::throwExceptionIfFieldMissingSourceData($arr, $fieldName);
    return (int) $arr[$fieldName];
  }

  private static function getAndValidateNullablePositiveIntFieldPreparedData(array $arr, string $fieldName, int $hebrewYear) : ?int {
    if (!isset($arr[$fieldName])) {
      return NULL;
    }
    if ($arr[$fieldName]) {
      $value = (int) $arr[$fieldName];
      if ($value < 1) {
        self::throwInvalidFieldExceptionPreparedData($fieldName, $hebrewYear);
      }
      return $value;
    }
    else return NULL;
  }

  private static function getAndValidateNullablePositiveIntFieldSourceData(array $arr, string $fieldName, ?int $hebrewYear) : ?int {
    if (!isset($arr[$fieldName])) {
      return NULL;
    }
    if ($arr[$fieldName]) {
      $value = (int) $arr[$fieldName];
      if ($value < 1) {
        self::throwInvalidFieldExceptionSourceData($fieldName, $hebrewYear);
      }
      return $value;
    }
    else return NULL;
  }

  private static function getAndValidatePositiveIntFieldPreparedData(array $arr, string $fieldName, int $hebrewYear) : int {
    self::throwExceptionIfFieldMissingPreparedData($arr, $fieldName, $hebrewYear);
    $value = (int) $arr[$fieldName];
    if ($value < 1) {
      self::throwInvalidFieldExceptionPreparedData($fieldName, $hebrewYear);
    }
    return $value;
  }

  private static function getAndValidatePositiveIntFieldSourceData(array $arr, string $fieldName, ?int $hebrewYear) : int {
    self::throwExceptionIfFieldMissingSourceData($arr, $fieldName);
    $value = (int) $arr[$fieldName];
    if ($value < 1) {
      self::throwInvalidFieldExceptionSourceData($fieldName, $hebrewYear);
    }
    return $value;
  }

  private static function throwExceptionIfFieldMissingPreparedData(array $arr, string $fieldName, int $hebrewYear) : void {
    if (!isset($arr[$fieldName])) {
      throw new CorruptDatabaseTableException($fieldName . ' is missing for year ' . $hebrewYear . ' in prepared data table.');
    }
  }

  private static function throwExceptionIfFieldMissingSourceData(array $arr, string $fieldName) : void {
    if (!isset($arr[$fieldName])) {
      throw new CorruptDatabaseTableException($fieldName . ' is missing in source data table.');
    }
  }

  private static function throwInvalidFieldExceptionPreparedData(string $fieldName, int $hebrewYear) : never {
    throw new CorruptDatabaseTableException($fieldName . ' is invalid for year ' . $hebrewYear . ' in prepared data table.');
  }

  private static function throwInvalidFieldExceptionSourceData(string $fieldName, ?int $hebrewYear = NULL) : never {
    $message = $fieldName . ' is invalid in source data table';
    if ($hebrewYear === NULL) $message .= '.';
    else $message .= ' for year ' . $hebrewYear . '.';
    throw new CorruptDatabaseTableException($message);
  }

  private static function throwNoPreparedDataForYearException(int $hebrewYear) : never {
    throw new NoDataForYearException('No prepared data found for Hebrew year '
      . $hebrewYear
      . '. Could it be you forgot to call prepareYears(), or that there is not data for the year requested?');
  }

}
