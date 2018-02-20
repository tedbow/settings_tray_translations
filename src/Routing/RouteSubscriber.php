<?php

namespace Drupal\settings_tray_translations\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.block.settings_tray_form')) {
      $requirements = $route->getRequirements();
      unset($requirements['_access_block_has_overrides_settings_tray_form']);
      $route->setRequirements($requirements );
    }
  }
}
