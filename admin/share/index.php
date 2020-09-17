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
###  Page to update list of enrolled users
###

use ceLTIc\LTI\ResourceLink;

require_once('../../includes.php');

#
### Option only available for tutors
#
if (!ALLOW_SHARING || !check_user($_user, APP__USER_TYPE_TUTOR)) {
    header('Location:' . APP__WWW . '/logout.php?msg=denied');
    exit;
}
#
### Set the page information
#
$UI->page_title = APP__NAME . " view sharing data";
$UI->menu_selected = 'sharing';
$UI->breadcrumbs = array('home' => '../../../../admin/', 'lti sources' => null);
$UI->help_link = '?q=node/237';
$heading = "Shared Source Resource Links";
#
### Display page
#
$UI->head();
?>
<script type="text/javascript" src="../../js/ajax.js"></script>
<?php
$UI->body();
$UI->content_start();

$can_edit = ($_source_id == $_user_source_id) && ($_module_code == $_user_context_id);
if ($can_edit) {
    ?>
    <div class="content_box">
      <div style="border: 1px dotted black; float: right; padding: 10px; text-align: center;">
        <form action="" method="get">
          <strong>New share key</strong><br /><br />
          Life:&nbsp;<select id="life">
            <option value="1">1 hour</option>
            <option value="2">2 hours</option>
            <option value="12">12 hours</option>
            <option value="24">1 day</option>
            <option value="48">2 days</option>
            <option value="72" selected="selected">3 days</option>
            <option value="96">4 days</option>
            <option value="120">5 days</option>
            <option value="168">1 week</option>
          </select><br />
          Auto approve?&nbsp;<input type="checkbox" id="auto_approve" value="yes" /><br /><br />
          <input type="button" value="Generate" onclick="return doGenerateKey();" />
        </form>
      </div>
      <div>
        <?php
    }

    $resource_link = ResourceLink::fromPlatform($lti_platform, $_module_code);

    $shares = $resource_link->getShares();
    ?>
    <h2><?php echo $heading; ?></h2>

    <p>
      You may share this module with users from other sources.  These might be:
    </p>
    <ul>
      <li>other links from within the same course;</li>
      <li>links from other courses in the same VLE; or even</li>
      <li>links from a different VLE within your own institution or outside.</li>
    </ul>
    <div class="obj">
      <table class="obj" cellpadding="2" cellspacing="2">
        <?php
        if (count($shares) > 0) {
            ?>
            <tr>
              <th>&nbsp;</th>
              <th>Source</th>
              <th>Title</th>
              <th>Approved</th>
              <th>&nbsp;</th>
            </tr>
            <?php
            $i = 0;
            foreach ($shares as $share) {
                $i++;
                echo "<tr>\n";
                echo "  <td class=\"icon\">";
                if ($can_edit) {
                    echo "\n" . '    <a href="cancel.php?ci=' . urlencode($share->consumerName) . '&rlid=' . urlencode($share->resourceLinkId) . '" onclick="return confirm(\'Cancel share; are you sure?\');">';
                    echo '<img src="../../../../images/buttons/cross.gif" width="16" height="16" alt="Cancel share" title="Cancel share" /></a>' . "\n";
                } else {
                    echo '&nbsp;';
                }
                echo "  </td>\n";
                echo '  <td class="obj_info_text">' . $share->consumerName . '</td>' . "\n";
                echo '  <td class="obj_info_text">' . $share->title . '</td>' . "\n";
                echo '  <td class="obj_info_text">';
                if ($can_edit) {
                    if ($share->approved) {
                        echo "<span id=\"label{$i}\">Yes</span></td><td>";
                        echo "<input type=\"button\" id=\"button{$i}\" value=\"Suspend\" ";
                        echo "onclick=\"return doApprove('{$i}', '" . APP__WWW . '/mod/' . LTI_MODULE_NAME . "/admin/share/approve.php', '{$share->consumerName}', '{$share->resourceLinkId}');\" />";
                    } else {
                        echo "<span id=\"label{$i}\">No</span></td><td>";
                        echo "<input type=\"button\" id=\"button{$i}\" value=\"Approve\" ";
                        echo "onclick=\"return doApprove('{$i}', '" . APP__WWW . '/mod/' . LTI_MODULE_NAME . "/admin/share/approve.php', '{$share->consumerName}', '{$share->resourceLinkId}');\" />";
                    }
                } else if ($share->approved) {
                    echo 'Yes';
                } else {
                    echo 'No';
                }
                echo "</td>\n";
            }
            echo "</tr>\n";
        } else {
            echo "<tr><td>This module is not currently being shared.</td></tr>\n";
        }
        ?>
      </table>
    </div>

    <p>
      To invite another source to share this module:
    </p>
    <ol>
      <li>use the button at the bottom of this page to generate a new share key (you may choose to pre-approve the
        share or leave it to be approved once the key has been initialised, see below)</li>
      <li>send the share key to an instructor for the other source</li>
    </ol>

    <p>
      On receipt of the share key string (e.g. "share_key=xyz"), the other instructor should:
    </p>
    <ol>
      <li>create an LTI link to this <?php echo APP__NAME; ?> server using their own consumer key and secret, and
        include the share key string in the custom parameters</li>
      <li>initialise the share by clicking on the link to launch <?php echo APP__NAME; ?></li>
      <li>if the share has not been pre-approved the connection will fail and they should notify you so that the share
        can be approved on this page</li>
    </ol>

    <p>
      Once the share has been approved all users from the other source will be directed into this module.
    </p>

  </div>
  <div style="clear: both;">&nbsp;</div>

</div>
<?php
$UI->content_end();
?>