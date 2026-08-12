<?php

declare(strict_types=1);

namespace Drupal\oe_contact_forms\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that plain-text field values contain no HTML markup.
 */
class NoHtmlMarkupConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    if (!$constraint instanceof NoHtmlMarkupConstraint) {
      throw new UnexpectedTypeException($constraint, NoHtmlMarkupConstraint::class);
    }

    if (!$items instanceof FieldItemListInterface) {
      return;
    }

    $field_label = (string) $items->getFieldDefinition()->getLabel();

    foreach ($items as $delta => $item) {
      $value = $item->value ?? NULL;

      if (!is_string($value) || $value === '' || !$this->containsHtmlMarkup($value)) {
        continue;
      }

      $this->context
        ->buildViolation($constraint->message)
        ->setParameter('@name', $field_label)
        ->atPath((string) $delta)
        ->addViolation();
    }
  }

  /**
   * Determines whether a value contains HTML markup.
   */
  private function containsHtmlMarkup(string $value): bool {
    $decoded = $value;

    for ($depth = 0; $depth < 10; $depth++) {
      $next = Html::decodeEntities($decoded);

      if ($next === $decoded) {
        break;
      }

      $decoded = $next;
    }

    // Fail closed if the decoding depth limit was exhausted while the value
    // is still changing.
    if (Html::decodeEntities($decoded) !== $decoded) {
      return TRUE;
    }

    return preg_match(
      '/<(?:\/?[A-Za-z][A-Za-z0-9:-]*[^<>]*>|!--|![A-Za-z]|\?)/s',
      $decoded,
    ) === 1;
  }

}
