<?php

namespace Drupal\block_test_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Behat\Mink\Exception\Exception;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ExampleBlock' block.
 *
 * @Block(
 *  id = "example_block",
 *  admin_label = @Translation("Example block"),
 * )
 */
class ExampleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'title' => 'There are no updated content for today.',
        'no_results_message' => 'There are no updated content for today.',
        'how_many_hours_to_cache' => 12,
        'how_many_to_show' => 0,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['title'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Title for the results.'),
      '#description'   => $this->t('Enter a tilte to show in the block container (uncheck "Display title" when placing if entered).'),
      '#default_value' => $this->configuration['title'],
    ];

    $form['no_results_message'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Message for no results'),
      '#description'   => $this->t('Enter a brief message to show for when there are no results.'),
      '#default_value' => $this->configuration['no_results_message'],
      '#required'      => 1,
    ];

    $form['how_many_hours_to_cache'] = [
      '#type'          => 'select',
      '#title'         => $this->t('How many hours to cache?'),
      '#description'   => $this->t('How many hours should the block be refreshed with data.'),
      '#options'       => [
        '1'  => '1',
        '6'  => '6',
        '12' => '12',
        '24' => '24',
        '36' => '36',
        '48' => '48',
      ],
      '#default_value' => $this->configuration['how_many_hours_to_cache'],
      '#size'          => 1,
    ];

    $form['how_many_to_show'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('How many results to show?'),
      '#description'   => $this->t('Enter 0 to show all results.'),
      '#default_value' => $this->configuration['how_many_to_show'],
      '#required'      => 1,
    ];

    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);

    if (!is_numeric($form_state->getValue('how_many_to_show'))) {
      $form_state->setErrorByName('how_many_to_show', 'Must be a numeric value.');
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['no_results_message'] = $form_state->getValue('no_results_message');
    $this->configuration['how_many_to_show'] = (int) $form_state->getValue('how_many_to_show');
    $this->configuration['how_many_hours_to_cache'] = (int) $form_state->getValue('how_many_hours_to_cache');
  }

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    return $instance;
  }

  /**
   * Lazy load the content so the block can refresh with content.
   *
   * {@inheritdoc}
   */
  public function build() {
    $content = [];
    $content['example_block'] = [
      '#lazy_builder'       => [
        static::class . '::lazyLoadContent',
        [
          $this->configuration['title'],
          $this->configuration['no_results_message'],
          (int) $this->configuration['how_many_to_show'],
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
    $content['#markup'] = '';
    return $content;
  }

  /**
   * @param string $title
   *   The title of the block container.
   * @param string $no_results
   *   The message if no results.
   * @param int $limit
   *   How many to show in list.
   *
   * @return array
   *   A render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function lazyLoadContent(string $title, string $no_results, int $limit): array {
    // Get the query.
    $node_storage = \Drupal::service('entity_type.manager')->getStorage('node');
    $query = $node_storage->getQuery();

    // Get the default timezone for Drupal.
    $tz = \Drupal::config('system.date')->get('timezone')['default'];

    // Build the condition to only get nodes updated today.
    $startOfDay = strtotime("today", strtotime('now' . ' ' . $tz));
    $endOfDay = strtotime("tomorrow", $startOfDay) - 1;

    // Set the conditions to query the nodes.
    $and = $query->andConditionGroup();
    $and
      ->condition('changed', $startOfDay, '>')
      ->condition('changed', $endOfDay, '<');

    $query
      ->condition($and)
      ->condition('status', TRUE);

    // If a limit is set, set a range for the query.
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    // Get the nids that match criteria.
    $nids = $query->execute();

    // If nodes are founds, load them all at once for performance.
    $nodes = [];
    if (is_array($nids) && count($nids)) {
      try {
        $nodes = $node_storage->loadMultiple($nids);
      } catch (Exception $exception) {
        $module_name = basename(__FILE__, '.module');
        \Drupal::logger($module_name)->error(sprintf('<pre>%s</pre>', print_r($exception, TRUE)));
      }
    }

    // Return the rendered array.
    return [
      '#theme'              => 'block_test_example',
      '#title'              => $title,
      '#no_results_message' => $no_results,
      '#content'            => $nodes,
    ];
  }

}
