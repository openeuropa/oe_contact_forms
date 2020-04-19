<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms;

use Drupal\contact\MessageForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for contact message forms.
 *
 * @internal
 */
class ContactMessageForm extends MessageForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['privacy_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Privacy policy'),
    ];

    return $form;
  }

}
