<?php

namespace Drupal\utevent_content_type_event\Plugin\Field\FieldFormatter;

use Drupal\smart_date_recur\Plugin\Field\FieldFormatter\SmartDateRecurrenceFormatter;

/**
 * Plugin for a recurrence-optimized formatter for 'smartdate' fields.
 *
 * This formatter renders the start time range using <time> elements, with
 * recurring dates given special formatting.
 *
 * @FieldFormatter(
 *   id = "utevent_smartdate_recurring",
 *   label = @Translation("UT Event Recurring"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class UtEventSmartDateRecurrenceFormatter extends SmartDateRecurrenceFormatter {
}
