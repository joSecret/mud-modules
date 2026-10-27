<?php

namespace Drupal\mud_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\image\ImageDerivativeUtilities;
use Drupal\image\ImageStyleStorageInterface;
use Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the Thumbnail formatter with inline SVG rendering when the source
 * file is an SVG. Non-SVG files fall back to the standard thumbnail behavior.
 */
#[FieldFormatter(
  id: 'media_svg_inline',
  label: new TranslatableMarkup('Thumbnail (SVG inline)'),
  field_types: ['entity_reference'],
)]
class MediaSvgInlineFormatter extends MediaThumbnailFormatter {

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountInterface $current_user,
    ImageStyleStorageInterface $image_style_storage,
    FileUrlGeneratorInterface $file_url_generator,
    RendererInterface $renderer,
    ImageDerivativeUtilities $imageDerivativeUtilities,
    protected FileSystemInterface $fileSystem,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator, $renderer, $imageDerivativeUtilities);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('file_url_generator'),
      $container->get('renderer'),
      $container->get(ImageDerivativeUtilities::class),
      $container->get('file_system'),
    );
  }

  public static function defaultSettings(): array {
    return [
      'svg_width' => '',
      'svg_height' => '',
    ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['svg_width'] = [
      '#type' => 'number',
      '#title' => $this->t('SVG width (px)'),
      '#default_value' => $this->getSetting('svg_width'),
      '#min' => 0,
      '#description' => $this->t('Applied only when the file is SVG. Leave empty to keep original.'),
    ];

    $elements['svg_height'] = [
      '#type' => 'number',
      '#title' => $this->t('SVG height (px)'),
      '#default_value' => $this->getSetting('svg_height'),
      '#min' => 0,
      '#description' => $this->t('Applied only when the file is SVG. Leave empty to keep original.'),
    ];

    return $elements;
  }

  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $width = $this->getSetting('svg_width');
    $height = $this->getSetting('svg_height');

    if ($width || $height) {
      $summary[] = $this->t('SVG size: @w × @h px', [
        '@w' => $width ?: 'auto',
        '@h' => $height ?: 'auto',
      ]);
    }

    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = parent::viewElements($items, $langcode);
    $media_items = $this->getEntitiesToView($items, $langcode);

    foreach ($media_items as $delta => $media) {
      $file = $this->getMediaSourceFile($media);

      if (!$file instanceof FileInterface || !$this->isSvg($file)) {
        continue;
      }

      $inline = $this->buildInlineSvg($file);
      if (empty($inline)) {
        continue;
      }

      $elements[$delta] = $inline;
      $this->renderer->addCacheableDependency($elements[$delta], $media);
    }

    return $elements;
  }

  private function getMediaSourceFile($media): ?FileInterface {
    $source = $media->getSource();
    $definition = $source->getSourceFieldDefinition($media->bundle->entity);
    if (!$definition) {
      return NULL;
    }

    $field_name = $definition->getName();
    if (!$media->hasField($field_name)) {
      return NULL;
    }

    $file = $media->get($field_name)->entity;
    return $file instanceof FileInterface ? $file : NULL;
  }

  private function isSvg(FileInterface $file): bool {
    return $file->getMimeType() === 'image/svg+xml'
      || strtolower(pathinfo($file->getFileUri(), PATHINFO_EXTENSION)) === 'svg';
  }

  private function buildInlineSvg(FileInterface $file): array {
    $realpath = $this->fileSystem->realpath($file->getFileUri());
    if ($realpath === FALSE) {
      return [];
    }

    $svg = file_get_contents($realpath);
    if ($svg === FALSE) {
      return [];
    }

    $width = $this->getSetting('svg_width');
    $height = $this->getSetting('svg_height');

    if ($width || $height) {
      $svg = $this->setSvgDimensions($svg, $width, $height);
    }

    return ['#markup' => Markup::create($svg)];
  }

  private function setSvgDimensions(string $svg, $width, $height): string {
    $dom = new \DOMDocument();
    @$dom->loadXML($svg);

    $root = $dom->documentElement;
    if (!$root) {
      return $svg;
    }

    if ($width) {
      $root->setAttribute('width', (int) $width);
    }
    if ($height) {
      $root->setAttribute('height', (int) $height);
    }

    return $dom->saveXML($root);
  }

}
