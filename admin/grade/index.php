<?php
/*
 *  webpa-lti - WebPA module to add LTI support
 *  Copyright (C) 2020  Stephen P Vickers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: stephen@spvsoftwareproducts.com
 */

###
###  Page to allow grades for an assessment to be passed to the platform
###

use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\Outcome;

require_once('../../includes.php');

if (file_exists(DOC__ROOT . 'includes/classes/class_simple_object_iterator.php')) {
    require_once(DOC__ROOT . 'includes/classes/class_simple_object_iterator.php');
} else {

    class SimpleObjectIterator extends \WebPA\includes\classes\SimpleObjectIterator
    {

    }

}
if (file_exists(DOC__ROOT . 'includes/functions/lib_array_functions.php')) {
    require_once(DOC__ROOT . 'includes/functions/lib_array_functions.php');
}
if (file_exists(DOC__ROOT . 'includes/classes/class_assessment.php')) {
    require_once(DOC__ROOT . 'includes/classes/class_assessment.php');
} else {

    class Assessment extends \WebPA\includes\classes\Assessment
    {

    }

}
if (file_exists(DOC__ROOT . 'includes/classes/class_algorithm_factory.php')) {
    require_once(DOC__ROOT . 'includes/classes/class_algorithm_factory.php');
} else {

    class AlgorithmFactory extends \WebPA\includes\classes\AlgorithmFactory
    {

    }

    class ResultHandler extends \WebPA\includes\classes\ResultHandler
    {

    }

}

#
### Option only available for tutors
#
if (!check_user($_user, APP__USER_TYPE_TUTOR)) {
    header('Location:' . APP__WWW . '/logout.php?msg=denied');
    exit;
}
#
### Set the page information
#
$UI->page_title = APP__NAME . " manage grades";
$UI->menu_selected = 'transfer grades';
$UI->breadcrumbs = array('home' => '../../../../tutors/', 'manage grades' => null);
$UI->help_link = '?q=node/237';
#
### Display page
#
$UI->head();
$UI->body();
$UI->content_start();
?>
<div class="content_box">
  <?php
  $resource_link = ResourceLink::fromPlatform($lti_platform, $_module_code);
  if ($resource_link->hasOutcomesService()) {
      $assessment_id = lti_fetch_GET('a');
      $marking_date = lti_fetch_GET('md');
      if ($assessment_id && $marking_date) {
          $ok = true;
          $assessment = new Assessment($DB);
          if (!$assessment->load($assessment_id)) {
              $ok = false;
              error_log('Error: The requested assessment could not be loaded.');
          }
          if ($ok) {
              $marking_params = $assessment->get_marking_params($marking_date);
              if (!$marking_params) {
                  $ok = false;
                  error_log('Error: The requested marksheet could not be loaded.');
              }
          }
          if ($ok) {
              $groups_and_marks = $assessment->get_group_marks();
              $algorithm = AlgorithmFactory::get_algorithm($marking_params['algorithm']);
              if (!$algorithm) {
                  $ok = false;
                  error_log('Error: The requested algorithm could not be loaded.');
              }
          }
          if ($ok) {
              $algorithm->set_grade_ordinals($ordinal_scale);
              $algorithm->set_assessment($assessment);
              $algorithm->set_marking_params($marking_params);
              $algorithm->calculate();
              $grades = $algorithm->get_grades();
              $users = $CIS->get_user(array_keys($grades));
              $user_grades = array();
              foreach ($users as $user) {
                  $user_grades[$user['username']] = $grades[$user['user_id']];
              }
              $lti_users = $resource_link->getUserResultSourcedIDs();
              $sent = 0;
              $errors = 0;
              foreach ($lti_users as $lti_user) {
                  $outcome = new Outcome();
                  if (isset($user_grades[$lti_user->getId()])) {
                      $sent++;
                      $outcome->setValue($user_grades[$lti_user->getId()]);
                      if ($marking_params['grading'] == 'grade_af') {
                          $outcome->type = 'letterafplus';
                      } else {
                          $outcome->type = 'percentage';
                      }
                      $outcome->status = 'final';
                      $err = !$resource_link->doOutcomesService(ResourceLink::EXT_WRITE, $outcome, $lti_user);
                      if ($err) {
                          $errors++;
                      }
                  }
              }
#
### Update time of synchronisation
#
              $settings = unserialize($resource_link->getSetting('ext_ims_lti_tool_setting'));
              $date = date('j F Y \a\t g:i a');
              $settings['grades'] = $date;
              $settings['assessment'] = $assessment_id;
              $settings['mark_sheet'] = $marking_date;
              $resource_link->doSettingService(ResourceLink::EXT_WRITE, serialize($settings));
#
### Set result message
#
              $msg = "{$sent} grade";
              if ($sent != 1) {
                  $msg .= 's';
              }
              $msg .= " sent, {$errors} error";
              if ($errors != 1) {
                  $msg .= 's';
              }
              $msg .= ' reported';
              echo "<div class=\"success_box\">Grades transferred for <em>{$assessment->name}</em> ({$msg}).</div>\n";
          } else {
              ?>

              <p>
                Sorry an error occurred in processing your request.
              </p>

              <?php
          }
      } else {
          ?>

          <p>
            This page allows you to update the source for this module with grades from a <?php echo APP__NAME; ?>
            assessment.  Only one grade book column is associated with this module, so if there are multiple,
            marked assessments you will need to select one of them for this task.
          </p>

          <?php
#
### Get the assessments that are closed and have been marked
#
          $now = date(MYSQL_DATETIME_FORMAT);
          $sql = 'SELECT DISTINCT a.* FROM ' . APP__DB_TABLE_PREFIX . 'assessment a ' .
              'LEFT JOIN ' . APP__DB_TABLE_PREFIX . 'assessment_marking am ON a.assessment_id = am.assessment_id ' .
              'WHERE a.module_id = ? AND a.close_date < ? AND am.assessment_id IS NOT NULL ' .
              'ORDER BY a.open_date, a.close_date, a.assessment_name';
          $stmt = lti_getConnection()->prepare($sql);
          $stmt->bind_param('is', $_module_id, $now);
          $stmt->execute();
          $result = $stmt->get_result();
          $assessments = $result->fetch_all(MYSQLI_ASSOC);
#
### Get details of last grade synchronisation
#
          $settings = unserialize($resource_link->getSetting('ext_ims_lti_tool_setting'));
          if (isset($settings['grades'])) {
              $date = $settings['grades'];
          }
          $assessment_id = '';
          $marking_date = '';
          if (isset($settings['assessment']) && (count($assessments) > 0)) {
              $id = $settings['assessment'];
              foreach ($assessments as $assessment) {
                  if ($assessment['assessment_id'] == $id) {
                      $assessment_id = $id;
                      $assessment_name = $assessment['assessment_name'];
                      $marking_date = $settings['mark_sheet'];
                      break;
                  }
              }
          }
          if (isset($date) && $assessment_id) {
              echo "<p>\n";
              echo "<strong>Last update:</strong> {$date} with assessment <em>{$assessment_name}</em>.\n";
              echo "</p>\n";
          }

          if (!$assessments) {
              ?>
              <p>You do not have any completed, marked assessments.</p>
              <?php
          } else {
              ?>
              <div class="obj_list">
                <?php
#
### Get response counts for each assessment
#
                $result_handler = new ResultHandler($DB);
                $responses = $result_handler->get_responses_count_for_user($_user->id);
                $members = $result_handler->get_members_count_for_user($_user->id);
#
### Display assessments
#
                $assessment_iterator = new SimpleObjectIterator($assessments, 'Assessment', $DB);
                for ($assessment_iterator->reset(); $assessment_iterator->is_valid(); $assessment_iterator->next()) {
                    $assessment = & $assessment_iterator->current();
                    $assessment->set_db($DB);
                    $num_responses = (array_key_exists($assessment->id, $responses)) ? $responses[$assessment->id] : 0;
                    $num_members = (array_key_exists($assessment->id, $members)) ? $members[$assessment->id] : 0;
                    $completed_msg = ($num_responses == $num_members) ? '- <strong>COMPLETED</strong>' : '';

                    $mark_sheets = $assessment->get_all_marking_params();
                    ?>
                    <div class="obj">
                      <table class="obj" cellpadding="2" cellspacing="2">
                        <tr>
                          <td class="icon" width="24"><img src="../../../../images/icons/finished_icon.gif" alt="Finished" title="Finished" height="24" width="24" /></td>
                          <td class="obj_info">
                            <div class="obj_name"><?php echo($assessment->name); ?></div>
                            <div class="obj_info_text">scheduled: <?php echo($assessment->get_date_string('open_date')); ?> &nbsp;-&nbsp; <?php echo($assessment->get_date_string('close_date')); ?></div>
                            <div class="obj_info_text">student responses: <?php echo("$num_responses / $num_members $completed_msg"); ?></div>
                          </td>
                        </tr>
                      </table>
                      <?php
                      if ($mark_sheets) {
                          foreach ($mark_sheets as $date_created => $params) {
                              $date_created = strtotime($date_created);
                              if (($assessment->id == $assessment_id) && ($date_created == $marking_date)) {
                                  $action = 'Update';
                              } else {
                                  $action = 'Replace';
                              }

                              $reports_url = "../../../../tutors/assessments/reports/report_student_grades.php?t=view&a={$assessment->id}&md={$date_created}";
                              $send_url = "index.php?a={$assessment->id}&md={$date_created}";

                              $algorithm = $params['algorithm'];
                              $penalty_type = ($params['penalty_type'] == 'pp') ? ' pp' : '%';   // Add a space to the 'pp'.
                              $tolerance = ($params['tolerance'] == 0) ? 'N/A' : "+/- {$params['tolerance']}%";
                              $grading = ($params['grading'] == 'grade_af') ? 'A-F' : 'Numeric (%)';

                              echo('    <div class="mark_sheet">');
                              echo('      <table class="mark_sheet_info" cellpadding="0" cellspacing="0">');
                              echo('      <tr>');
                              echo('        <td>');
                              echo('          <div class="mark_sheet_title">Mark Sheet</div>');
                              echo("          <div class=\"info\" style=\"font-weight: bold;\">Algorithm: {$algorithm}.</div>");
                              echo("          <div class=\"info\">PA weighting: {$params['weighting']}%</div>");
                              echo("          <div class=\"info\">Non-completion penalty: {$params['penalty']}{$penalty_type}</div>");
                              echo("          <div class=\"info\">Grading: {$grading}</div>");
                              echo('        </td>');
                              echo('        <td class="buttons" style="line-height: 2em;">');
                              echo("          <a href=\"{$reports_url}\" target=\"_blank\"><img src=\"../../../../images/file_icons/report.png\" width=\"32\" height=\"32\" alt=\"View the grades report\" /></a><br />\n");
                              echo("          <a class=\"button\" href=\"$send_url\" onclick=\"return confirm('Are you sure?');\">{$action} source</a>");
                              echo('        </td>');
                              echo('      </tr>');
                              echo('      </table>');
                              echo('    </div>');
                          }
                      }
                      ?>
                    </div>
                    <?php
                }
                ?>
              </div>
              <?php
          }
      }
  } else {
      ?>

      <p>
        Sorry, this source does not support the outcomes service.
      </p>

      <?php
  }
  ?>
</div>
<?php
$UI->content_end();
?>