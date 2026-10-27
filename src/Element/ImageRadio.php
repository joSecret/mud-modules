<?php

namespace Drupal\mud_core\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Render\Markup;

/**
 * Form element: radio list with image previews instead of text labels.
 *
 * Usage:
 * @code
 * $form['variant'] = [
 *   '#type' => 'image_radio',
 *   '#title' => t('Choose variant'),
 *   '#options' => [
 *     'value1' => ['label' => 'Standard', 'icon' => '/path/to/icon.svg'],
 *     'value2' => ['label' => 'Dropdown', 'icon' => '/path/to/icon2.svg'],
 *   ],
 *   '#default_value' => 'value1',
 * ];
 * @endcode
 */
#[FormElement('image_radio')]
class ImageRadio extends Radios {

  public function getInfo(): array {
    $info = parent::getInfo();
    $info['#attached']['library'][] = 'mud_core/image-radio';
    $info['#attributes']['class'][] = 'image-radio';
    return $info;
  }

  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form): array {
    if (!empty($element['#options'])) {
      foreach ($element['#options'] as $key => $option) {
        if (is_array($option)) {
          $icon_html = '';
          if (!empty($option['icon'])) {
            $icon_html = '<span class="image-radio__icon"><img src="' . Html::escape($option['icon']) . '" alt=""></span>';
          }
          $label_html = '<span class="image-radio__label">' . Html::escape($option['label'] ?? '') . '</span>';
          $element['#options'][$key] = Markup::create($icon_html . $label_html);
        }
      }
    }

    return parent::processRadios($element, $form_state, $complete_form);
  }

}
