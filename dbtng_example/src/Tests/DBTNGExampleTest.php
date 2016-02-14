<?php

/**
 * @file
 * Contains \Drupal\dbtng_example\Tests\DBTNGExampleTest.
 */

namespace Drupal\dbtng_example\Tests;

use Drupal\dbtng_example\DBTNGExampleStorage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for the dbtng_example module.
 *
 * @group dbtng_example
 * @group examples
 *
 * @ingroup dbtng_example
 */
class DBTNGExampleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dbtng_example');

  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Regression test for dbtng_example.
   *
   * We'll verify the following:
   * - Assert that two entries were inserted at install.
   * - Test the example description page.
   * - Verify that the example pages have links in the Tools menu.
   */
  public function testDBTNGExample() {
    // Assert that two entries were inserted at install.
    $result = DBTNGExampleStorage::load();
    $this->assertEqual(
      count($result), 2, 'Found two entries in the table after installing the module.'
    );

    // Test the example description page.
    $this->drupalGet('/examples/dbtng_example');
    $this->assertResponse(200, 'Description page exists.');

    // Verify and validate that default menu links were loaded for this module.
    $links = $this->providerMenuLinks();
    foreach ($links as $page => $hrefs) {
      foreach ($hrefs as $href) {
        $this->drupalGet($page);
        $this->assertLinkByHref($href);
      }
    }
  }

  /**
   * Data provider for testing menu links.
   *
   * @return array
   *   Array of page -> link relationships to check for:
   *   - The key is the path to the page where our link should appear.
   *   - The value is an array of links that should appear on that page.
   */
  protected function providerMenuLinks() {
    return array(
      '' => array(
        '/examples/dbtng_example',
      ),
      '/examples/dbtng_example' => array(
        '/examples/dbtng_example/add',
        '/examples/dbtng_example/update',
        '/examples/dbtng_example/advanced',
      ),
    );
  }

  /**
   * Test the UI.
   */
  public function testUI() {
    // @todo: remove the need to have a logged-in user.
    $this->drupalLogin($this->createUser());
    // Test the basic list.
    $this->drupalGet('/examples/dbtng_example');
    $this->assertPattern("/John[td\/<>\w\s]+Doe/", "Text 'John Doe' found in table");

    // Test the add tab.
    // Add the new entry.
    $this->drupalPostForm(
      '/examples/dbtng_example/add',
      array(
        'name' => 'Some',
        'surname' => 'Anonymous',
        'age' => 33,
      ),
      t('Add')
    );
    // Now find the new entry.
    $this->drupalGet('/examples/dbtng_example');
    $this->assertPattern("/Some[td\/<>\w\s]+Anonymous/", "Text 'Some Anonymous' found in table");
    // Try the update tab.
    // Find out the pid of our "anonymous" guy.
    $result = DBTNGExampleStorage::load(array('surname' => 'Anonymous'));
    $this->drupalGet('/examples/dbtng_example');
    $this->assertEqual(
      count($result), 1, 'Found one entry in the table with surname = "Anonymous".'
    );
    $entry = $result[0];
    unset($entry->uid);
    $entry->name = 'NewFirstName';
    $this->drupalPostForm('/examples/dbtng_example/update', (array) $entry, t('Update'));
    // Now find the new entry.
    $this->drupalGet('/examples/dbtng_example');
    $this->assertPattern("/NewFirstName[td\/<>\w\s]+Anonymous/", "Text 'NewFirstName Anonymous' found in table");

    // Try the advanced tab.
    $this->drupalGet('/examples/dbtng_example/advanced');
    $rows = $this->xpath("//*[@id='dbtng-example-advanced-list'][1]/tbody/tr");
    $this->assertEqual(count($rows), 1, 'One row found in advanced view');
    $this->assertFieldByXPath("//*[@id='dbtng-example-advanced-list'][1]/tbody/tr/td[4]", "Roe", "Name 'Roe' Exists in advanced list");
  }

  /**
   * Tests several combinations, adding entries, updating and deleting.
   */
  public function testDBTNGExampleStorage() {
    // Create a new entry.
    $entry = array(
      'name' => 'James',
      'surname' => 'Doe',
      'age' => 23,
    );
    DBTNGExampleStorage::insert($entry);

    // Save another entry.
    $entry = array(
      'name' => 'Jane',
      'surname' => 'NotDoe',
      'age' => 19,
    );
    DBTNGExampleStorage::insert($entry);

    // Verify that 4 records are found in the database.
    $result = DBTNGExampleStorage::load();
    $this->assertEqual(
      count($result), 4, 'Found a total of four entries in the table after creating two additional entries.'
    );

    // Verify 2 of these records have 'Doe' as surname.
    $result = DBTNGExampleStorage::load(array('surname' => 'Doe'));
    $this->assertEqual(
      count($result), 2, 'Found two entries in the table with surname = "Doe".'
    );

    // Now find our not-Doe entry.
    $result = DBTNGExampleStorage::load(array('surname' => 'NotDoe'));
    $this->assertEqual(
      count($result), 1, 'Found one entry in the table with surname "NotDoe'
    );
    // Our NotDoe will be changed to "NowDoe".
    $entry = $result[0];
    $entry->surname = "NowDoe";
    // update() returns the number of entries updated.
    $this->assertNotEqual(DBTNGExampleStorage::update((array) $entry), 0, "NotDoe updated to NowDoe.");

    $result = DBTNGExampleStorage::load(array('surname' => 'NowDoe'));
    $this->assertEqual(
      count($result), 1, "Found renamed 'NowDoe' surname");

    // Read only John Doe entry.
    $result = DBTNGExampleStorage::load(array('name' => 'John', 'surname' => 'Doe'));
    $this->assertEqual(
      count($result), 1, 'Found one entry for John Doe.'
    );
    // Get the entry.
    $entry = (array) end($result);
    // Change age to 45.
    $entry['age'] = 45;
    // Update entry in database.
    DBTNGExampleStorage::update((array) $entry);

    // Find entries with age = 45.
    // Read only John Doe entry.
    $result = DBTNGExampleStorage::load(array('surname' => 'NowDoe'));
    $this->assertEqual(
      count($result), 1, 'Found one entry with surname = Nowdoe.'
    );

    // Verify it is Jane NowDoe.
    $entry = (array) end($result);
    $this->assertEqual(
      $entry['name'], 'Jane', 'The name Jane is found in the entry'
    );
    $this->assertEqual(
      $entry['surname'], 'NowDoe', 'The surname NowDoe is found in the entry'
    );

    // Delete the entry.
    DBTNGExampleStorage::delete($entry);

    // Verify that now there are only 3 records.
    $result = DBTNGExampleStorage::load();
    $this->assertEqual(
      count($result), 3, 'Found only three records, a record was deleted.'
    );
  }

}
