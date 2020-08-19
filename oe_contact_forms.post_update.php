<?php

/**
 * @file
 * Post update functions for OpenEuropa Contact Forms module.
 */

declare(strict_types = 1);

/**
 * Enable the corporate countries component.
 */
function oe_contact_forms_post_update_00001(): void {
  \Drupal::service('module_installer')->install(['oe_corporate_countries']);
  \Drupal::service('kernel')->invalidateContainer();
}
