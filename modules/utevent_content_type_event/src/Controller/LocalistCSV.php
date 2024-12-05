<?php

namespace Drupal\utevent_content_type_event\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

/**
 * Localist CSV endpoint.
 *
 * @package Drupal\utevent_content_type_event\Controller
 */
class LocalistCSV extends ControllerBase {

  /**
   * The Controller endpoint.
   */
  public static function endpoint(Request $request) {
    $rows = ['foo', 'bar','baz'];
    $content = implode("\n", $rows);
    $response = new Response($content);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition','attachment; filename="sample.csv"');
    return $response;
  }
}
