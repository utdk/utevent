<?php

/**
 * @file
 * Builds placeholder replacement tokens for utevent-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function utevent_content_type_event_token_info() {
  $type = [
    'name' => t('Event nodes'),
    'description' => t('Tokens related to individual event items provided by the UT Event add-on.'),
    'needs-data' => 'node',
  ];

  // Chained tokens for nodes.
  $node['field_utevent_datetime-start-iso8601'] = [
    'name' => t("Event start date in ISO 8601 format"),
    'type' => 'date',
  ];
  $node['field_utevent_datetime-end-iso8601'] = [
    'name' => t("Event end date in ISO 8601 format"),
    'type' => 'date',
  ];
  return [
    'types' => ['node' => $type],
    'tokens' => ['node' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function utevent_content_type_event_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if (isset($options['langcode'])) {
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = [];
  if ($type == 'node' && !empty($data['node']) && $data['node'] instanceof FieldableEntityInterface && $data['node']->hasField('field_utevent_datetime') && !$data['node']->get('field_utevent_datetime')->isEmpty()) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'field_utevent_datetime-start-iso8601':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($node->get('field_utevent_datetime')->getValue()[0]['value'], 'custom', 'c', NULL, $langcode);
          break;

        case 'field_utevent_datetime-end-iso8601':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($node->get('field_utevent_datetime')->getValue()[0]['end_value'], 'custom', 'c', NULL, $langcode);
          break;

      }
    }
  }

  return $replacements;
}
