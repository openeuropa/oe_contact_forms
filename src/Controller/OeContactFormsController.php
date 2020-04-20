<?php

declare(strict_types = 1);

namespace Drupal\oe_contact_forms\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\contact_storage\Controller\ContactStorageController;

/**
 * Controller routines for contact storage routes.
 */
class OeContactFormsController extends ContactStorageController {

  /**
   * Callback for topics add more.
   *
   * @param string $delta
   *   The current field group delta.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns an array of AJAX Commands.
   */
  public function addOneCallback($delta = 0): AjaxResponse {
    $elements['topics_fieldset']['group'] = [
      '#type' => 'fieldgroup',
    ];
    $elements['topics_fieldset']['group'][$delta]['topic_name'] = [
      '#type' => 'textfield',
      '#title' => t('Topic name'),
      '#states' => [
        'required' => [
          ':input[name="is_corporate_form"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['topics_fieldset']['group'][$delta]['topic_email_address'] = [
      '#type' => 'textfield',
      '#title' => t('Topic email address(es)'),
      '#states' => [
        'required' => [
          ':input[name="is_corporate_form"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['topics_fieldset']['ajax_target'] = [
      '#markup' => '<div id="topics-fieldset-wrapper"></div>',
    ];

    $renderer = \Drupal::service('renderer');
    $renderedField = $renderer->render($elements);
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand('#topics-fieldset-wrapper', $renderedField));

    return $response;
  }

}
