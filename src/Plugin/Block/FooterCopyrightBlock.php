<?php

namespace Drupal\mud_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Block(
 *   id = "mud_core_footer_copyright",
 *   admin_label = @Translation("Footer Copyright"),
 *   category = @Translation("eGov"),
 * )
 */
class FooterCopyrightBlock extends BlockBase {

  public function defaultConfiguration(): array {
    return [
      'text' => 'Toate drepturile rezervate.',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright text'),
      '#default_value' => $this->configuration['text'],
      '#required' => TRUE,
    ];
    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['text'] = $form_state->getValue('text');
  }

  public function build(): array {
    return [
      '#type' => 'component',
      '#component' => 'mud_core:footer_copyright',
      '#props' => [
        'year' => (int) date('Y'),
        'text' => $this->configuration['text'],
      ],
    ];
  }

}
