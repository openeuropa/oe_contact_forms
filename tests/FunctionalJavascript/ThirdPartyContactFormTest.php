<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test Thirdparty settings on contact forms.
 */
class ThirdPartyContactFormTest extends WebDriverTestBase {

  /**
   * An test user with permission to submit contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testuser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'system',
    'contact',
    'contact_storage',
    'oe_contact_forms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and login test user.
    $this->testuser = $this->drupalCreateUser([
      'access site-wide contact form',
    ]);
    $this->drupalLogin($this->testuser);
  }

  /**
   * Tests the contact add form.
   */
  public function testAddForm(): void {
    $this->drupalGet(Url::fromRoute('contact.form_add'));
    $page = $this->getSession()->getPage();
    $page->fillField('Label', 'My form');
    $page->fillField('Recipients', 'test@test.com');
    $page->fillField('Topic name', 'my topic name');
    $page->fillField('Topic email address(es)', 'example@example.com');
    // $page->pressButton('Add more');
    $page->pressButton('Save');
  }

}
