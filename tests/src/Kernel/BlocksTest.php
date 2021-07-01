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

    // Create an initial contact form with one topic.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form_one']);
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

    // Create a second contact form, this time with another topic.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form_two']);
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

    // Assert we have the correct build.
    $this->assertEquals($header, $build['header']['#markup']);
    $this->assertEquals($privacy_text, $build['privacy_policy']['#title']);
    $this->assertEquals($topic_label, $build['oe_topic']['widget']['#title']);
    $this->assertContains('Topic name', $build['oe_topic']['widget']['#options']);

    // Assert the dynamically generated topics did not get cached and we only
    // have the topic configured on this contact form.
    $this->assertNotContains('Topic to compare', $build['oe_topic']['widget']['#options']);

    // Configure the form not to be exposed as a block anymore.
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', FALSE);
    $contact_form->save();

    // Assert the contact form is no longer derived into a block.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));

    // Create a third contact form and don't expose it as a block.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form_three']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', FALSE);
    $contact_form->save();
    $plugin_id = 'oe_contact_forms_corporate_block:' . $contact_form->uuid();

    // Assert the block derivative was not created.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));

    // Now expose the block to pick up the change.
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $contact_form->save();

    // Assert the block derivative was created.
    $this->assertTrue($block_manager->hasDefinition($plugin_id));

    // Now delete the block content entity.
    $contact_form->delete();

    // Assert the block derivative was removed.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));

    // Create a fourth contact form which is not corporate.
    $contact_form = ContactForm::create(['id' => 'oe_contact_form_four']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);
    $contact_form->save();
    $plugin_id = 'oe_contact_forms_corporate_block:' . $contact_form->uuid();

    // Assert the block derivative was not created.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));
  }

}
