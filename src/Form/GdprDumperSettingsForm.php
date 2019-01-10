<?php

namespace Drupal\gdpr_dumper\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gdpr_dumper\GdprDumperEnums;
use Drupal\gdpr_dumper\Manager\DatabaseManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GdprDumperSettingsForm
 * @package Drupal\gdpr_dumper\Form
 */
class GdprDumperSettingsForm extends ConfigFormBase {

  protected $settings;
  protected $connection;
  protected $databaseManager;

  /**
   * GdprDumperSettingsForm constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\gdpr_dumper\Manager\DatabaseManager $database_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, DatabaseManager $database_manager) {
    parent::__construct($config_factory);
    $this->connection = $connection;
    $this->databaseManager = $database_manager;
    $this->settings = $this->config('gdpr_dumper.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('gdpr_dumper.database_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_dumper_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gdpr_dumper.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $replacements = $this->settings->get('gdpr_replacements');
    $database_tables = $this->databaseManager->getTableColumns();
    $db_schema = $this->connection->schema();
    $schema_handles_db_comments = \is_callable([$db_schema, 'getComment']);

    $form['intro'] = [
      '#type' => 'item',
      '#title' => $this->t('Manage tables and columns that contain sensitive data'),
    ];

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['replacements'] = [
      '#tree' => TRUE,
    ];

    foreach ($replacements as $table => $columns) {
      if (isset($database_tables[$table])) {
        $form['replacements'][$table] = [
          '#type' => 'details',
          '#title' => $table,
          '#group' => 'advanced',
        ];

        $form['replacements'][$table]['columns'] = [
          '#type' => 'table',
          '#header' => [
            ['data' => $this->t('Field')],
            ['data' => $this->t('Type')],
            ['data' => $this->t('Description')],
            ['data' => $this->t('Apply anonymization')],
          ],
          // @todo: attach this in JS.
          //'#title' => $schema_handles_db_comments ? $db_schema->getComment($table) : NULL,
        ];

        foreach ($columns as $column => $row) {
          if (isset($database_tables[$table][$column])) {
            $column_info = $database_tables[$table][$column];

            $form['replacements'][$table]['columns'][$column]['field'] = [
              '#markup' => '<strong>' . $column_info['COLUMN_NAME'] . '</strong>',
            ];
            $form['replacements'][$table]['columns'][$column]['data_type'] = [
              '#markup' => '<strong>' . $column_info['DATA_TYPE'] . '</strong>',
            ];
            $form['replacements'][$table]['columns'][$column]['comment'] = [
              '#markup' => '<strong>' . (empty($column_info['COLUMN_COMMENT']) ? '-' : $column_info['COLUMN_COMMENT']) . '</strong>',
            ];
            $form['replacements'][$table]['columns'][$column]['anonymization'] = [
              '#type' => 'select',
              '#title' => $this->t('Apply anonymization'),
              '#title_display' => 'invisible',
              '#options' => GdprDumperEnums::fakerFormatters(),
              '#empty_value' => '',
              '#empty_option' => $this->t('- No -'),
              '#required' => FALSE,
              '#default_value' => $row['formatter'],
            ];
          }
        }

        $form['replacements'][$table]['empty'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Empty this table'),
          '#button_type' => 'secondary',
        ];
      }

      $form['actions']['add_table'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add table'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    dpm($form_state->getValues());
    parent::submitForm($form, $form_state);
  }

}