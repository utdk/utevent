<?php

namespace Drupal\utevent_content_type_event;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

// @note: You only need Reference, if you want to change service arguments.
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the language manager service.
 */
class UteventContentTypeEventServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides AddToCal Augment class.
    if ($container->hasDefinition('addtocal_augment.addtocal_service')) {
      $definition = $container->getDefinition('addtocal_augment.addtocal_service');
      $definition->setClass('Drupal\utevent_content_type_event\Plugin\DateAugmenter\AddToCal');
    }
  }
}
