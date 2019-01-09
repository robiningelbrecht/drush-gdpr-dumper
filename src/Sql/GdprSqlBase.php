<?php

namespace Drupal\gdpr_dumper\Sql;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Database;
use Drush\Drush;
use Drush\Sql\SqlBase;

/**
 * Class GdprSqlBase
 * @package Drupal\gdpr_dumper\Sql
 */
class GdprSqlBase extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public static function create($options = []) {
    // Set defaults in the unfortunate event that caller doesn't provide values.
    $options += [
      'database' => 'default',
      'target' => 'default',
      'db-url' => NULL,
      'databases' => NULL,
      'db-prefix' => NULL,
    ];
    $database = $options['database'];
    $target = $options['target'];

    if ($url = $options['db-url']) {
      $url = is_array($url) ? $url[$database] : $url;
      $db_spec = self::dbSpecFromDbUrl($url);
      $db_spec['db_prefix'] = $options['db-prefix'];
      return self::getInstance($db_spec, $options);
    }
    elseif (($databases = $options['databases']) && (array_key_exists($database, $databases)) && (array_key_exists($target, $databases[$database]))) {
      $db_spec = $databases[$database][$target];
      return self::getInstance($db_spec, $options);
    }
    elseif ($info = Database::getConnectionInfo($database)) {
      $db_spec = $info[$target];
      return self::getInstance($db_spec, $options);
    }
    else {
      throw new \Exception(dt('Unable to load Drupal settings. Check your --root, --uri, etc.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getInstance($dbSpec, $options) {
    $driver = $dbSpec['driver'];
    $className = 'Drupal\gdpr_dumper\Sql\GdprSql' . ucfirst($driver);
    // Fetch module settings.
    $config = \Drupal::config('gdpr_dumper.settings');

    if (empty($options['extra-dump']) || strpos($options['extra-dump'], '--gdpr-expressions') === FALSE) {
      // Add the configured GDPR expressions to the command.
      $expressions = Json::encode($config->get('gdpr_expressions'));
     $options['extra-dump'] .= " --gdpr-expressions='$expressions'";
    }

    if (empty($options['extra-dump']) || strpos($options['extra-dump'], '--gdpr-replacements') === FALSE) {
      // Add the configured GDPR replacements to the command.
      $replacements = Json::encode($config->get('gdpr_replacements'));
      $options['extra-dump'] .= " --gdpr-replacements='$replacements'";
    }

    $instance = new $className($dbSpec, $options);
    $driver_options = isset($config->get('drivers')[$driver]) ? $config->get('drivers')[$driver] : [];
    // Inject config
    $instance->setConfig(Drush::config());
    $instance->setGdprExpressions($config->get('gdpr_expressions'));
    $instance->setDriverOptions($driver_options);
    return $instance;
  }

}