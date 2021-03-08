<?php

namespace Drupal\utevent_view_listing_page\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\utevent\Form\BaseConfigurationForm;

/**
 * Supplement form UI to add setting for which blocks & layouts are available.
 */
class ListingPageConfig extends BaseConfigurationForm {

  /**
   * The actual form elements.
   */
  public function alterForm(&$form, FormStateInterface $form_state, $form_id) {
    $default_title = $this->configFactory->get('utevent_view_listing_page.config')->get('page_title');
    $form['page_title'] = [
      '#title' => $this->t('Event listing page title'),
      '#type' => 'textfield',
      '#default_value' => $default_title ?? 'Events',
      '#description' => $this->t('Displayed on <a href=":link">:link</a>.', [
        ':link' => \Drupal::request()->getSchemeAndHttpHost() . '/events',
      ]),
    ];

    $form['#submit'][] = [$this, 'submitListingConfig'];
  }

  /**
   * Extended submit callback.
   */
  public function submitListingConfig(&$form, FormStateInterface $form_state) {
    $page_title = $form_state->getValue('page_title');
    if ($page_title) {
      $config = $this->configFactory->getEditable('utevent_view_listing_page.config');
      $config->set('page_title', $page_title);
      $config->save();
      // Ensure that this change invalidates the view cache.
      Cache::invalidateTags(['config:views.view.utevent_listing_page']);
    }
  }

}
