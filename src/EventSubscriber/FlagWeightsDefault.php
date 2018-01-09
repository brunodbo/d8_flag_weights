<?php

/**
 * @file
 * Contains \Drupal\flag_weights\EventSubscriber\FlagWeightsDefault.
 * Sets the default flag_weights value when a flagging is created.
 * @todo: Add an option to turn on/off flag_weights functionality on a per flag
 * basis, so this only runs for relevant flags.
 */

namespace Drupal\flag_weights\EventSubscriber;

use Drupal\Core\Database\Database;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;

class FlagWeightsDefault implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    return $events;
  }

  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    $flag = $event->getFlagging()->getFlag();
    $flag_id = $flag->id();
    $flag_global = $flag->isGlobal();
    $entity_id = $flagging->getFlaggable()->id();

    $default_weight = _flag_weights_get_default_weight($flag_id);
    $db_connection = Database::getConnection();

    // If the configured default weight is MIN/MAX then set it to the right int.
    if ($default_weight == MIN_DEFAULT_WEIGHT) {
      if ($flag_global) {
        $found_min = $db_connection->query("SELECT min(weight) FROM {flagging} WHERE flag_id = :flag_id", [':flag_id' => $flag_id])->fetchField();
      }
      else {
        $found_min = $db_connection->query("SELECT min(weight) FROM {flagging} WHERE flag_id = :flag_id AND uid = :uid", [':flag_id' => $flag_id, ':uid' => $account->uid])->fetchField();
      }

      if ($found_min !== FALSE && $found_min > MIN_DEFAULT_WEIGHT) {
        $default_weight = $found_min - 1;
      }
    }
    elseif ($default_weight == MAX_DEFAULT_WEIGHT) {
      if ($flag_global) {
        $found_max = $db_connection->query("SELECT max(weight) FROM {flagging} WHERE flag_id = :flag_id", [':flag_id' => $flag_id])->fetchField();
      }
      else {
        $found_max = $db_connection->query("SELECT max(weight) FROM {flagging} WHERE flag_id = :flag_id AND uid = :uid", [':flag_id' => $flag_id, ':uid' => $account->uid])->fetchField();
      }
      if ($found_max !== FALSE && $found_max < MAX_DEFAULT_WEIGHT) {
        $default_weight = $found_max + 1;
      }
    }

    // Don't bother applying a weight of 0 - this is the default.
    if ($default_weight != 0) {
      $db_connection->update('flagging')
        ->fields([
          'weight' => $default_weight,
        ])
        ->condition('flag_id', $flag_id)
        ->condition('entity_id', $entity_id)
        ->execute();
    }
  }

}
