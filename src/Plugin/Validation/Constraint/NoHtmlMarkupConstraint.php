<?php

declare(strict_types=1);

namespace Drupal\oe_contact_forms\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint as ConstraintAttribute;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Prevents HTML markup in plain-text contact message fields.
 *
 * @Constraint(
 *   id = "OeContactFormsNoHtmlMarkup",
 *   label = @Translation("No HTML markup", context = "Validation")
 * )
 */
#[ConstraintAttribute(
  id: 'OeContactFormsNoHtmlMarkup',
  label: new TranslatableMarkup(
    'No HTML markup',
    [],
    ['context' => 'Validation'],
  ),
)]
class NoHtmlMarkupConstraint extends SymfonyConstraint {

  /**
   * Validation error message.
   *
   * @var string
   */
  public string $message = '@name must not contain HTML markup.';

}
