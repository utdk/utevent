<?php

namespace Drupal\Tests\utevent\FunctionalJavascript;

use Drupal\Core\Language\Language;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\utevent\Permissions as UteventPermissions;
use Drupal\utexas\Permissions as UtexasPermissions;

/**
 * Test all aspects of Event CRUD functionality.
 *
 * @group utexas
 */
class BasicUteventTest extends WebDriverTestBase {

  use TestFileCreationTrait;
  use NodeCreationTrait;
  use CKEditor5TestTrait;

  /**
   * Use the 'utexas' installation profile.
   *
   * @var string
   */
  protected $profile = 'utexas';

  /**
   * Tests must specify what theme will be used.
   *
   * @var string
   */
  protected $defaultTheme = 'forty_acres';

  /**
   * Modules to enable.
   *
   * @var array
   *
   * @see Drupal\Tests\BrowserTestBase
   */
  protected static $modules = [
    'utevent',
    'utevent_content_type_event',
    'utevent_view_listing_page',
    'utevent_vocabulary_location',
    'utevent_vocabulary_tags',
    'utevent_overrides',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->strictConfigSchema = NULL;
    parent::setUp();
    // Create a test media item.
    $this->testMediaImageId = $this->createTestMediaImage();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->testMediaImageFilename = $this->entityTypeManager->getStorage('media')
      ->load($this->testMediaImageId)
      ->get('field_utexas_media_image')
      ->entity
      ->getFileName();
    // Create a content editor user with all necessary permissions.
    $this->user = $this->drupalCreateUser();
    $this->user->addRole('utexas_content_editor');
    $this->user->save();
    UtexasPermissions::assignPermissions('editor', 'utexas_content_editor');
    UteventPermissions::assignPermissions('editor', 'utexas_content_editor');
  }

  /**
   * Test that Event content be created, viewed, edited, and deleted.
   */
  public function testUtevent() {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $utevent = \Drupal::service('extension.list.module')->getPath('utevent');
    // Enlarge the viewport so that everything is clickable.
    $this->getSession()->resizeWindow(1200, 3000);

    $this->drupalLogin($this->user);

    // Add an event location term.
    $this->drupalGet('/admin/structure/taxonomy/manage/utevent_location/add');
    $page->fillField('name[0][value]', 'Event location test');
    $page->pressButton('Save');

    // Add an event tag.
    $this->drupalGet('/admin/structure/taxonomy/manage/utevent_tags/add');
    $page->fillField('name[0][value]', 'Event tag test');
    $page->pressButton('Save');

    // Navigate to node edit screen.
    $this->drupalGet('node/add/utevent_event');

    // Add field values.
    $page->fillField('title[0][value]', 'Test Event 1');
    $page->fillField('field_utevent_datetime[0][time_wrapper][value][date]', '07-31-2023');
    $page->fillField('field_utevent_datetime[0][time_wrapper][value][time]', '17:00:00');
    $page->fillField('field_utevent_datetime[0][time_wrapper][end_value][date]', '07-31-2023');
    $page->fillField('field_utevent_datetime[0][time_wrapper][end_value][time]', '18:00:00');

    // Access media library.
    $page->pressButton('edit-field-utevent-main-media-open-button');
    // Wait for media library to load.
    sleep(10);
    $this->assertTrue($assert->waitForText('Add or select media', 20000));
    // Select the test media item ("Image 1" with file name "test-image.png").
    $assert->elementExists('css', 'img[src*="' . $this->testMediaImageFilename . '"]')->click();
    $assert->elementExists('css', '.ui-dialog-buttonset')->pressButton('Insert selected');
    // Wait for media library to close.
    $this->assertTrue($assert->waitForElementRemoved('css', '.ui-dialog-title'));

    $page->fillField('field_utevent_body[0][summary]', 'Summary text here');

    // Populate CKEditor field.
    $text = "<p>Pellentesque tristique senectus <strong>et netus</strong> et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p><ul><li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li><li>Aliquam tincidunt mauris eu risus.</li><li>Vestibulum auctor dapibus neque.</li></ul>";
    $this->fillCkeditorField('.form-item--field-utevent-body-0-value', $text);

    // Populate other fields on edit below.
    // Create the node.
    $page->pressButton('Save');

    $this->drupalLogout();
    $this->drupalGet('/events/test-event-1');
    // Wait for image derivative to be built.
    sleep(5);
    $screenshot1 = '1-node-view.png';
    $this->createScreenshot($screenshot1);
    // Perform a visual regression test of the node display.
    // (The screenshot generated during the test run will be in the web/ dir).
    $this->assertTrue($this->filesAreEqual($utevent . '/tests/fixtures/' . $screenshot1, getcwd() . '/' . $screenshot1), "The screenshot in web/$screenshot1 should match the baseline in tests/fixtures/$screenshot1");

    // Make a change to the event and verify the node can be saved and
    // that the change is reflected in the output by another visual regression
    // test.
    $this->drupalLogin($this->user);
    $this->drupalGet('/node/1/edit');

    $page->fillField('field_utevent_display_media[value]', '0');
    $page->fillField('field_utevent_location[target_id]', 'Event location test');
    $page->fillField('field_utevent_tags[target_id]', 'Event tag test');
    $page->fillField('field_utevent_status', 'EventMovedOnline');
    $page->fillField('field_utevent_featured[value]', '1');

    $page->pressButton('Save');

    $this->drupalLogout();
    $this->drupalGet('/events/test-event-1');
    // Wait for image derivative to be built.
    sleep(5);
    $screenshot2 = '2-node-view.png';
    $this->createScreenshot($screenshot2);
    // Perform a visual regression test of the node display.
    // (The screenshot generated during the test run will be in the web/ dir).
    $this->assertTrue($this->filesAreEqual($utevent . '/tests/fixtures/' . $screenshot2, getcwd() . '/' . $screenshot2), "The screenshot in web/$screenshot2 should match the baseline in tests/fixtures/$screenshot2");

    // Perform a visual regression test of /events.
    $this->drupalGet('/events');
    // Wait for image derivative to be built.
    sleep(5);
    $screenshot3 = 'events-list.png';
    $this->createScreenshot($screenshot3);
    // Perform a visual regression test of the node display.
    // (The screenshot generated during the test run will be in the web/ dir).
    $this->assertTrue($this->filesAreEqual($utevent . '/tests/fixtures/' . $screenshot3, getcwd() . '/' . $screenshot3), "The screenshot in web/$screenshot3 should match the baseline in tests/fixtures/$screenshot3");

    // Perform a visual regression test of /past-events.
    $this->drupalGet('/past-events');
    $screenshot4 = 'past-events-list.png';
    $this->createScreenshot($screenshot4);
    // Perform a visual regression test of the node display.
    // (The screenshot generated during the test run will be in the web/ dir).
    $this->assertTrue($this->filesAreEqual($utevent . '/tests/fixtures/' . $screenshot4, getcwd() . '/' . $screenshot4), "The screenshot in web/$screenshot4 should match the baseline in tests/fixtures/$screenshot4");

    // Confirm that an event node can be deleted from the system.
    $this->drupalLogin($this->user);
    $this->drupalGet('/node/1/delete');
    $this->assertTrue($assert->waitForText('Are you sure you want to delete the content item Test Event 1?'));
    $page->pressButton('Delete');
    $this->assertTrue($assert->waitForText('The Event Test Event 1 has been deleted.'));

  }

  /**
   * Set the value of a complex CKEditor enabled field.
   *
   * @param string $target
   *   The html name of the field that implements the editor.
   * @param string $value
   *   The value to enter into the field.
   */
  protected function fillCkeditorField($target, $value) {
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $editor = "$target .ck-editor__editable";
    $session = $this->getSession();
    $ckeditor_javascript = "
    (function (){
        var domEditableElement = document.querySelector(\"$editor\");
        if (domEditableElement.ckeditorInstance) {
          const editorInstance = domEditableElement.ckeditorInstance;
          if (editorInstance) {
            editorInstance.setData(\"$value\");
          } else {
            throw new Exception('Could not get the editor instance!');
          }
        } else {
          throw new Exception('Could not find the element!');
        }
      }());";
    $session->executeScript($ckeditor_javascript);
  }

  /**
   * Creates a test image in Drupal and returns the media MID.
   *
   * @return string
   *   The MID.
   */
  protected function createTestMediaImage() {
    $images = $this->getTestFiles('image');
    // Create a File entity for the initial image. The zeroth element is a PNG.
    $file = File::create([
      'uri' => $images[0]->uri,
      'uid' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();
    $image_media = Media::create([
      'name' => 'Image 1',
      'bundle' => 'utexas_image',
      'uid' => '1',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'status' => '1',
      'field_utexas_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'Test Alt Text',
        'title' => 'Test Title Text',
      ],
    ]);
    $image_media->save();
    return $image_media->id();
  }

  /**
   * Check if two files are identical.
   *
   * @param string $a
   *   A valid path to a file.
   * @param string $b
   *   A valid path to a file.
   *
   * @return bool
   *   Whether or not the files are identical.
   */
  protected function filesAreEqual($a, $b) {
    // Check if filesize is different.
    if (filesize($a) !== filesize($b)) {
      return FALSE;
    }
    // Check if content is different.
    $ah = fopen($a, 'rb');
    $bh = fopen($b, 'rb');
    $result = TRUE;
    while (!feof($ah)) {
      if (fread($ah, 8192) != fread($bh, 8192)) {
        $result = FALSE;
        break;
      }
    }
    fclose($ah);
    fclose($bh);
    return $result;
  }

}
