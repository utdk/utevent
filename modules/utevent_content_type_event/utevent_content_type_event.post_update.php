<?php

/**
 * @file
 * Post-update functions for UTEvent content type.
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Configure XMLSitemap settings.
 */
function utevent_content_type_event_post_update_configure_xmlsitemap() {
  if (\Drupal::moduleHandler()->moduleExists('xmlsitemap') !== FALSE) {
    if (\Drupal::config('xmlsitemap.settings.node.utevent_event')->get('status') === NULL) {
      $config = \Drupal::configFactory()->getEditable('xmlsitemap.settings.node.utevent_event');
      $config_path = drupal_get_path('module', 'utevent_content_type_event') . '/config/install/xmlsitemap.settings.node.utevent_event.yml';
      if (!empty($config_path)) {
        $data = Yaml::parse(file_get_contents($config_path));
        if (is_array($data)) {
          $config->setData($data)->save(TRUE);
        }
      }
    } else {
      $message = dt('XML Sitemap configuration object "xmlsitemap.settings.node.utevent_event" found. No action taken.');
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('utexas')->notice($message);
    }
  }
}
