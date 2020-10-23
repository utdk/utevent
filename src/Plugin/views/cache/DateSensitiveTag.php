<?php

namespace Drupal\utevent\Plugin\views\cache;

use Drupal\views\Plugin\views\cache\Tag;

/**
 * Date-sensitive caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 * id = "utevent_date_sensitive_tag",
 * title = @Translation("Date-sensitive tag-based"),
 * help = @Translation("Tag-based caching of date-sensitive data. Caches will be cleared on each new day.")
 * )
 */
class DateSensitiveTag extends Tag {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Date-sensitive tag-based');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags[] = 'date_sensitive';
    return $tags;
  }

}
