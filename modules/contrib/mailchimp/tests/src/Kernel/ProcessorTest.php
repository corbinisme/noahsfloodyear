<?php

declare(strict_types=1);

namespace Drupal\Tests\mailchimp\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\ContextProvider\CurrentLanguageContext;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\State\StateInterface;
use Drupal\mailchimp\ApiService;
use Drupal\mailchimp\ClientFactory;
use Drupal\mailchimp\Queue\Processor;
use Mailchimp\MailchimpAPIException;
use Mailchimp\Tests\Mailchimp as TestMailchimp;
use Mailchimp\Tests\MailchimpApiUser as TestMailchimpApiUser;
use Mailchimp\Tests\MailchimpCampaigns as TestMailchimpCampaigns;
use Mailchimp\Tests\MailchimpLists as TestMailchimpLists;
use Mailchimp\http\MailchimpHttpClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * Tests for the Mailchimp queue processor.
 */
#[CoversClass(Processor::class)]
#[Group('mailchimp')]
#[RunTestsInSeparateProcesses]
final class ProcessorTest extends MailchimpKernelTestBase {

  /**
   * Tests that a fresh ping cache hit returns the cached success value.
   */
  public function testPingUsesCachedSuccessWithinTtl(): void {
    $now = 1_700_000_000;
    $processor = $this->createProcessor(
      api_service: $this->createApiService(),
      state: $this->createStateStore([
        'mailchimp.ping_time' => $now - 30,
        'mailchimp.ping_ok' => TRUE,
      ]),
      time: $this->createTimeMock($now),
    );

    self::assertTrue($this->invokePing($processor));
  }

  /**
   * Tests that a fresh ping cache hit returns the cached failure value.
   */
  public function testPingUsesCachedFailureWithinTtl(): void {
    $now = 1_700_000_000;
    $processor = $this->createProcessor(
      api_service: $this->createApiService(),
      state: $this->createStateStore([
        'mailchimp.ping_time' => $now - 30,
        'mailchimp.ping_ok' => FALSE,
      ]),
      time: $this->createTimeMock($now),
    );

    self::assertFalse($this->invokePing($processor));
  }

  /**
   * Tests that an expired cache triggers a successful API ping.
   */
  public function testPingCallsApiWhenCacheExpiredAndSucceeds(): void {
    $now = 1_700_000_000;
    $state = $this->createStateStore([
      'mailchimp.ping_time' => $now - 120,
    ]);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(
        http_client: new MailchimpPingTestHttpClient(),
      ),
      state: $state,
      time: $this->createTimeMock($now),
    );

    self::assertTrue($this->invokePing($processor));
    self::assertSame($now, $this->getStateValue($state, 'mailchimp.ping_time'));
    self::assertTrue($this->getStateValue($state, 'mailchimp.ping_ok'));
  }

  /**
   * Tests that a non-200 ping response is treated as failure.
   */
  public function testPingReturnsFalseOnNon200Status(): void {
    $now = 1_700_000_000;
    $state = $this->createStateStore([
      'mailchimp.ping_time' => $now - 120,
    ]);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(
        http_client: new MailchimpPingTestHttpClient(status_code: 503),
      ),
      state: $state,
      time: $this->createTimeMock($now),
    );

    self::assertFalse($this->invokePing($processor));
    self::assertFalse($this->getStateValue($state, 'mailchimp.ping_ok'));
  }

  /**
   * Tests that a Mailchimp API exception during ping is treated as failure.
   */
  public function testPingReturnsFalseOnApiException(): void {
    $now = 1_700_000_000;
    $state = $this->createStateStore([
      'mailchimp.ping_time' => $now - 120,
    ]);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with('Mailchimp API ping failed: {message}', ['message' => 'Connection refused']);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(
        http_client: new MailchimpPingTestHttpClient(
          exception: new MailchimpAPIException('Connection refused'),
        ),
      ),
      state: $state,
      logger: $logger,
      time: $this->createTimeMock($now),
    );

    self::assertFalse($this->invokePing($processor));
    self::assertFalse($this->getStateValue($state, 'mailchimp.ping_ok'));
  }

  /**
   * Tests that ping fails when no API object is available.
   */
  public function testPingReturnsFalseWhenApiObjectMissing(): void {
    $now = 1_700_000_000;
    $state = $this->createStateStore([
      'mailchimp.ping_time' => $now - 120,
    ]);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(with_api_user: FALSE),
      state: $state,
      time: $this->createTimeMock($now),
    );

    self::assertFalse($this->invokePing($processor));
    self::assertFalse($this->getStateValue($state, 'mailchimp.ping_ok'));
  }

  /**
   * Tests that process() skips the queue when ping fails.
   */
  public function testProcessReturnsZeroWhenPingFails(): void {
    $now = 1_700_000_000;
    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->expects($this->never())->method('get');

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('warning')
      ->with('Mailchimp API ping failed. Queue processing skipped to prevent data loss.');

    $processor = $this->createProcessor(
      api_service: $this->createApiService(),
      queue_factory: $queue_factory,
      state: $this->createStateStore([
        'mailchimp.ping_time' => $now - 30,
        'mailchimp.ping_ok' => FALSE,
      ]),
      logger: $logger,
      time: $this->createTimeMock($now),
    );

    self::assertSame(0, $processor->process());
  }

  /**
   * Tests that process() returns zero for an empty queue.
   */
  public function testProcessReturnsZeroWhenQueueEmpty(): void {
    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())->method('createQueue');
    $queue->method('numberOfItems')->willReturn(0);

    $failed_queue = $this->createMock(QueueInterface::class);
    $failed_queue->expects($this->once())->method('createQueue');

    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->method('get')->willReturnMap([
      [MAILCHIMP_QUEUE_CRON, FALSE, $queue],
      [MAILCHIMP_QUEUE_CRON_FAILED, FALSE, $failed_queue],
    ]);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(),
      queue_factory: $queue_factory,
      state: $this->createStateStore([
        'mailchimp.ping_time' => 1_700_000_000 - 30,
        'mailchimp.ping_ok' => TRUE,
      ]),
      time: $this->createTimeMock(1_700_000_000),
    );

    self::assertSame(0, $processor->process());
  }

  /**
   * Tests that process() invokes queued API calls and deletes items.
   */
  public function testProcessInvokesQueuedApiCalls(): void {
    $item = (object) [
      'data' => [
        'function' => 'unsubscribeProcess',
        'args' => ['57afe96172', 'test@example.com'],
      ],
    ];

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())->method('createQueue');
    $queue->method('numberOfItems')->willReturn(1);
    $queue->method('claimItem')->willReturn($item);
    $queue->expects($this->once())->method('deleteItem')->with($item);

    $failed_queue = $this->createMock(QueueInterface::class);
    $failed_queue->expects($this->once())->method('createQueue');

    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->method('get')->willReturnMap([
      [MAILCHIMP_QUEUE_CRON, FALSE, $queue],
      [MAILCHIMP_QUEUE_CRON_FAILED, FALSE, $failed_queue],
    ]);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(),
      queue_factory: $queue_factory,
      state: $this->createStateStore([
        'mailchimp.ping_time' => 1_700_000_000 - 30,
        'mailchimp.ping_ok' => TRUE,
      ]),
      time: $this->createTimeMock(1_700_000_000),
    );

    self::assertSame(1, $processor->process(1));
  }

  /**
   * Tests that process() reads batch_limit from config when not provided.
   */
  public function testProcessUsesConfiguredBatchLimit(): void {
    $item = (object) [
      'data' => [
        'function' => 'unsubscribeProcess',
        'args' => ['57afe96172', 'test@example.com'],
      ],
    ];

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('createQueue');
    $queue->method('numberOfItems')->willReturn(5);
    $queue->method('claimItem')->willReturn($item);
    $queue->expects($this->exactly(2))->method('deleteItem')->with($item);

    $failed_queue = $this->createMock(QueueInterface::class);
    $failed_queue->method('createQueue');

    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->method('get')->willReturnMap([
      [MAILCHIMP_QUEUE_CRON, FALSE, $queue],
      [MAILCHIMP_QUEUE_CRON_FAILED, FALSE, $failed_queue],
    ]);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('batch_limit')->willReturn(2);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('mailchimp.settings')->willReturn($config);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(),
      config_factory: $config_factory,
      queue_factory: $queue_factory,
      state: $this->createStateStore([
        'mailchimp.ping_time' => 1_700_000_000 - 30,
        'mailchimp.ping_ok' => TRUE,
      ]),
      time: $this->createTimeMock(1_700_000_000),
    );

    self::assertSame(2, $processor->process());
  }

  /**
   * Tests that process() uses the smaller of queue count and batch limit.
   */
  public function testProcessBatchSizeIsMinOfQueueCountAndLimit(): void {
    $item = (object) [
      'data' => [
        'function' => 'unsubscribeProcess',
        'args' => ['57afe96172', 'test@example.com'],
      ],
    ];

    $queue = $this->createMock(QueueInterface::class);
    $queue->method('createQueue');
    $queue->method('numberOfItems')->willReturn(2);
    $queue->method('claimItem')->willReturn($item);
    $queue->expects($this->exactly(2))->method('deleteItem')->with($item);

    $failed_queue = $this->createMock(QueueInterface::class);
    $failed_queue->method('createQueue');

    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->method('get')->willReturnMap([
      [MAILCHIMP_QUEUE_CRON, FALSE, $queue],
      [MAILCHIMP_QUEUE_CRON_FAILED, FALSE, $failed_queue],
    ]);

    $processor = $this->createProcessor(
      api_service: $this->createApiService(),
      queue_factory: $queue_factory,
      state: $this->createStateStore([
        'mailchimp.ping_time' => 1_700_000_000 - 30,
        'mailchimp.ping_ok' => TRUE,
      ]),
      time: $this->createTimeMock(1_700_000_000),
    );

    self::assertSame(2, $processor->process(5));
  }

  /**
   * Creates a Processor with optional dependency overrides.
   */
  protected function createProcessor(
    ApiService $api_service,
    ?ConfigFactoryInterface $config_factory = NULL,
    ?QueueFactory $queue_factory = NULL,
    ?StateInterface $state = NULL,
    ?LoggerInterface $logger = NULL,
    ?TimeInterface $time = NULL,
  ): Processor {
    return new Processor(
      $api_service,
      $config_factory ?? $this->createMock(ConfigFactoryInterface::class),
      $queue_factory ?? $this->createMock(QueueFactory::class),
      $state ?? $this->createStateStore(),
      $logger ?? $this->createMock(LoggerInterface::class),
      $time ?? $this->createMock(TimeInterface::class),
    );
  }

  /**
   * Creates an ApiService backed by the Mailchimp test library.
   */
  protected function createApiService(
    bool $with_api_user = TRUE,
    ?MailchimpPingTestHttpClient $http_client = NULL,
  ): ApiService {
    $api_class = new TestMailchimp(['api_key' => 'TEST', 'api_user' => 'TEST']);
    if ($http_client) {
      $api_class->client = $http_client;
    }

    $client_factory = $this->prophesize(ClientFactory::class);
    $client_factory->getByClassNameOrNull('MailchimpLists')->willReturn(new TestMailchimpLists($api_class));
    $client_factory->getByClassNameOrNull('MailchimpCampaigns')->willReturn(new TestMailchimpCampaigns($api_class));
    $client_factory->getByClassNameOrNull('MailchimpApiUser')->willReturn(
      $with_api_user ? new TestMailchimpApiUser($api_class) : NULL,
    );

    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $key_value_store->get('lists', [])->willReturn([]);
    $key_value_store->get(Argument::any(), Argument::any())->willReturnArgument(1);
    $key_value_store->set(Argument::any(), Argument::any())->willReturn(NULL);
    $key_value_factory = $this->prophesize(KeyValueFactoryInterface::class);
    $key_value_factory->get('mailchimp_lists')->willReturn($key_value_store->reveal());

    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('test_mode')->willReturn(TRUE);
    $config->get('cron')->willReturn(FALSE);
    $config->get('optin_check_email_msg')->willReturn('Please check your email');
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('mailchimp.settings')->willReturn($config->reveal());

    $lock_backend = $this->prophesize(LockBackendInterface::class);
    $lock_backend->acquire(Argument::any(), Argument::any())->willReturn(TRUE);
    $lock_backend->release(Argument::any())->willReturn(NULL);

    return new ApiService(
      $client_factory->reveal(),
      $this->prophesize(MessengerInterface::class)->reveal(),
      $key_value_factory->reveal(),
      $this->prophesize(CacheBackendInterface::class)->reveal(),
      $this->prophesize(LoggerInterface::class)->reveal(),
      $this->prophesize(CurrentLanguageContext::class)->reveal(),
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $config_factory->reveal(),
      $lock_backend->reveal(),
    );
  }

  /**
   * Creates an in-memory state store for ping cache assertions.
   */
  protected function createStateStore(array $values = []): StateInterface {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturnCallback(function (string $key, mixed $default = NULL) use (&$values): mixed {
      return $values[$key] ?? $default;
    });
    $state->method('set')->willReturnCallback(function (string $key, mixed $value) use (&$values): void {
      $values[$key] = $value;
    });
    $state->method('getMultiple')->willReturnCallback(function (array $keys) use (&$values): array {
      return array_intersect_key($values, array_flip($keys));
    });
    return $state;
  }

  /**
   * Reads a value from a mocked state store.
   */
  protected function getStateValue(StateInterface $state, string $key): mixed {
    return $state->get($key);
  }

  /**
   * Creates a time mock returning a fixed request time.
   */
  protected function createTimeMock(int $now): TimeInterface {
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn($now);
    return $time;
  }

  /**
   * Invokes the protected ping() method.
   */
  protected function invokePing(Processor $processor): bool {
    $method = new \ReflectionMethod(Processor::class, 'ping');
    return $method->invoke($processor);
  }

}

/**
 * HTTP client test double for Mailchimp ping responses.
 */
final class MailchimpPingTestHttpClient implements MailchimpHttpClientInterface {

  public $method;

  public $uri;

  public $options;

  public function __construct(
    private readonly int $status_code = 200,
    private readonly ?MailchimpAPIException $exception = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function handleRequest($method, $uri = '', $options = [], $parameters = [], $returnAssoc = FALSE) {
    if ($this->exception) {
      throw $this->exception;
    }

    if (!empty($parameters)) {
      if ($method == 'GET') {
        $options['query'] = $parameters;
      }
      else {
        $options['json'] = (object) $parameters;
      }
    }

    $this->method = $method;
    $this->uri = $uri;
    $this->options = $options;

    if ($this->status_code === 200) {
      return (object) ['health_status' => "Everything's Chimpy!"];
    }

    return (object) [];
  }

}
