<?php

namespace Drupal\Tests\mailchimp_lists\Functional;

/**
 * Tests audience webhook functionality.
 *
 * @group mailchimp
 */
class MailchimpListsWebhookTest extends MailchimpListsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['mailchimp', 'mailchimp_lists', 'mailchimp_test'];

  /**
   * Tests retrieval of webhooks for an audience.
   */
  public function testGetWebhook() {
    $list_id = '57afe96172';

    $webhooks = \Drupal::service('mailchimp.api')->webhookGet($list_id);

    $this->assertSame($webhooks[0]->list_id, $list_id);
    $this->assertSame($webhooks[0]->id, '37b9c73a88');
    $this->assertSame($webhooks[0]->url, 'http://example.org');
    $this->assertTrue($webhooks[0]->events->subscribe);
    $this->assertFalse($webhooks[0]->events->unsubscribe);
    $this->assertTrue($webhooks[0]->sources->user);
    $this->assertFalse($webhooks[0]->sources->api);
  }

  /**
   * Tests adding a webhook to an audience.
   */
  public function testAddWebhook() {
    $list_id = '57afe96172';
    $url = 'http://example.org/web-hook-new';
    $events = [
      'subscribe' => TRUE,
    ];
    $sources = [
      'user' => TRUE,
      'admin' => TRUE,
      'api' => FALSE,
    ];

    $webhook_id = \Drupal::service('mailchimp.api')->webhookAdd($list_id, $url, $events, $sources);

    $this->assertSame($webhook_id, 'ab24521a00');
  }

  /**
   * Tests deletion of a webhook.
   */
  public function testDeleteWebhook() {
    $list_id = '57afe96172';
    $url = 'http://example.org';

    $webhook_deleted = \Drupal::service('mailchimp.api')->webhookDelete($list_id, $url);

    $this->assertTrue($webhook_deleted, 'Tested webhook deletion.');
  }

}
