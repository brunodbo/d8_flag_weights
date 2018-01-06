<?php

/**
 * @file
 *   Flag weights implementation for draggableviews.
 */

$plugin = array(
    'label' => 'Flag Weights',
    'handler' => array(
        'class' => 'draggableviews_handler_flag_weights',
    ),
);

/**
 * Implementation using the Flag Weight module.
 */
class draggableviews_handler_flag_weights extends draggableviews_handler {
  //iStryker: set the weight to node weight.  I don't know why its 'node' weight, it should be entity weight I think
  //This will probably change when flag weight changes its naming convention
  function get($field, $index) {
    $row = $field->view->result[$index];
    return (isset($row->flagging_node_weight)) ? $row->flagging_node_weight : 0;
  }

  function set($form_state) {
    $user = \Drupal::currentUser(); // assume $extra['uid] = '***CURRENT_USER***'

    // Get the result set from the View.
    $result = isset($form_state['build_info']['args'][0]->result) ? $form_state['build_info']['args'][0]->result : array();

    $fv = $form_state['values'];
    $view = $form_state['build_info']['args'][0];
    $relationship = $view->relationship['flag_content_rel'];
    $fid = $relationship->definition['extra'][0]['value'];
    $flag = flag_get_flag($relationship->options['flag']);

    // For global flags, use uid 0
    $uid = $flag->global ? 0 : $user->uid;

    // Save records to our flags table.
    foreach ($fv['draggableviews'] as $item) {
      // Make sure id is available.
      if (!isset($item['id'])) {
        continue;
      }

      // Find the Entity ID.
      $id = $item['id'];
      if (!empty($result)) {
        foreach ($result as $result_item) {
          $flag_id = isset($result_item->flagging_node_flagging_id) ? $result_item->flagging_node_flagging_id : null;
          if ($item['id'] == $flag_id) {
            $id = $result_item->flagging_node_entity_id;
          }
        }
      }

      // Save the Flag Weight.
      flag_weights_set_weight($fid, $flag->entity_type, $id, $uid, $item['weight']);
    }
  }
}
