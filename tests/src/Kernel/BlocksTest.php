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

    $contact_form = ContactForm::create(['id' => 'oe_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $contact_form->save();

    // Assert the block was created.
    $this->assertTrue($block_manager->hasDefinition('oe_contact_forms_corporate_block:' . $contact_form->uuid()));

    $contact_form = ContactForm::create(['id' => 'oe_contact_form_no_block']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', TRUE);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'expose_as_block', TRUE);
    $contact_form->save();

    // Assert the block was not created.
    $this->assertFalse($block_manager->hasDefinition('oe_contact_forms_corporate_block:' . $contact_form->uuid()));

    $contact_form = ContactForm::create(['id' => 'default_contact_form']);
    $contact_form->setThirdPartySetting('oe_contact_forms', 'is_corporate_form', FALSE);
    $contact_form->save();

    // Assert the block was not created.
    $this->assertFalse($block_manager->hasDefinition('oe_contact_forms_corporate_block:' . $contact_form->uuid()));
  }

}
