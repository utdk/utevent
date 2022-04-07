<?php

namespace Drupal\utevent_content_type_event\Plugin\DateAugmenter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\date_augmenter\DateAugmenter\DateAugmenterPluginBase;
use Drupal\date_augmenter\Plugin\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Date Augmenter plugin to inject Add to Calendar links.
 *
 * @DateAugmenter(
 *   id = "addtocal",
 *   label = @Translation("Add To Calendar Links"),
 *   description = @Translation("Adds links to add an events dates to a user's preferred calendar."),
 *   weight = 0
 * )
 */
class AddToCal extends DateAugmenterPluginBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  use PluginFormTrait;

  /**
   * Unused property.
   *
   * @var mixed
   */
  protected $processService;
  /**
   * Unused property.
   *
   * @var mixed
   */
  protected $config;
  /**
   * Unused property.
   *
   * @var mixed
   */
  protected $output;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $plugin->setStringTranslation($translation);
    return $plugin;
  }

  /**
   * Builds and returns a render array for the task.
   *
   * @param array $output
   *   The existing render array, to be augmented, passed by reference.
   * @param Drupal\Core\Datetime\DrupalDateTime $start
   *   The object which contains the start time.
   * @param Drupal\Core\Datetime\DrupalDateTime $end
   *   The optionalobject which contains the end time.
   * @param array $options
   *   An array of options to further guide output.
   */
  public function augmentOutput(array &$output, DrupalDateTime $start, DrupalDateTime $end = NULL, array $options = []) {
    if ($data = $this->buildLinks($output, $start, $end, $options)) {
      $config = $options['settings'] ?? $this->getConfiguration();
      $ical_render = implode('%0D%0A', $data['ical']);
      $google_base = 'https://www.google.com/calendar/r/eventedit?';
      $google_render = $google_base . http_build_query($data['google']);
      // Generate a unique ID for DOM targeting purposes.
      $id = Html::getUniqueId($data['google']['text']);
      $output['addtocal'] = [
        '#theme' => 'addtocal_links',
        '#label' => $config['label'] ?? $this->t('Add to Calendar'),
        '#google' => $google_render,
        '#ical' => $ical_render,
        '#id' => $id,
      ];
      if (isset($config['target']) && ($config['target'] === 'modal')) {
        $output['addtocal']['#theme'] = 'addtocal_links__modal';
        $output['addtocal']['#attached']['library'][] = 'core/drupal.dialog.ajax';
        $output['addtocal']['#attached']['library'][] = 'addtocal_augment/modal';
      }
    }
  }

  /**
   * This call to DrupalDateTime is isolated for overrding in Unit testing.
   *
   * @see Drupal\Tests\addtocal_augment\Unit\TestAddToCal
   */
  protected function getCurrentDate() {
    return new DrupalDateTime();
  }

  /**
   * Builds a prepared array of data for output.
   *
   * @param array $output
   *   The existing render array, to be augmented.
   * @param Drupal\Core\Datetime\DrupalDateTime $start
   *   The object which contains the start time.
   * @param Drupal\Core\Datetime\DrupalDateTime $end
   *   The optionalobject which contains the end time.
   * @param array $options
   *   An array of options to further guide output.
   */
  public function buildLinks(array $output, DrupalDateTime $start, DrupalDateTime $end = NULL, array $options = []) {
    // Use provided settings if they exist, otherwise look for plugin config.
    $config = $options['settings'] ?? $this->getConfiguration();
    if (empty($config['title']) && !isset($options['entity'])) {
      // @todo log some kind of warning that we can't work without the entity
      // or a provided title?
      return;
    }
    $end_fallback = $end ?? $start;

    $now = $this->getCurrentDate();
    // For a recurring date, determine if the last instance is in the past.
    $upcoming_instance = FALSE;
    // @todo Validate that if set, $options['ends'] is DrupalDateTime.
    if (!empty($options['repeats']) && (empty($options['ends']) || $options['ends'] > $now)) {
      $upcoming_instance = TRUE;
    }
    if (!$upcoming_instance && $end_fallback < $now && !$config['past_events']) {
      return;
    }
    $entity = $options['entity'] ?? NULL;
    if (!$end) {
      $end = $start;
    }
    if ($start instanceof DrupalDateTime && $tz = $start->getTimezone()) {
      $timezone = $tz->getName();
    }
    else {
      $tz = $this->configFactory->get('system.date')->get('timezone');
      $timezone = $tz['default'];
    }
    if (isset($options['allday']) && $options['allday']) {
      $start_formatted = $start->format("Ymd", $timezone);
      // Offset the end by one day for calendar ingestion.
      $end->add(new \DateInterval('P1D'));
      $end_formatted = $end->format("Ymd", $timezone);
      $prefix = ':';
    }
    else {
      $date_format = "Ymd\\THi00";
      if ($timezone) {
        $prefix = ';TZID=' . $timezone . ':';
      }
      else {
        $date_format .= "\\Z";
        $prefix = ':';
      }
      $start_formatted = $start->format($date_format, $timezone);
      $end_formatted = $end->format($date_format, $timezone);
    }
    if (!empty($config['title'])) {
      $label = $this->parseField($config['title'], $entity);
    }
    else {
      $label = $this->parseField($entity->label(), FALSE);
    }
    $description = NULL;
    if (!empty($config['description'])) {
      $description = $this->parseField($config['description'], $entity, TRUE);
      $max_length = isset($config['max_desc']) ? $config['max_desc'] : 60;
      if ($max_length) {
        // @todo Use Smart Trim if available.
        // @todo Make the use of ellipsis configurable?
        $description = trim(substr($description, 0, $max_length)) . '...';
      }
    }
    $location = NULL;
    if (!empty($config['location'])) {
      $location = $this->parseField($config['location'], $entity, TRUE);
    }
    $uuid = $entity->uuid() ?? Html::getUniqueId($label);

    // Build output.
    $ical_link = ['data:text/calendar;charset=utf8,BEGIN:VCALENDAR'];
    $ical_link[] = 'PRODID:' . $this->configFactory->get('system.site')->get('name');
    if ($timezone) {
      $offset_from = $start->format('O', $timezone);
      $offset_to = $end->format('O', $timezone);

      // Timezone must precede VEVENT in iCal format
      // per icalendar.org/iCalendar-RFC-5545/3-6-5-time-zone-component.html .
      $google_link['ctz'] = $timezone;

    }
    $ical_link[] = 'VERSION:2.0';
    $ical_link[] = 'BEGIN:VEVENT';
    $ical_link[] = 'UID:' . $uuid;

    // Title.
    $ical_link[] = 'SUMMARY:' . $label;
    $google_link['text'] = $label;

    // Dates.
    // As per RFC 2445 4.8.7.2 the DTSTAMP property must be in UTC.
    $now->setTimezone(new \DateTimeZone('UTC'));

    // Set start/end dates timezone to UTC.
    $start->setTimezone(new \DateTimeZone('UTC'));
    $end->setTimezone(new \DateTimeZone('UTC'));

    $ical_link[] = 'DTSTAMP:' . $now->format('Ymd\\THi00\\Z');

    // Use UTC date format.
    $ical_link[] = 'DTSTART:' . $start->format('Ymd\\THi00\\Z');
    $ical_link[] = 'DTEND:' . $end->format('Ymd\\THi00\\Z');

    $google_link['dates'] = $start_formatted . '/' . $end_formatted;

    // Recurrence.
    if (!empty($options['repeats'])) {
      $ical_link[] = '' . $options['repeats'];
      $google_link['recur'] = $options['repeats'];
    }

    // Description.
    if ($description) {
      $ical_link[] = 'DESCRIPTION:' . $description;
      $google_link['details'] = $description;
    }

    // Location.
    if ($location) {
      $ical_link[] = 'LOCATION:' . $location;
      $google_link['location'] = $location;
    }
    $ical_link[] = 'END:VEVENT';
    $ical_link[] = 'END:VCALENDAR';
    return [
      'ical' => $ical_link,
      'google' => $google_link,
    ];
  }

  /**
   * Manipulate the provided value, checking for tokens and cleaning up.
   *
   * @param string $field_value
   *   The value to manipulate.
   * @param mixed $entity
   *   The entity whose values can be used to replace tokens.
   * @param bool $strip_markup
   *   Whether or not to clean up the output.
   *
   * @return string
   *   The manipulated value, prepared for use in a link href.
   */
  public function parseField($field_value, $entity, $strip_markup = FALSE) {
    if (\Drupal::hasService('token') && $entity) {
      $token_service = \Drupal::service('token');
      $token_data = [
        $entity->getEntityTypeId() => $entity,
      ];
      $field_value = $token_service->replace($field_value, $token_data);
    }
    if ($strip_markup) {
      // Strip tags. Requires decoding entities, which will be re-encoded later.
      $field_value = strip_tags(html_entity_decode($field_value));

      // Strip out line breaks.
      $field_value = preg_replace('/\n|\r|\t/m', ' ', $field_value);

      // Strip out non-breaking spaces.
      $field_value = str_replace('&nbsp;', ' ', $field_value);
      $field_value = str_replace("\xc2\xa0", ' ', $field_value);

      // Strip out extra spaces.
      $field_value = trim(preg_replace('/\s\s+/', ' ', $field_value));
    }
    return $field_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'event_title' => '',
      'location' => '',
      'description' => '',
      'max_desc' => 60,
      'past_events' => FALSE,
      'label' => $this->t('Add to Calendar'),
      'target' => '',
    ];
  }

  /**
   * Create configuration fields for the plugin form, or injected directly.
   *
   * @param array $form
   *   The form array.
   * @param array|null $settings
   *   The setting to use as defaults.
   * @param mixed $field_definition
   *   A parameter to define the field being modified. Likely FieldConfig.
   *
   * @return array
   *   The updated form array.
   */
  public function configurationFields(array $form, ?array $settings, $field_definition = "") {
    if (empty($settings)) {
      $settings = $this->defaultConfiguration();
    }
    $form['label'] = [
      '#title' => $this->t('Links label'),
      '#type' => 'textfield',
      '#default_value' => $settings['label'],
      '#description' => $this->t('Text to prefix the actual add links.'),
    ];

    $form['event_title'] = [
      '#title' => $this->t('Event title'),
      '#type' => 'textfield',
      '#default_value' => $settings['event_title'],
      '#description' => $this->t('Optional - if left empty, the entity label will be used. You can use static text or tokens.'),
    ];

    $form['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#description' => $this->t('Optional. You can use static text or tokens.'),
      '#default_value' => $settings['location'],
    ];

    $form['description'] = [
      '#title' => $this->t('Event description'),
      '#type' => 'textarea',
      '#default_value' => $settings['description'],
      '#description' => $this->t('Optional. You can use static text or tokens.'),
    ];

    $form['max_desc'] = [
      '#title' => $this->t('Maximum description length'),
      '#type' => 'number',
      '#default_value' => isset($settings['max_desc']) ? $settings['max_desc'] : 60,
      '#description' => $this->t('Trim the desctiption to a specified length. Leave empty or use zero to not trim the value.'),
    ];

    $form['past_events'] = [
      '#title' => $this->t('Show Add to Cal widget for past events?'),
      '#type' => 'checkbox',
      '#default_value' => $settings['past_events'],
    ];

    $form['target'] = [
      '#title' => $this->t('Links display'),
      '#description' => $this->t('Display as a list of links or as a single link that triggers a modal (pop-up).'),
      '#type' => 'select',
      '#default_value' => $settings['target'] ?? '',
      '#options' => [
        '' => $this->t('List of links'),
        'modal' => $this->t('Modal dialog'),
      ],
    ];

    if (function_exists('token_theme')) {
      $type = NULL;
      if (method_exists($field_definition, 'getTargetEntityTypeId')) {
        $type = $field_definition->getTargetEntityTypeId();
      }
      // @todo support other field types?
      $form['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$type],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->configurationFields($form, $this->configuration);

    return $form;
  }

}
