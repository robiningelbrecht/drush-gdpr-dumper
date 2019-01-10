<?php

namespace Drupal\gdpr_dumper;

/**
 * Class GdprDumperEnums
 * @package Drupal\gdpr_dumper
 */
final class GdprDumperEnums {

  /**
   * @return array
   */
  public static function fakerFormatters() {
    return [
      'name' => 'Generate a name',
      'phoneNumber' => t('Generate a phone number'),
      'username' =>  t('Generate a random user name'),
      'password' =>  t('Generate a random password'),
      'email' =>  t('Generate a random email address'),
      'date' =>  t('Generate a date'),
      'longText' =>  t('Generate a sentence'),
      'number' =>  t('Generate a number'),
      'randomText' =>  t('Generate a sentence'),
      'text' =>  t('Generate a paragraph'),
      'uri' =>  t('Generate a URI'),
      'clear' =>  t('Generate an empty string'),
    ];
  }

}