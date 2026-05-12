<?php

namespace Drupal\utevent_import\Plugin\Tamper;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\media\Entity\Media;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use GuzzleHttp\Client;

/**
 * Plugin implementation of the create_media_tamper plugin.
 *
 * @Tamper(
 *   id = "create_media_tamper",
 *   label = @Translation("Create Media Tamper"),
 *   description = @Translation("Create a utexas_image media entity from an image URL."),
 *   category = "Other"
 * )
 */
class CreateMedia extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    if (empty($data)) {
      return $data;
    }

    $media_type = 'utexas_image';
    $media_field = 'field_utexas_media_image';
    $items = $item ? $item->getSource() : [];
    $alt = $items['utevent_image_alt_json'] ?? '';
    $title = $items['utevent_image_title_json'] ?? '';
    $file_system = \Drupal::service('file_system');
    $file_name = $this->getFileName($file_system, $data);

    $file = $this->findFile($file_name);
    if (FALSE === $file) {
      $file = $this->writeData($this->getContent($data), 'public://' . $file_name);
    }

    if (!$file) {
      return $data;
    }

    $media = $this->findMedia($file->id(), $media_field);
    if (!$media) {
      $media = Media::create([
        'name' => $file_name,
        'bundle' => $media_type,
        'uid' => 1,
        'langcode' => 'en',
        'status' => 1,
        $media_field => [
          'target_id' => $file->id(),
          'alt' => $alt,
          'title' => $title,
        ],
      ]);
      $media->save();
    }

    return $media->id();
  }

  /**
   * Derive a safe filename from the source URL.
   */
  protected function getFileName($file_system, $url) {
    $filename = trim($file_system->basename($url), " \t\n\r\0\x0B.");
    [$filename] = explode('?', $filename);
    return $filename;
  }

  /**
   * Fetch the remote file contents.
   */
  protected function getContent($url) {
    $client = new Client();
    $response = $client->request('GET', $url);
    if ($response->getStatusCode() >= 400) {
      return FALSE;
    }
    return (string) $response->getBody();
  }

  /**
   * Write file data to the public scheme, reusing an existing file if present.
   */
  protected function writeData($data, $destination) {
    try {
      return \Drupal::service('file.repository')->writeData($data, $destination, FileExists::Rename);
    }
    catch (EntityStorageException | FileException $e) {
      return FALSE;
    }
  }

  /**
   * Look up an existing managed file by filename.
   */
  protected function findFile(string $file_name) {
    $existing = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['filename' => $file_name]);
    return $existing ? reset($existing) : FALSE;
  }

  /**
   * Look up an existing media entity referencing the given file id.
   */
  protected function findMedia($fid, $media_field) {
    $existing = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->loadByProperties([$media_field => $fid]);
    return $existing ? reset($existing) : FALSE;
  }

}
