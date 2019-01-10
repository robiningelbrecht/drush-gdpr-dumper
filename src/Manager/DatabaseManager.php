<?php

namespace Drupal\gdpr_dumper\Manager;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\InvalidQueryException;

/**
 * Class DatabaseManager
 * @package Drupal\gdpr_dumper\Manager
 */
class DatabaseManager {

  protected $database;

  /**
   * DatabaseManager constructor.
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * @return array
   */
  public function getTableColumns() {
    $tables = $this->database->schema()->findTables('%');
    $columns = [];
    foreach ($tables as $table) {
      $result = $this->getColumns($table);
      if (NULL === $result) {
        continue;
      }
      $columns[$table] = $result->fetchAllAssoc('COLUMN_NAME', \PDO::FETCH_ASSOC);
    }

    return $columns;
  }

  /**
   * @param $table
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function getColumns($table) {
    // @todo: How cross-driver is this?
    $query = $this->database->select('information_schema.columns', 'columns');
    $query->fields('columns', ['COLUMN_NAME', 'DATA_TYPE', 'COLUMN_COMMENT']);
    $query->condition('TABLE_SCHEMA', $this->database->getConnectionOptions()['database']);
    $query->condition('TABLE_NAME', $table);
    return $query->execute();
  }
}