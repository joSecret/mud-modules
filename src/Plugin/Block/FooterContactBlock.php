<?php

namespace Drupal\mud_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;

/**
 * @Block(
 *   id = "mud_core_footer_contact",
 *   admin_label = @Translation("Footer Contact"),
 *   category = @Translation("eGov"),
 * )
 */
class FooterContactBlock extends BlockBase {

  public function defaultConfiguration(): array {
    return [
      'logo_media' => NULL,
      'phone' => '',
      'email' => '',
      'address' => '',
      'menu_name' => '',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $image_media = $media_storage->loadByProperties(['bundle' => 'image', 'status' => 1]);
    $logo_options = ['' => $this->t('- None (use site logo) -')];
    foreach ($image_media as $id => $media) {
      $logo_options[$id] = $media->label();
    }

    $form['logo_media'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo'),
      '#description' => $this->t('Pick an existing media item. <a href=":url" target="_blank">Browse / add media</a>.', [
        ':url' => '/admin/content/media',
      ]),
      '#options' => $logo_options,
      '#default_value' => $this->configuration['logo_media'],
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [static::class, 'ajaxLogoPreview'],
        'wrapper' => 'mud-logo-preview',
        'event' => 'change',
      ],
    ];
    $complete_fs = $form_state->getCompleteFormState();
    $selected_mid = $complete_fs->getValue(['settings', 'logo_media']) ?? $this->configuration['logo_media'];
    $form['logo_preview'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'mud-logo-preview'],
      'preview' => $this->buildLogoPreview($selected_mid),
    ];
    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => $this->configuration['phone'],
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $this->configuration['email'],
    ];
    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#rows' => 3,
      '#default_value' => $this->configuration['address'],
    ];

    $menus = \Drupal::entityTypeManager()->getStorage('menu')->loadMultiple();
    $menu_options = ['' => $this->t('- None -')];
    foreach ($menus as $id => $menu) {
      $menu_options[$id] = $menu->label();
    }
    $form['menu_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#options' => $menu_options,
      '#default_value' => $this->configuration['menu_name'],
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    $logo = $form_state->getValue('logo_media');
    $this->configuration['logo_media'] = $logo ? (int) $logo : NULL;
    $this->configuration['phone'] = $form_state->getValue('phone');
    $this->configuration['email'] = $form_state->getValue('email');
    $this->configuration['address'] = $form_state->getValue('address');
    $this->configuration['menu_name'] = $form_state->getValue('menu_name');
  }

  public function build(): array {
    [$logo_src, $logo_alt] = $this->resolveLogo();

    $slots = [];
    if (!empty($this->configuration['menu_name'])) {
      $menu_tree = \Drupal::menuTree();
      $menu_name = $this->configuration['menu_name'];
      $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
      $tree = $menu_tree->load($menu_name, $parameters);
      $tree = $menu_tree->transform($tree, [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ]);
      $built = $menu_tree->build($tree);
      if (!empty($built['#items'])) {
        $slots['menu'] = $built;
      }
    }

    return [
      '#type' => 'component',
      '#component' => 'mud_egov:footer-contact',
      '#props' => [
        'footer_contact_logo_src' => $logo_src,
        'footer_contact_logo_alt' => $logo_alt,
        'footer_contact_logo_url' => '/',
        'footer_contact_phone' => $this->configuration['phone'],
        'footer_contact_email' => $this->configuration['email'],
        'footer_contact_address' => $this->configuration['address'],
      ],
      '#slots' => $slots,
    ];
  }

  public static function ajaxLogoPreview(array &$form, FormStateInterface $form_state): array {
    return $form['settings']['logo_preview'];
  }

  private function buildLogoPreview($mid): array {
    if (!$mid) {
      return ['#markup' => ''];
    }
    $media = Media::load((int) $mid);
    if (!$media || !$media->hasField('field_media_image') || $media->get('field_media_image')->isEmpty()) {
      return ['#markup' => ''];
    }
    $file = $media->get('field_media_image')->entity;
    if (!$file) {
      return ['#markup' => ''];
    }
    $url = \Drupal::service('file_url_generator')->generateString($file->getFileUri());
    return [
      '#type' => 'inline_template',
      '#template' => '<div class="mud-logo-preview__box" style="margin-top:8px;padding:8px;border:1px solid #ccc;max-width:200px;"><img src="{{ url }}" alt="{{ alt }}" style="max-width:100%;height:auto;display:block;"></div>',
      '#context' => [
        'url' => $url,
        'alt' => $media->label(),
      ],
    ];
  }

  private function resolveLogo(): array {
    $mid = $this->configuration['logo_media'] ?? NULL;
    if ($mid) {
      $media = Media::load($mid);
      if ($media && $media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
        $item = $media->get('field_media_image')->first();
        $file = $item->entity ?? NULL;
        if ($file) {
          $src = \Drupal::service('file_url_generator')->generateString($file->getFileUri());
          $alt = $item->alt ?: $media->label();
          return [$src, $alt];
        }
      }
    }

    $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    $src = theme_get_setting('logo.url', $theme) ?: NULL;
    return [$src, NULL];
  }

}
