<?php

declare(strict_types=1);

namespace Drupal\Tests\mailchimp\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\ContextProvider\CurrentLanguageContext;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\mailchimp\ApiService;
use Drupal\mailchimp\ClientFactory;
use Mailchimp\Tests\Mailchimp as TestMailchimp;
use Mailchimp\Tests\MailchimpLists as TestMailchimpLists;
use Mailchimp\Tests\MailchimpCampaigns as TestMailchimpCampaigns;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * Tests for ApiService methods.
 */
#[Group('mailchimp')]
final class ApiServiceTest extends MailchimpKernelTestBase {

  /**
   * The Api service.
   *
   * @var \Drupal\mailchimp\ApiService
   */
  protected $apiService;

  /**
   * The key-value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $keyValueFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cache;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $lockBackend;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $api_class = new TestMailchimp(['api_key' => 'TEST', 'api_user' => 'TEST']);
    $client_factory = $this->prophesize(ClientFactory::class);
    $mc_lists = new TestMailchimpLists($api_class);
    $mc_campaigns = new TestMailchimpCampaigns($api_class);
    $client_factory->getByClassNameOrNull('MailchimpLists')->willReturn($mc_lists);
    $client_factory->getByClassNameOrNull('MailchimpCampaigns')->willReturn($mc_campaigns);
    $messenger = $this->prophesize(MessengerInterface::class);
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $key_value_store->get('lists', [])->willReturn([]);
    $key_value_store->get(Argument::any(), Argument::any())->willReturnArgument(1);
    $key_value_store->set(Argument::any(), Argument::any())->willReturn(NULL);
    $key_value_store->deleteAll()->willReturn(NULL);
    $this->keyValueFactory = $this->prophesize(KeyValueFactoryInterface::class);
    $this->keyValueFactory->get('mailchimp_lists')->willReturn($key_value_store->reveal());
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $logger = $this->prophesize(LoggerInterface::class);
    $languageContext = $this->prophesize(CurrentLanguageContext::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler->invokeAll(Argument::any(), Argument::any())->willReturn([]);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('test_mode')->willReturn(TRUE);
    $config->get('cron')->willReturn(FALSE);
    $config->get('optin_check_email_msg')->willReturn('Please check your email');
    $this->configFactory->get('mailchimp.settings')->willReturn($config->reveal());
    $this->lockBackend = $this->prophesize(LockBackendInterface::class);
    $this->lockBackend->acquire(Argument::any(), Argument::any())->willReturn(TRUE);
    $this->lockBackend->release(Argument::any())->willReturn(NULL);
    $this->apiService = new ApiService(
      $client_factory->reveal(),
      $messenger->reveal(),
      $this->keyValueFactory->reveal(),
      $this->cache->reveal(),
      $logger->reveal(),
      $languageContext->reveal(),
      $this->moduleHandler->reveal(),
      $this->configFactory->reveal(),
      $this->lockBackend->reveal()
    );
  }

  /**
   * Tests the API service's getApiObject() function.
   */
  public function testGetApiObject(): void {
    $result = $this->apiService->getApiObject('MailchimpLists');
    self::assertNotNull($result, "ApiService::getApiObject() should return a MailchimpLists object.");
    self::assertInstanceOf('Mailchimp\Tests\MailchimpLists', $result);
  }

  /**
   * Tests that getApiObject() notifies the messenger by default on failure.
   */
  public function testGetApiObjectNotifiesMessengerByDefaultOnFailure(): void {
    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())->method('addError');

    $api_service = $this->createApiServiceWithMissingApiUser($messenger);

    self::assertNull($api_service->getApiObject('MailchimpApiUser'));
  }

  /**
   * Tests that getApiObject(notify: FALSE) suppresses the messenger error.
   *
   * Regression test: callers like Processor::ping(), which run on every
   * cron invocation, must not queue a user-facing messenger error when the
   * API is unavailable.
   */
  public function testGetApiObjectNotifyFalseSuppressesMessengerError(): void {
    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->never())->method('addError');

    $api_service = $this->createApiServiceWithMissingApiUser($messenger);

    self::assertNull($api_service->getApiObject('MailchimpApiUser', notify: FALSE));
  }

  /**
   * Builds an ApiService whose MailchimpApiUser client cannot be loaded.
   */
  protected function createApiServiceWithMissingApiUser(MessengerInterface $messenger): ApiService {
    $client_factory = $this->prophesize(ClientFactory::class);
    $client_factory->getByClassNameOrNull('MailchimpApiUser')->willReturn(NULL);

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('mailchimp.settings')->willReturn($this->prophesize(ImmutableConfig::class)->reveal());

    return new ApiService(
      $client_factory->reveal(),
      $messenger,
      $this->prophesize(KeyValueFactoryInterface::class)->reveal(),
      $this->prophesize(CacheBackendInterface::class)->reveal(),
      $this->prophesize(LoggerInterface::class)->reveal(),
      $this->prophesize(CurrentLanguageContext::class)->reveal(),
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $config_factory->reveal(),
      $this->prophesize(LockBackendInterface::class)->reveal(),
    );
  }

  /**
   * Tests the API service's getAudiences() function.
   */
  public function testGetAudiences(): void {
    // Get all lists.
    $result = $this->apiService->getAudiences();
    self::assertCount(3, $result, "ApiService::getAudiences() returned incorrect number of lists.");

    // Get specific lists.
    $list_ids = ['57afe96172', '587693d673'];
    $result = $this->apiService->getAudiences($list_ids);
    self::assertCount(2, $result, "ApiService::getAudiences() returned incorrect number of lists.");

    // Test with reset flag.
    $result = $this->apiService->getAudiences([], TRUE);
    self::assertCount(3, $result, "ApiService::getAudiences() with reset should return all lists.");
  }

  /**
   * Tests the API service's getMergevars() function.
   */
  public function testGetMergevars(): void {
    $list_ids = ['57afe96172', '587693d673'];
    $result = $this->apiService->getMergevars($list_ids);
    self::assertCount(2, $result, "ApiService::getMergevars() returned incorrect number of lists.");
    foreach ($list_ids as $list_id) {
      self::assertCount(4, $result[$list_id], "$list_id returned unexpected mergevar count.");
      self::assertSame('EMAIL', $result[$list_id][0]->tag, "$list_id returned unexpected mergevar tag.");
      self::assertSame('FNAME', $result[$list_id][1]->tag, "$list_id returned unexpected mergevar tag.");
      self::assertSame('LNAME', $result[$list_id][2]->tag, "$list_id returned unexpected mergevar tag.");
    }

    // Test with reset flag.
    $result = $this->apiService->getMergevars($list_ids, TRUE);
    self::assertCount(2, $result, "ApiService::getMergevars() with reset should return mergevars.");
  }

  /**
   * Tests the API service's getMemberInfo() function.
   */
  public function testGetMemberInfo(): void {
    $this->cache->get(Argument::any())->willReturn(FALSE);
    $this->cache->set(Argument::any(), Argument::any())->willReturn(NULL);    $result = $this->apiService->getMemberInfo('57afe96172', 'test@example.com');

    // Get all lists.
    self::assertSame('test@example.com', $result->email_address, "ApiService::getMemberInfo() returned unexpected result.");
    self::assertSame('subscribed', $result->status, "ApiService::getMemberInfo() returned unexpected status.");

    // Test with reset flag.
    $result = $this->apiService->getMemberInfo('57afe96172', 'test@example.com', TRUE);
    self::assertSame('test@example.com', $result->email_address, "ApiService::getMemberInfo() with reset should return member info.");
  }

  /**
   * Tests the API service's subscribeProcess() function.
   */
  public function testSubscribeProcess(): void {
    $audience_id = '57afe96172';
    $email = 'test@example.com';
    $merge_vars = ['FNAME' => 'Test', 'LNAME' => 'User'];
    $interests = [];

    $result = $this->apiService->subscribeProcess($audience_id, $email, $merge_vars, $interests);
    self::assertNotNull($result, "ApiService::subscribeProcess() should return a result.");
    self::assertSame($email, $result->email_address, "ApiService::subscribeProcess() returned unexpected email.");

    // Test with double opt-in.
    $result = $this->apiService->subscribeProcess($audience_id, $email, $merge_vars, $interests, TRUE);
    self::assertNotNull($result, "ApiService::subscribeProcess() with double opt-in should return a result.");

    // Test with tags.
    $result = $this->apiService->subscribeProcess($audience_id, $email, $merge_vars, $interests, FALSE, 'html', NULL, FALSE, 'tag1,tag2');
    self::assertNotNull($result, "ApiService::subscribeProcess() with tags should return a result.");

    // Test with segment.
    $result = $this->apiService->subscribeProcess($audience_id, $email, $merge_vars, $interests, FALSE, 'html', NULL, FALSE, NULL, 'segment-1');
    self::assertNotNull($result, "ApiService::subscribeProcess() with segment should return a result.");
  }

  /**
   * Tests the API service's updateMemberProcess() function.
   */
  public function testUpdateMemberProcess(): void {
    $audience_id = '57afe96172';
    $email = 'test@example.com';
    $merge_vars = ['FNAME' => 'Updated', 'LNAME' => 'Name'];
    $interests = [];
    $format = 'html';

    $result = $this->apiService->updateMemberProcess($audience_id, $email, $merge_vars, $interests, $format);
    self::assertNotNull($result, "ApiService::updateMemberProcess() should return a result.");
    self::assertSame($email, $result->email_address, "ApiService::updateMemberProcess() returned unexpected email.");

    // Test with double opt-in.
    $result = $this->apiService->updateMemberProcess($audience_id, $email, $merge_vars, $interests, $format, TRUE);
    self::assertNotNull($result, "ApiService::updateMemberProcess() with double opt-in should return a result.");
  }

  /**
   * Tests the API service's getMembers() function.
   */
  public function testGetMembers(): void {
    $audience_id = '57afe96172';
    $status = 'subscribed';

    $result = $this->apiService->getMembers($audience_id, $status);
    self::assertIsObject($result, "ApiService::getMembers() should return an object.");
    self::assertSame(2, $result->total_items, "ApiService::getMembers() returned incorrect number of members.");
    self::assertCount(2, $result->members, "ApiService::getMembers() returned incorrect members array.");

    // Test with lock failure.
    $this->lockBackend->acquire(Argument::any(), Argument::any())->willReturn(FALSE);
    $result = $this->apiService->getMembers($audience_id, $status);
    self::assertFalse($result, "ApiService::getMembers() should return FALSE when lock cannot be acquired.");
  }

  /**
   * Tests the API service's batchUpdateMembers() function.
   */
  public function testBatchUpdateMembers(): void {
    $audience_id = '57afe96172';
    $batch = [
      [
        'email' => 'test1@example.com',
        'email_type' => 'html',
        'merge_vars' => ['FNAME' => 'Test1'],
      ],
      [
        'email' => 'test2@example.com',
        'email_type' => 'html',
        'merge_vars' => ['FNAME' => 'Test2'],
      ],
    ];

    $result = $this->apiService->batchUpdateMembers($audience_id, $batch);
    self::assertIsObject($result, "ApiService::batchUpdateMembers() should return a batch result object.");
    self::assertSame('pending', $result->status, "ApiService::batchUpdateMembers() returned unexpected batch status.");

    // Test with empty batch.
    $result = $this->apiService->batchUpdateMembers($audience_id, []);
    self::assertFalse($result, "ApiService::batchUpdateMembers() should return FALSE for empty batch.");
  }

  /**
   * Tests the API service's unsubscribeProcess() function.
   */
  public function testUnsubscribeProcess(): void {
    $audience_id = '57afe96172';
    $email = 'test@example.com';

    $result = $this->apiService->unsubscribeProcess($audience_id, $email);
    self::assertTrue($result, "ApiService::unsubscribeProcess() should return TRUE on success.");
  }

  /**
   * Tests the API service's getCampaignData() function.
   */
  public function testGetCampaignData(): void {
    $campaign_id = 'test-campaign-id';
    $this->cache->set(Argument::any(), Argument::any())->willReturn(NULL);

    // Test without cache.
    $this->cache->get(Argument::any())->willReturn(FALSE);
    $result = $this->apiService->getCampaignData($campaign_id);
    self::assertNotFalse($result, "ApiService::getCampaignData() should return campaign data.");
    if (is_object($result)) {
      self::assertSame($campaign_id, $result->id, "ApiService::getCampaignData() returned unexpected campaign ID.");
    }

    // Test with cache.
    $cached_data = new \stdClass();
    $cached_data->data = (object) ['id' => $campaign_id, 'type' => 'cached'];
    $this->cache->get('campaign_' . $campaign_id)->willReturn($cached_data);
    $result = $this->apiService->getCampaignData($campaign_id);
    self::assertNotFalse($result, "ApiService::getCampaignData() should return cached data.");

    // Test with reset flag.
    $result = $this->apiService->getCampaignData($campaign_id, TRUE);
    self::assertNotFalse($result, "ApiService::getCampaignData() with reset should return campaign data.");
  }

  /**
   * Tests the API service's getAudiencesForEmail() function.
   */
  public function testGetAudiencesForEmail(): void {
    $email = 'test@example.com';

    $result = $this->apiService->getAudiencesForEmail($email);
    self::assertIsArray($result, "ApiService::getAudiencesForEmail() should return an array.");
    self::assertNotEmpty($result, "ApiService::getAudiencesForEmail() should return subscribed lists for test email.");
  }

  /**
   * Tests the API service's webhookGet() function.
   */
  public function testWebhookGet(): void {
    $audience_id = '57afe96172';

    $result = $this->apiService->webhookGet($audience_id);
    self::assertIsArray($result, "ApiService::webhookGet() should return an array of webhooks.");
    self::assertCount(1, $result, "ApiService::webhookGet() returned incorrect number of webhooks.");
    self::assertSame('http://example.org', $result[0]->url, "ApiService::webhookGet() returned unexpected webhook URL.");
  }

  /**
   * Tests the API service's webhookAdd() function.
   */
  public function testWebhookAdd(): void {
    $audience_id = '57afe96172';
    $url = 'https://example.com/webhook';
    $events = ['subscribe' => TRUE, 'unsubscribe' => TRUE];
    $sources = ['user' => TRUE, 'api' => TRUE];

    $result = $this->apiService->webhookAdd($audience_id, $url, $events, $sources);
    self::assertSame('ab24521a00', $result, "ApiService::webhookAdd() returned unexpected webhook ID.");
  }

  /**
   * Tests the API service's webhookDelete() function.
   */
  public function testWebhookDelete(): void {
    $audience_id = '57afe96172';
    $url = 'http://example.org';

    $result = $this->apiService->webhookDelete($audience_id, $url);
    self::assertTrue($result, "ApiService::webhookDelete() should return TRUE on success.");

    // Test with non-existent webhook.
    $result = $this->apiService->webhookDelete($audience_id, 'https://nonexistent.com/webhook');
    self::assertFalse($result, "ApiService::webhookDelete() should return FALSE for non-existent webhook.");
  }

  /**
   * Tests the API service's interestGroupFormElements() function.
   */
  public function testInterestGroupFormElements(): void {
    // First, get an audience with interest groups.
    $audiences = $this->apiService->getAudiences();
    $audience = reset($audiences);

    // Ensure the audience has interest groups.
    if (!empty($audience->intgroups)) {
      $result = $this->apiService->interestGroupFormElements($audience);
      self::assertIsArray($result, "ApiService::interestGroupFormElements() should return an array.");
      self::assertNotEmpty($result, "ApiService::interestGroupFormElements() should return form elements.");

      // Test with defaults.
      $defaults = ['a1e9f4b7f6' => ['9143cf3bd1']];
      $result = $this->apiService->interestGroupFormElements($audience, $defaults);
      self::assertIsArray($result, "ApiService::interestGroupFormElements() with defaults should return an array.");

      // Test with email.
      $result = $this->apiService->interestGroupFormElements($audience, [], 'test@example.com');
      self::assertIsArray($result, "ApiService::interestGroupFormElements() with email should return an array.");

      // Test with hidden mode.
      $result = $this->apiService->interestGroupFormElements($audience, [], NULL, 'hidden');
      self::assertIsArray($result, "ApiService::interestGroupFormElements() with hidden mode should return an array.");
    }
    else {
      // If no interest groups, test still passes but we skip the detailed assertions.
      $this->markTestSkipped('No interest groups available in test audience.');
    }
  }

}
