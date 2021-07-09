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
 * External comment API
 *
 * @package    core_comment
 * @category   external
 * @copyright  Costantino Cito <ccito@cvaconsulting.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */

namespace core_comment\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/comment/lib.php");

use comment_exception;
use core_comment\capability;
use core_comment\manager;
use core_comment\subscription;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * External comment API functions
 *
 * @package    core_comment
 * @category   external
 * @copyright  Costantino Cito <ccito@cvaconsulting.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */
class external extends \external_api {

    /**
     * Parse a subscription string and return the integer constant or null if invalid.
     *
     * @param string $subscription One of 'default', 'off', 'digests' and 'immediate'.
     * @return int|null The subscription constant.
     */
    public static function parse_subscription(string $subscription) : ?int {
        switch ($subscription) {
            case 'default':
                return subscription::NOTIFICATION_DEFAULT;
            case 'off':
                return subscription::NOTIFICATION_OFF;
            case 'digests':
                return subscription::NOTIFICATION_DAILY_DIGEST;
            case 'immediate':
                return subscription::NOTIFICATION_IMMEDIATE;
        }
        return null;
    }

    /**
     * Format a subscription constant as a string or null if invalid.
     *
     * @param int $subscription The subscription constant.
     * @return string|null One of 'default', 'off', 'digests' and 'immediate'.
     */
    public static function format_subscription(int $subscription) : ?string {
        switch ($subscription) {
            case subscription::NOTIFICATION_DEFAULT:
                return 'default';
            case subscription::NOTIFICATION_OFF:
                return 'off';
            case subscription::NOTIFICATION_DAILY_DIGEST:
                return 'digests';
            case subscription::NOTIFICATION_IMMEDIATE:
                return 'immediate';
        }
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.9
     */
    public static function get_comments_parameters() {

        return new external_function_parameters(
            array(
                'contextlevel'  => new external_value(PARAM_ALPHA, 'deprecated, replaced by contextid', VALUE_DEFAULT, null, NULL_ALLOWED),
                'instanceid'    => new external_value(PARAM_INT, 'deprecated, replaced by contextid', VALUE_DEFAULT, null, NULL_ALLOWED),
                'contextid'     => new external_value(PARAM_INT, 'the context id', VALUE_DEFAULT, null, NULL_ALLOWED),
                'component'     => new external_value(PARAM_COMPONENT, 'component', VALUE_DEFAULT, null, NULL_ALLOWED),
                'itemid'        => new external_value(PARAM_INT, 'associated id', VALUE_DEFAULT, null, NULL_ALLOWED),
                'area'          => new external_value(PARAM_AREA, 'deprecated, replaced by commentarea', VALUE_DEFAULT, null, NULL_ALLOWED),
                'commentarea'   => new external_value(PARAM_AREA, 'string comment area', VALUE_DEFAULT, null, NULL_ALLOWED),
                'replytoid'     => new external_value(PARAM_INT, 'get replies to comment', VALUE_DEFAULT, null, NULL_ALLOWED),
                'commentid'     => new external_value(PARAM_INT, 'get one comment by id', VALUE_DEFAULT, null, NULL_ALLOWED),
                'page'          => new external_value(PARAM_INT, 'page number (0 based)', VALUE_DEFAULT, 0),
                'pagesize'      => new external_value(PARAM_INT, 'page size', VALUE_DEFAULT, 50),
                'timefrom'      => new external_value(PARAM_INT, 'filter comments by timecreated', VALUE_DEFAULT, null, NULL_ALLOWED),
                'timeto'        => new external_value(PARAM_INT, 'filter comments by timecreated', VALUE_DEFAULT, null, NULL_ALLOWED),
                'sortdirection' => new external_value(PARAM_ALPHA, 'Sort direction: ASC or DESC', VALUE_DEFAULT, 'DESC'),
            )
        );
    }

    /**
     * Return a list of comments
     *
     * @param string|null $contextlevel 'system, course, user', etc.. (deprecated, use $contextid instead)
     * @param int|null $instanceid (deprecated, use $contextid instead)
     * @param int|null $contextid the context id
     * @param string|null $component the name of the component
     * @param int|null $itemid the item id
     * @param string|null $area comment area (deprecated, use $commentarea instead)
     * @param string|null $commentarea comment area
     * @param int|null $replytoid get replies to comment
     * @param int|null $commentid get one comment by id
     * @param int $page page number (0 based)
     * @param int $pagesize page size (maximum 200, defaults to 50)
     * @param int|null $timefrom filter comments by timecreated
     * @param int|null $timeto filter comments by timecreated
     * @param string $sortdirection sort direction
     * @return array of comments and warnings
     * @since Moodle 2.9
     */
    public static function get_comments(?string $contextlevel, ?int $instanceid, ?int $contextid, ?string $component,
            ?int $itemid, ?string $area = null, ?string $commentarea = null, ?int $replytoid = null,
            ?int $commentid = null, int $page = 0, int $pagesize = 50, ?int $timefrom = null, ?int $timeto = null,
            string $sortdirection = 'DESC') {
        global $CFG, $SITE, $USER, $PAGE;

        $warnings = array();
        $arrayparams = array(
            'contextlevel'  => $contextlevel,
            'instanceid'    => $instanceid,
            'contextid'     => $contextid,
            'component'     => $component,
            'itemid'        => $itemid,
            'area'          => $area,
            'commentarea'   => $commentarea,
            'replytoid'     => $replytoid,
            'commentid'     => $commentid,
            'page'          => $page,
            'pagesize'      => $pagesize,
            'timefrom'      => $timefrom,
            'timeto'        => $timeto,
            'sortdirection' => $sortdirection,
        );
        $params = self::validate_parameters(self::get_comments_parameters(), $arrayparams);

        $sortdirection = strtoupper($params['sortdirection']);
        $directionallowedvalues = array('ASC', 'DESC');
        if (!in_array($sortdirection, $directionallowedvalues)) {
            throw new \invalid_parameter_exception('Invalid value for sortdirection parameter (value: ' . $sortdirection . '),' .
                'allowed values are: ' . implode(',', $directionallowedvalues));
        }

        if ($params['pagesize'] > 200) {
            throw new \invalid_parameter_exception('Invalid value for pagesize parameter (value: ' .
                $params['pagesize'] . ', maximum 200 allowed)');
        }

        $comments = [];
        $count = 0;
        $canpost = false;
        if (!is_null($params['commentid'])) {
            // Select a single comment by id.
            $comment = manager::get_comment($params['commentid']);
            self::validate_context($comment->get_section()->get_context());

            if ($comment && $comment->get_section()->get_capability($USER)->can_view()) {
                $comments = [$comment];
                $count = 1;
            }
        } else {
            // Search comments.
            $context = self::get_context_from_params($params);
            self::validate_context($context);

            list($context, $course, $cm) = get_context_info_array($context->id);
            if ($context->id == SYSCONTEXTID) {
                $course = $SITE;
            }

            // Initialising comment object.
            $area = manager::get_comment_area($params['component'], $params['commentarea'] ?? $params['area'], $context, $course);
            if (!is_null($params['itemid'])) {
                $section = $area->get_section($params['itemid']);
                $cap = $section->get_capability($USER);
                if ($cap->can_view()) {
                    $comments = $section->get_comments($params['replytoid'], $params['timefrom'], $params['timeto'],
                        $params['page'], $params['pagesize'], $sortdirection, $USER);
                    $count = $comments->count_total();
                }
                $canpost = $cap->can_post(capability::POST_PSEUDONYM) || $cap->can_post(capability::POST_REALNAME);
            } else {
                $comments = $area->get_comments_in_area($params['timefrom'], $params['timeto'], $params['page'],
                    $params['pagesize'], $sortdirection, true, $USER);
                $count = $comments->count_total();
            }
        }

        // Export comments and sections.
        $exportedcomments = [];
        $exportedsections = [];
        $renderer = $PAGE->get_renderer('core');
        foreach ($comments as $comment) {
            $commentexporter = new comment_exporter($comment);
            $exportedcomments[] = $commentexporter->export($renderer);
            $sectionkey = $comment->get_section()->get_unique_key();
            if (!array_key_exists($sectionkey, $exportedsections)) {
                $sectionexporter = new comment_section_exporter($comment->get_section());
                $exportedsections[$sectionkey] = $sectionexporter->export($renderer);
            }
        }

        return array(
            'comments' => $exportedcomments,
            'count' => $count,
            'commentsections' => array_values($exportedsections),
            'perpage' => $params['pagesize'],
            'canpost'  => $canpost,
            'warnings' => $warnings
        );
    }

    /**
     * Returns description of method result value
     *
     * @return \external_description
     * @since Moodle 2.9
     */
    public static function get_comments_returns() {
        return new external_single_structure(
            array(
                'comments' => new external_multiple_structure(
                    comment_exporter::get_read_structure(), 'List of comments'
                ),
                'count' => new external_value(PARAM_INT,  'Total number of comments.', VALUE_OPTIONAL),
                'commentsections' => new external_multiple_structure(
                    comment_section_exporter::get_read_structure(), 'List of all comment sections referenced in the comments list'
                ),
                'perpage' => new external_value(PARAM_INT,  'Number of comments per page.', VALUE_OPTIONAL),
                'canpost' => new external_value(PARAM_BOOL, 'deprecated, replaced by commentsections.canpost', VALUE_OPTIONAL),
                'warnings' => new \external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters for the add_comments method.
     *
     * @return external_function_parameters
     * @deprecated Use create_comments instead.
     * @since Moodle 3.8
     */
    public static function add_comments_parameters() {
        return new external_function_parameters(
            [
                'comments' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'contextlevel' => new external_value(PARAM_ALPHA, 'contextlevel system, course, user...'),
                            'instanceid'   => new external_value(PARAM_INT, 'the id of item associated with the contextlevel'),
                            'component'    => new external_value(PARAM_COMPONENT, 'component'),
                            'content'      => new external_value(PARAM_RAW, 'component'),
                            'itemid'       => new external_value(PARAM_INT, 'associated id'),
                            'area'         => new external_value(PARAM_AREA, 'string comment area', VALUE_DEFAULT, ''),
                        ]
                    )
                )
            ]
        );
    }

    /**
     * Mark the add_comments web service as deprecated.
     *
     * @return  bool
     */
    public static function add_comments_is_deprecated() {
        return true;
    }

    /**
     * Add a comment or comments.
     *
     * @param array $comments the array of comments to create.
     * @return array the array containing those comments created.
     * @throws comment_exception
     * @deprecated since Moodle 4.0 MDL-71935 - Use create_comments instead.
     * @since Moodle 3.8
     */
    public static function add_comments($comments) {
        debugging('add_comments() is deprecated. Please use external::create_comments() instead.', DEBUG_DEVELOPER);

        return self::create_comments(array_map(function($comment) {
            return array(
                'contextid'     => self::get_context_from_params($comment)->id,
                'component'     => $comment['component'],
                'itemid'        => $comment['itemid'],
                'commentarea'   => $comment['area'],
                'content'       => $comment['content'],
                'contentformat' => FORMAT_MOODLE,
            );
        }, $comments));
    }

    /**
     * Returns description of method result value for the add_comments method.
     *
     * @return \external_description
     * @deprecated Use create_comments instead.
     * @since Moodle 3.8
     */
    public static function add_comments_returns() {
        return new external_multiple_structure(
            comment_exporter::get_read_structure()
        );
    }

    /**
     * Returns description of method parameters for the create_comments method.
     *
     * @return external_function_parameters
     * @since Moodle 4.0
     */
    public static function create_comments_parameters() {
        return new external_function_parameters(
            [
                'comments' => new external_multiple_structure(
                    comment_exporter::get_create_structure()
                )
            ]
        );
    }

    /**
     * Create a comment or comments.
     *
     * @param array $comments the array of comments to create.
     * @return array the array containing those comments created.
     * @throws comment_exception
     * @since Moodle 4.0
     */
    public static function create_comments($comments) {
        global $CFG, $SITE, $USER, $PAGE;

        if (empty($CFG->usecomments)) {
            throw new comment_exception('commentsnotenabled', 'moodle');
        }

        $params = self::validate_parameters(self::create_comments_parameters(), ['comments' => $comments]);

        // Validate every intended comment before creating anything, storing the validated comment for use below.
        $createdcomments = [];
        foreach ($params['comments'] as $comment) {
            list($context, $course, $cm) = get_context_info_array($comment['contextid']);
            if ($context->id == SYSCONTEXTID) {
                $course = $SITE;
            }
            self::validate_context($context);

            // Initialising comment object.
            $area = manager::get_comment_area($comment['component'], $comment['commentarea'], $context, $course);
            $section = $area->get_section($comment['itemid']);
            $replyto = null;
            if ($comment['replytoid'] !== null) {
                $replyto = $section->get_comment($comment['replytoid']);
                if (!$replyto) {
                    throw new \invalid_parameter_exception('Invalid value for replytoid (value: ' .
                        $comment['replytoid'] . '), no comment found with that id');
                }
            }

            // Trim strings.
            $comment['content'] = trim($comment['content']);
            $comment['pseudonym'] = trim($comment['pseudonym']);
            $comment['customdata'] = trim($comment['customdata']);

            $cap = $section->get_capability($USER);
            $postmode = $comment['pseudonym'] != '' ? capability::POST_PSEUDONYM : capability::POST_REALNAME;
            if (!$cap->can_post($postmode, $replyto)) {
                throw new comment_exception('nopermissiontocomment');
            }

            $commentobj = $section->construct_new_comment(
                $comment['content'],
                $comment['contentformat'],
                $USER->id,
                $comment['pseudonym'],
                $comment['replytoid'],
                $comment['customdata']
            );

            self::validate_and_modify_comment($commentobj, $cap);

            // TODO: Increase reply count in parent comment

            $createdcomments[] = $commentobj;
        }

        // Create the comments.
        $results = [];
        $renderer = $PAGE->get_renderer('core');
        foreach ($createdcomments as $commentobj) {
            $commentobj->save();
            $exporter = new comment_exporter($commentobj);
            $results[] = $exporter->export($renderer);
        }

        return $results;
    }

    /**
     * Returns description of method result value for the create_comments method.
     *
     * @return \external_description
     * @since Moodle 4.0
     */
    public static function create_comments_returns() {
        return new external_multiple_structure(
            comment_exporter::get_read_structure()
        );
    }

    /**
     * Returns description of method parameters for the update_comments method.
     *
     * @return external_function_parameters
     * @since Moodle 4.0
     */
    public static function update_comments_parameters() {
        return new external_function_parameters(
            [
                'comments' => new external_multiple_structure(
                    comment_exporter::get_update_structure()
                )
            ]
        );
    }

    /**
     * Update a comment or comments.
     *
     * @param array $comments the array of comments to update.
     * @return array the array containing those comments updated (after any changes by the server).
     * @throws comment_exception
     * @since Moodle 4.0
     */
    public static function update_comments($comments) {
        global $CFG, $SITE, $USER, $PAGE;

        if (empty($CFG->usecomments)) {
            throw new comment_exception('commentsnotenabled', 'moodle');
        }

        $params = self::validate_parameters(self::update_comments_parameters(), ['comments' => $comments]);

        // Validate every intended comment before updating anything, storing the validated comment for use below.
        $updatedcomments = [];
        foreach ($params['comments'] as $comment) {
            list($context, $course, $cm) = get_context_info_array($comment['contextid']);
            if ($context->id == SYSCONTEXTID) {
                $course = $SITE;
            }
            self::validate_context($context);

            // Find and update comment.
            $area = manager::get_comment_area($comment['component'], $comment['commentarea'], $context, $course);
            $section = $area->get_section($comment['itemid']);
            $commentobj = $section->get_comment($comment['id']);
            if (!$commentobj) {
                throw new \invalid_parameter_exception('Cannot update comment, comment not found.');
            }

            $cap = $section->get_capability($USER);
            if (!$cap->can_edit($commentobj)) {
                throw new comment_exception('nopermissiontoedit');
            }

            $commentobj->set_content(trim($comment['content']), $comment['contentformat']);
            $commentobj->set_pseudonym(trim($comment['pseudonym']));
            $commentobj->set_custom_data_json(trim($comment['customdata']));
            $commentobj->update_time_user($USER->id);

            self::validate_and_modify_comment($commentobj, $cap);

            $updatedcomments[] = $commentobj;
        }

        // Update the comments.
        $results = [];
        $renderer = $PAGE->get_renderer('core');
        foreach ($updatedcomments as $commentobj) {
            $commentobj->save();
            $exporter = new comment_exporter($commentobj);
            $results[] = $exporter->export($renderer);
        }

        return $results;
    }

    /**
     * Returns description of method result value for the create_comments method.
     *
     * @return \external_description
     * @since Moodle 4.0
     */
    public static function update_comments_returns() {
        return new external_multiple_structure(
            comment_exporter::get_read_structure()
        );
    }

    /**
     * Returns description of method parameters for the delete_comments() method.
     *
     * @return external_function_parameters
     * @since Moodle 3.8
     */
    public static function delete_comments_parameters() {
        return new external_function_parameters(
            [
                'comments' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'id of the comment', VALUE_DEFAULT, 0)
                )
            ]
        );
    }

    /**
     * Deletes a comment or comments.
     *
     * @param array $comments array of comment ids to be deleted
     * @return array
     * @throws comment_exception
     * @since Moodle 3.8
     */
    public static function delete_comments(array $comments) {
        global $CFG, $DB, $USER, $SITE;

        if (empty($CFG->usecomments)) {
            throw new comment_exception('commentsnotenabled', 'moodle');
        }

        $params = self::validate_parameters(self::delete_comments_parameters(), ['comments' => $comments]);
        $commentids = $params['comments'];

        list($insql, $inparams) = $DB->get_in_or_equal($commentids);
        $commentrecords = $DB->get_records_select('comments', "id {$insql}", $inparams);

        // If one or more of the records could not be found, report this and fail early.
        if (count($commentrecords) != count($comments)) {
            $invalidcomments = array_diff($commentids, array_column($commentrecords, 'id'));
            $invalidcommentsstr = implode(',', $invalidcomments);
            throw new comment_exception("One or more comments could not be found by id: $invalidcommentsstr");
        }

        // Make sure we can delete every one of the comments before actually doing so.
        $comments = []; // Holds the comment objects, for later deletion.
        foreach ($commentrecords as $commentrecord) {
            // Validate the context.
            list($context, $course, $cm) = get_context_info_array($commentrecord->contextid);
            if ($context->id == SYSCONTEXTID) {
                $course = $SITE;
            }
            self::validate_context($context);

            // Make sure the user is allowed to delete the comment.
            $area = manager::get_comment_area($commentrecord->component, $commentrecord->commentarea, $context, $course);
            $section = $area->get_section($commentrecord->itemid);
            $comment = $section->construct_comment_from_db($commentrecord);

            $cap = $section->get_capability($USER);
            if (!$cap->can_delete($comment)) {
                throw new comment_exception('nopermissiontodelentry');
            }

            // TODO Decrease reply count in parent comment

            // User is allowed to delete it, so store the comment object, for use below in final deletion.
            $comments[] = $comment;
        }

        // All comments can be deleted by the user. Make it so.
        foreach ($comments as $comment) {
            $comment->delete();
        }

        return [];
    }

    /**
     * Returns description of method result value for the delete_comments() method.
     *
     * @return \external_description
     * @since Moodle 3.8
     */
    public static function delete_comments_returns() {
        return new \external_warnings();
    }

    /**
     * Returns description of method parameters for the get_commentsections() method.
     *
     * @return external_function_parameters
     * @since Moodle 4.0
     */
    public static function get_commentsections_parameters() {

        return new external_function_parameters(
            array(
                'contextid'     => new external_value(PARAM_INT, 'the context id'),
                'component'     => new external_value(PARAM_COMPONENT, 'component'),
                'area'          => new external_value(PARAM_AREA, 'string comment area'),
                'itemid'        => new external_value(PARAM_INT, 'associated id', VALUE_DEFAULT, null, NULL_ALLOWED),
            )
        );
    }

    /**
     * Return a list of comment sections.
     *
     * @param int $contextid the context id
     * @param string $component the name of the component
     * @param string $area comment area
     * @param int|null $itemid the item id
     * @return array of comment sections
     * @since Moodle 4.0
     */
    public static function get_commentsections(int $contextid, string $component, string $area, ?int $itemid = null) {
        global $CFG, $SITE, $USER, $PAGE;

        $arrayparams = array(
            'contextid'     => $contextid,
            'component'     => $component,
            'itemid'        => $itemid,
            'area'          => $area,
        );
        $params = self::validate_parameters(self::get_commentsections_parameters(), $arrayparams);

        list($context, $course, $cm) = get_context_info_array($params['contextid']);
        if ($context->id == SYSCONTEXTID) {
            $course = $SITE;
        }
        self::validate_context($context);

        // Search comment sections.
        $area = manager::get_comment_area($params['component'], $params['area'], $context, $course);
        $commentsections = [];
        if (!is_null($params['itemid'])) {
            $section = $area->get_section($params['itemid']);
            $commentsections = [$section];
        } else {
            // TODO this doesnt make sense. What are we searching for? Do we even need this function?
            $commentsections = [];
        }

        // Export sections.
        $exportedsections = [];
        $renderer = $PAGE->get_renderer('core');
        foreach ($commentsections as $section) {
            $sectionexporter = new comment_section_exporter($section);
            $exportedsections[] = $sectionexporter->export($renderer);
        }

        return array(
            'commentsections' => $exportedsections,
        );
    }

    /**
     * Returns description of method result value for the get_commentsections() method.
     *
     * @return \external_description
     * @since Moodle 4.0
     */
    public static function get_commentsections_returns() {
        return new external_single_structure(
            array(
                'commentsections' => new external_multiple_structure(
                    comment_section_exporter::get_read_structure(), 'List of comment sections'
                ),
            )
        );
    }

    /**
     * Validate a new or updated comment and modify it if necessary.
     *
     * @param \core_comment\comment $comment
     * @param capability $cap
     */
    protected static function validate_and_modify_comment(\core_comment\comment $comment, capability $cap) {
        $section = $comment->get_section();

        //TODO maybe move validation functions to capability class? seems related
        if ($comment->is_pseudonymous_author()
            && ($errors = $section->validate_comment_pseudonym($comment, $cap)) !== true) {
            //TODO handle errors
            throw new comment_exception();
        }
        if (($errors = $section->validate_comment_content($comment, $cap)) !== true) {
            //TODO handle errors
            throw new comment_exception();
        }
        if (($errors = $section->validate_comment_custom_data($comment, $cap)) !== true) {
            //TODO handle errors
            throw new comment_exception();
        }

        $section->modify_comment_before_save($comment, $cap);
    }

}
