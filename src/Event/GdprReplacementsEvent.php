<?php

namespace Drupal\gdpr_dumper\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class GdprReplacementsEvent
 * @package Drupal\gdpr_dumper\Event
 */
class GdprReplacementsEvent extends Event {

  /**
   * @var array
   */
  protected $replacements;


  /**
   * GdprReplacementsEvent constructor.
   * @param $replacements
   */
  public function __construct($replacements) {
    $this->replacements = $replacements;
  }

  /**
   * @return array
   */
  public function getReplacements() {
    return $this->replacements;
  }

  /**
   * @param array $replacements
   * @return $this
   */
  public function setReplacements(array $replacements) {
    $this->replacements = $replacements;
    return $this;
  }

}
