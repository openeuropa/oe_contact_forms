<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_contact_forms\Kernel;

use Drupal\contact\Entity\ContactForm;

/**
 * Tests contact form block derivatives.
 */
class BlocksTest extends ContactFormTestBase {

  /**
   * Tests that we have a block derivative for exposed contact forms.
   */
  public function testBlockDerivatives(): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $this->container->get('plugin.manager.block');

    $contact_form = ContactForm::create(['id' => 'oe_contact_form_compare']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', 'http://example.net');
    $topics = [['topic_name' => 'Topic to compare', 'topic_email_address' => 'compare-topic@emailaddress.com']];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();
    $plugin_id = 'oe_contact_forms_corporate_block:' . $contact_form->uuid();

    // Assert the block was created.
    $this->assertTrue($block_manager->hasDefinition($plugin_id));

    /** @var \Drupal\oe_contact_forms\Plugin\Block\CorporateFormBlock $plugin */
    $plugin = $block_manager->createInstance($plugin_id);
    $build = $plugin->build();

    // Assert we have correct topic option.
    $this->assertContains('Topic to compare', $build['oe_topic']['widget']['#options']);

    $contact_form = ContactForm::create(['id' => 'oe_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $topic_label = 'Topic label';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topic_label', $topic_label);
    $header = 'this is a test header';
    $contact_form->setThirdPartySetting('oe_contact_forms', 'header', $header);
    $privacy_url = 'http://example.net';
    $privacy_text = "I have read and agree with the <a href=\"{$privacy_url}\" target=\"_blank\">data protection terms</a>";
    $contact_form->setThirdPartySetting('oe_contact_forms', 'privacy_policy', $privacy_url);
    $topics = [['topic_name' => 'Topic name', 'topic_email_address' => 'topic@emailaddress.com']];
    $contact_form->setThirdPartySetting('oe_contact_forms', 'topics', $topics);
    $contact_form->save();
    $plugin_id = 'oe_contact_forms_corporate_block:' . $contact_form->uuid();

    // Assert the block was created.
    $this->assertTrue($block_manager->hasDefinition($plugin_id));

    /** @var \Drupal\oe_contact_forms\Plugin\Block\CorporateFormBlock $plugin */
    $plugin = $block_manager->createInstance($plugin_id);
    $build = $plugin->build();

    // Assert we have correct build.
    $this->assertEqual($build['header']['#markup'], $header);
    $this->assertEqual($build['privacy_policy']['#title'], $privacy_text);
    $this->assertEqual($build['oe_topic']['widget']['#title'], $topic_label);
    $this->assertContains('Topic name', $build['oe_topic']['widget']['#options']);
    $this->assertNotContains('Topic to compare', $build['oe_topic']['widget']['#options']);

    // Remove from derivatives.
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', FALSE);
    $contact_form->save();

    // Assert the block was removed.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));

    $contact_form = ContactForm::create(['id' => 'oe_contact_form_no_block']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', FALSE);
    $contact_form->save();
    $plugin_id = 'oe_contact_forms_corporate_block:' . $contact_form->uuid();

    // Assert the block was not created.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));

    // Now expose the block to pick up the change.
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $contact_form->save();

    // Assert the block was created.
    $this->assertTrue($block_manager->hasDefinition($plugin_id));

    // Now delete the block content entity.
    $contact_form->delete();

    // Assert the block was removed.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));

    $contact_form = ContactForm::create(['id' => 'default_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);
    $contact_form->save();
    $plugin_id = 'oe_contact_forms_corporate_block:' . $contact_form->uuid();

    // Assert the block was not created.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));
  }

}
