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
    $empty_tables = $this->settings->get('empty_tables');
    // Add the empty table options to the replacement array, if they don't exist already.
    // We need this because if only the "Empty table" option is checked,
    // the table won't be available in the replacements array.
    foreach (array_filter($empty_tables) as $empty_table => $value) {
      if (!isset($replacements[$empty_table])) {
        $replacements[$empty_table] = [];
      }
    }


    $database_tables = $this->databaseManager->getTableColumns();
    $db_schema = $this->connection->schema();
    $schema_handles_db_comments = \is_callable([$db_schema, 'getComment']);

    $form['intro'] = [
      '#type' => 'item',
      '#title' => $this->t('Manage tables and columns that contain sensitive data'),
    ];

    $tables_to_add = array_diff(array_keys($database_tables), array_keys($replacements));

    $form['table'] = [
      '#type' => 'select',
      '#title' => $this->t('Select table'),
      '#title_display' => 'invisible',
      '#options' => array_combine($tables_to_add, $tables_to_add),
      '#empty_value' => '',
      '#empty_option' => $this->t('- Select -'),
    ];

    $form['add_table'] = [
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Add table'),
          '#submit' => [
            [$this, 'submitAddTable']
          ],
        ],
      ],
    ];

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['replacements'] = [
      '#tree' => TRUE,
      '#attached' => [
        'library' => ['gdpr_dumper/settings-form']
      ],
    ];

    foreach ($replacements as $table => $columns) {
      if (isset($database_tables[$table])) {
        $table_summary = $schema_handles_db_comments ? $db_schema->getComment($table) : '-';
        $form['replacements'][$table] = [
          '#type' => 'details',
          '#title' => $table,
          '#group' => 'advanced',
          '#attributes' => [
            'data-table-summary' => $table_summary,
          ],
        ];

        $form['replacements'][$table]['columns'] = [
          '#type' => 'table',
          '#caption' => $table_summary,
          '#header' => [
            ['data' => $this->t('Field')],
            ['data' => $this->t('Type')],
            ['data' => $this->t('Description')],
            ['data' => $this->t('Apply anonymization')],
          ],
        ];

        foreach ($database_tables[$table] as $column_name => $column_properties) {
          $form['replacements'][$table]['columns'][$column_name]['field'] = [
            '#markup' => '<strong>' . $column_properties['COLUMN_NAME'] . '</strong>',
          ];
          $form['replacements'][$table]['columns'][$column_name]['data_type'] = [
            '#markup' => '<strong>' . $column_properties['DATA_TYPE'] . '</strong>',
          ];
          $form['replacements'][$table]['columns'][$column_name]['comment'] = [
            '#markup' => '<strong>' . (empty($column_properties['COLUMN_COMMENT']) ? '-' : $column_properties['COLUMN_COMMENT']) . '</strong>',
          ];
          $form['replacements'][$table]['columns'][$column_name]['anonymization'] = [
            '#type' => 'select',
            '#title' => $this->t('Apply anonymization'),
            '#title_display' => 'invisible',
            '#options' => GdprDumperEnums::fakerFormatters(),
            '#empty_value' => '',
            '#empty_option' => $this->t('- No -'),
            '#required' => FALSE,
            '#default_value' => isset($replacements[$table][$column_name]['formatter']) ? $replacements[$table][$column_name]['formatter'] : FALSE,
          ];
        }

        $form['replacements'][$table]['empty'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Empty this table'),
          '#button_type' => 'secondary',
          '#default_value' => isset($empty_tables[$table]) ? $empty_tables[$table] : FALSE,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit callback to add a table to the list.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitAddTable(array &$form, FormStateInterface $form_state) {
    if ($table = $form_state->getValue('table')) {
      $replacements = $this->settings->get('gdpr_replacements');

      if (!isset($replacements[$table])) {
        $replacements[$table] = [];
      }

      // Order tables alphabetically before saving.
      ksort($replacements);

      // Update config.
      $this->settings->set('gdpr_replacements', $replacements)->save();
      $this->messenger()
        ->addStatus($this->t('The table has been added. You can configure it by selecting the corresponding tab.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = [
      'gdpr_replacements' => [],
      'empty_tables' => [],
    ];

    $replacements = $form_state->getValue('replacements');
    // Format the replacement to a suitable config array.
    foreach ($replacements as $table_name => $properties) {
      foreach ($properties['columns'] as $column_name => $column) {
        if (!empty($column['anonymization'])) {
          $settings['gdpr_replacements'][$table_name][$column_name]['formatter'] = $column['anonymization'];
        }
      };
      $settings['empty_tables'][$table_name] = $properties['empty'];
    }

    // Save settings.
    $this->settings->set('gdpr_replacements', $settings['gdpr_replacements'])
      ->set('empty_tables', $settings['empty_tables'])->save();

    parent::submitForm($form, $form_state);
  }

}