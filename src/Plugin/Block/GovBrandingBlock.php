<?php

namespace Drupal\mud_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "mud_core_gov_branding",
 *   admin_label = @Translation("Government Branding"),
 *   category = @Translation("eGov"),
 * )
 */
class GovBrandingBlock extends BlockBase {

  public function build(): array {
    return [
      '#type' => 'component',
      '#component' => 'mud_egov:gov-branding',
      '#props' => [],
    ];
  }

}
