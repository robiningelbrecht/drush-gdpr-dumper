<?php

namespace Drupal\gdpr_dumper\Sql;

use Drupal\Console\Core\Utils\DrupalFinder;

/**
 * Trait GdprSqlTrait
 * @package Drupal\gdpr_dumper\Sql
 */
trait GdprSqlTrait {

  protected $gdprExpressions;
  protected $driverOptions;

  /**
   * {@inheritdoc}
   */
  public function dumpCmd($table_selection) {
    $cmd = parent::dumpCmd($table_selection);

    $drupal_finder = new DrupalFinder();
    $drupal_finder->locateRoot(DRUPAL_ROOT);

    if ($vendor = $drupal_finder->getVendorDir() && isset($this->driverOptions['dump_command'])) {
      // Replace default dump command with the GDPR compliant one.
      $cmd = str_replace($this->driverOptions['dump_command'], $drupal_finder->getVendorDir() . '/bin/mysqldump', $cmd);
    }

    return $cmd;
  }

  /**
   * @return array
   */
  public function getGdprExpressions() {
    return $this->gdprExpressions;
  }

  /**
   * @param array $expressions
   */
  public function setGdprExpressions(array $expressions) {
    $this->gdprExpressions = $expressions;
  }

  /**
   * @return array
   */
  public function getDriverOptions() {
    return $this->driverOptions;
  }

  /**
   * @param array $options
   */
  public function setDriverOptions(array $options) {
    $this->driverOptions = $options;
  }
}