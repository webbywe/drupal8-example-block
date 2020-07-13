<?php

namespace Drupal\Tests\block_test_example\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\block\Functional\BlockTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group block_test_example
 */
class ExampleBlockTest extends BlockTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    // Core functions.
    'node',
    'block',
    'test_page_test',

    // This module.
    'block_test_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * THe block configuration to test.
   *
   * @var array
   */
  protected $blockConfig;

  /**
   * The block entities used by this test.
   *
   * @var \Drupal\block\BlockInterface[]
   */
  protected $blocks;

  /**
   * Do not check configurations to prevent error on form save.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;


  /**
   * The block configuration for testing.
   *
   * @var array
   */
  protected $blockValues = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the main block value for testing.
    $this->blockValues = [
      'id' => 'exampletestblock',
      'region' => 'content',
      'settings[label_display]' => FALSE,
      'settings[label]' => $this->randomMachineName(8),
      'settings[title]' => 'Example Block',
      'settings[no_results_message]' => 'There are no updated content for today.',
      'settings[how_many_hours_to_cache]' => 1,
      'settings[how_many_to_show]' => 0,
    ];
  }

  /**
   * Create basic pages.
   */
  protected function createPages() {
    // Get the default timezone for Drupal.
    $tz = \Drupal::config('system.date')->get('timezone')['default'];

    $date = new \DateTime('now', new \DateTimeZone($tz));
    $today = $date->format('U');

    $pages = [
      [
        'title' => 'Page - today 1',
        'time' => $today,
        'publish' => TRUE,
      ],
      [
        'title' => 'Page - today 2',
        'time' => $today,
        'publish' => TRUE,
      ],
      [
        'title' => 'Page - today 3',
        'time' => $today,
        'publish' => TRUE,
      ],
      [
        'title' => 'Page - today 4',
        'time' => $today,
        'publish' => TRUE,
      ],
      [
        'title' => 'Page - yesterday',
        'time' => strtotime('-1 day', $today),
        'publish' => TRUE,
      ],
      [
        'title' => 'Page - tomorrow 1',
        'time' => strtotime('-1 day', $today),
        'publish' => TRUE,
      ],
      [
        'title' => 'Page - today unpublished',
        'time' => strtotime('-1 day', $today),
        'publish' => FALSE,
      ],
    ];

    if (!NodeType::load('page')) {
      // Create a "Camelids" node type.
      NodeType::create([
        'name' => 'pages',
        'type' => 'page',
      ])->save();
    }

    foreach ($pages as $page) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = Node::create(['type' => 'page']);
      $node->setTitle($page['title'])
        ->setOwnerId(1)
        ->setCreatedTime($page['time'])
        ->setChangedTime($page['time'])
        ->setRevisionCreationTime($page['time'])
        ->setPublished()
        ->save();
    }
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testSiteLoads() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the block can be placed and loads.
   */
  public function testBLockPlacement() {
    $plugin_id = 'example_block';
    $block_url = 'admin/structure/block/add/' . $plugin_id . '/' . $this->config('system.theme')->get('default');
    $this->drupalGet($block_url);

    // Assert configuration values are present on Block form.
    $form_fields = [
      'settings[title]',
      'settings[no_results_message]',
      'settings[how_many_hours_to_cache]',
      'settings[how_many_to_show]',
    ];

    foreach ($form_fields as $field) {
      $this->assertSession()->fieldExists($field);
    }

    // Save the block form.
    $this->drupalPostForm(NULL, $this->blockValues, t('Save block'));

    // Assure no errors on page after saving.
    $this->assertSession()->statusCodeEquals(200);

    // Go to the block listing and assure it was placed.
    $this->drupalGet('admin/structure/block/list/' . $this->config('system.theme')->get('default'));
    $this->assertSession()->pageTextContains($this->blockConfig['settings[label]']);
  }

  /**
   * Assert that only nodes for current day show.
   */
  public function testBlockResults() {
    $plugin_id = 'example_block';
    $block_url = 'admin/structure/block/add/' . $plugin_id . '/' . $this->config('system.theme')->get('default');
    $this->drupalPostForm($block_url, $this->blockValues, t('Save block'));

    // Load front page and check results.
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->blockValues['settings[no_results_message]']);

    $this->createPages();

    // Make sure no errors on page now that block is placed.
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);

    // Check for title of the block.
    $this->assertSession()->pageTextContains($this->blockValues['settings[title]']);

    // Assure the pages for today are present.
    $this->assertSession()->pageTextContains('Page - today 1');
    $this->assertSession()->pageTextContains('Page - today 2');
    $this->assertSession()->pageTextContains('Page - today 3');
    $this->assertSession()->pageTextContains('Page - today 4');

    // Assure the other pages are not present.
    $this->assertSession()->pageTextNotContains('Page - yesterday');
    $this->assertSession()->pageTextNotContains('Page - tomorrow 1');
    $this->assertSession()->pageTextNotContains('Page - today unpublished');

    // Edit the range to only have 3.
    $edit_url = 'admin/structure/block/manage/' . $this->blockValues['id'];
    $edit_data = $this->blockValues;
    $edit_data['settings[how_many_to_show]'] = 3;
    $this->drupalPostForm($edit_url, $edit_data, t('Save block'));

    // Assure block out of range don't show.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('Page - today 4');
  }

}
