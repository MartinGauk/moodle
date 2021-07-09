<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Exporting a comment.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_comment\external;

defined('MOODLE_INTERNAL') || die();

use core_comment\capability;
use core_comment\comment;
use core_comment\subscription;
use renderer_base;
use stdClass;

/**
 * Class for exporting a comment.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_exporter extends \core\external\exporter {

    /** @var comment The comment. */
    protected $comment = null;

    public function __construct(comment $comment) {
        $this->comment = $comment;
        $data = new stdClass();
        $data->component = $comment->get_section()->get_area()->get_component();
        $data->commentarea = $comment->get_section()->get_area()->get_area();
        $data->itemid = $comment->get_section()->get_item_id();
        $data->contextid = $comment->get_section()->get_context()->id;
        $data->id = $comment->get_id();
        $data->replytoid = $comment->get_replytoid();
        $data->content = $comment->get_content();
        $data->contentformat = $comment->get_content_format();
        $data->customdata = $comment->get_custom_data_json();
        $related = array(
            'context' => $comment->get_section()->get_context()
        );
        parent::__construct($data, $related);
    }

    protected static function define_related() {
        return array('context' => 'context');
    }

    protected static function define_properties() {
        return array(
            'component' => array(
                'type' => PARAM_COMPONENT,
            ),
            'commentarea' => array(
                'type' => PARAM_AREA,
            ),
            'contextid' => array(
                'type' => PARAM_INT,
            ),
            'itemid' => array(
                'type' => PARAM_INT,
            ),
            'id' => array(
                'type' => PARAM_INT,
            ),
            'replytoid' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ),
            'content' => array(
                'type' => PARAM_RAW,
            ),
            'contentformat' => array(
                'type' => PARAM_INT,
                'default' => FORMAT_MOODLE,
            ),
            'customdata' => array(
                'type' => PARAM_RAW,
                'default' => '',
            ),
        );
    }

    protected static function define_other_properties() {
        return array(
            'timecreated' => array(
                'type' => PARAM_INT,
            ),
            'timecreatedtext' => array(
                'type' => PARAM_RAW,
            ),
            'timemodified' => array(
                'type' => PARAM_INT,
            ),
            'timemodifiedtext' => array(
                'type' => PARAM_RAW,
            ),
            'strftimeformat' => array(
                'type' => PARAM_RAW,
            ),
            'profileurl' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED
            ),
            'fullname' => array(
                'type' => PARAM_RAW,
            ),
            'pseudonym' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
            ),
            'avatar' => array(
                'type' => PARAM_RAW,
            ),
            'replies' => array(
                'type' => PARAM_INT,
            ),
            'upvotes' => array(
                'type' => PARAM_INT,
            ),
            'vote' => array(
                'type' => PARAM_INT,
            ),
            'subscription' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
            ),
            'subscriptiondefault' => array(
                'type' => PARAM_RAW,
            ),
            'userid' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ),
            'usermodifiedid' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ),
            'usermodifiedfullname' => array(
                'type' => PARAM_RAW,
            ),
            'isown' => array(
                'type' => PARAM_BOOL,
            ),
            'canupvote' => array(
                'type' => PARAM_BOOL,
            ),
            'cansubscribeimmediate' => array(
                'type' => PARAM_BOOL,
            ),
            'cansubscribedigests' => array(
                'type' => PARAM_BOOL,
            ),
            'canunsubscribe' => array(
                'type' => PARAM_BOOL,
            ),
            'canreply' => array(
                'type' => PARAM_BOOL,
            ),
            'allowpseudonymreply' => array(
                'type' => PARAM_BOOL,
            ),
            'allowrealnamereply' => array(
                'type' => PARAM_BOOL,
            ),
            'canedit' => array(
                'type' => PARAM_BOOL,
            ),
            'candelete' => array(
                'type' => PARAM_BOOL,
            ),
            'delete' => array( // deprecated
                'type' => PARAM_BOOL,
                'description' => 'deprecated, replaced by candelete',
            ),
            'time' => array( // deprecated
                'type' => PARAM_RAW,
                'description' => 'deprecated, replaced by timecreatedtext',
            ),
            'format' => array( // deprecated
                'type' => PARAM_INT,
                'description' => 'deprecated, replaced by contentformat',
            ),
        );
    }

    public function get_other_values(renderer_base $output) {
        global $USER, $OUTPUT;
        $values = array();
        $values['strftimeformat'] = get_string('strftimerecentfull', 'langconfig');
        $values['time'] = $values['timecreated'] = $this->comment->get_timecreated();
        $values['timecreatedtext'] = userdate($values['timecreated'], $values['strftimeformat']);
        $values['timemodified'] = $this->comment->get_timemodified();
        $values['timemodifiedtext'] = userdate($values['timemodified'], $values['strftimeformat']);
        $usercreated = $this->comment->get_usercreated();
        $values['profileurl'] = null;
        if (!$this->comment->is_pseudonymous_author()) {
            $course = $this->comment->get_section()->get_area()->get_course();
            $url = new \moodle_url('/user/view.php', array('id' => $usercreated->id, 'course' => $course->id));
            $values['profileurl'] = $url->out(false);
        }
        $values['userid'] = null;
        if (!$this->comment->is_pseudonymous_author()) {
            $values['userid'] = $usercreated->id;
        }
        $values['usercreatedfullname'] = $this->comment->get_usercreated_fullname();
        $values['usermodifiedid'] = null;
        $usermodifiedid = $this->comment->get_usermodified_id();
        if ($usercreated->id != $usermodifiedid || !$this->comment->is_pseudonymous_author()) {
            $values['usermodifiedid'] = $usermodifiedid;
        }
        $values['usermodifiedfullname'] = $this->comment->get_usermodified_fullname();
        $values['pseudonym'] = $this->comment->get_pseudonym();
        $values['fullname'] = $this->comment->get_usercreated_fullname();
        $values['avatar'] = $OUTPUT->user_picture($usercreated, array('size'=>18));
        $values['replies'] = $this->comment->get_replies();
        $values['upvotes'] = $this->comment->get_upvotes();
        $values['vote'] = 0; //TODO get this from somewhere
        $values['isown'] = $this->comment->is_owned_by_user($USER->id);
        $cap = $this->comment->get_section()->get_capability($USER);
        $values['allowpseudonymreply'] = $cap->can_post(capability::POST_PSEUDONYM, $this->comment);
        $values['allowrealnamereply'] = $cap->can_post(capability::POST_REALNAME, $this->comment);
        $values['canreply'] = $values['allowpseudonymreply'] || $values['allowrealnamereply'];
        $values['canupvote'] = $cap->can_upvote($this->comment);
        $subscription = subscription::get_subscription_status($USER, $this->comment->get_section(), $this->comment);
        $values['cansubscribeimmediate'] = $cap->can_modify_subscription_status(
            $subscription,
            subscription::NOTIFICATION_IMMEDIATE,
            $this->comment
        );
        $values['cansubscribedigests'] = $cap->can_modify_subscription_status(
            $subscription,
            subscription::NOTIFICATION_DAILY_DIGEST,
            $this->comment
        );
        $values['canunsubscribe'] = $cap->can_modify_subscription_status(
            $subscription,
            subscription::NOTIFICATION_OFF,
            $this->comment
        );
        $values['canedit'] = $cap->can_edit($this->comment);
        $values['delete'] = $values['candelete'] = $cap->can_delete($this->comment);
        $values['subscription'] = external::format_subscription($subscription);
        $subscriptiondefault = subscription::NOTIFICATION_OFF; //TODO where should default come from?
        $values['subscriptiondefault'] = external::format_subscription($subscriptiondefault);
        $values['format'] = $this->comment->get_content_format();
        return $values;
    }
}
