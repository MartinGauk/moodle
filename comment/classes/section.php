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
 * Abstract class that represents a comment section in a plugin.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class that represents a comment section in a plugin.
 *
 * A comment section is a collection of all comments that belong to one itemid within a comment area.
 * Comments always belong to a context, component, comment area and item id.
 *
 * What the item id refers to is defined by the plugin.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class section {
    /** @var area comment area that this section belongs to */
    protected $area;

    /** @var int */
    protected $itemid;

    /** @var mixed item object that belongs to the item id */
    protected $item;

    /**
     * Comment section constructor.
     *
     * Optionally, an item object that belongs to the item id can be passed in order to avoid fetching
     * more information from the database. When a subclass makes use of $item, it has to check in the
     * constructor whether $item is the expected object.
     *
     * @param area $area
     * @param int $itemid
     * @param mixed $item
     */
    public function __construct(area $area, int $itemid, $item = null) {
        $this->area = $area;
        $this->itemid = $itemid;
        $this->item = $item;
    }

    public function get_area() : area {
        return $this->area;
    }

    public function get_item_id() : int {
        return $this->itemid;
    }

    /**
     * Show the comments in this section.
     *
     * @param int $pagesize maximum number of comments to show initially (TODO better variable name?)
     * @return string HTML to display
     */
    public function output(int $pagesize) : string {
        // TODO move to renderer
        global $PAGE;
        return $PAGE->get_renderer('core_comment')->render(new output\section($this));
    }

    /**
     * Delete all comments in this section.
     */
    public function delete() {
        // TODO
    }

    /**
     * Get the number of comments in this section or the number of replies to a comment.
     *
     * @param int|null $replytoid comment id
     * @return int
     */
    public function count_comments(int $replytoid = null) : int {
        // TODO
    }

    /**
     * Fetch the comments that were posted in the section.
     *
     * @param int|null $replytoid when int given, fetch only replies to the comment id
     * @param int|null $timefrom only comments that were created at or after this time
     * @param int|null $timeto only comments that were created at or before this time
     * @param int $page
     * @param int $pagesize maximum number of comments to fetch
     * @param string $sortdirection ASC or DESC (comments are ordered by timecreated)
     * @param \stdClass|null $user user object
     * @return comment_search
     */
    public function get_comments(?int $replytoid, ?int $timefrom, ?int $timeto, int $page, int $pagesize, string $sortdirection,
            ?\stdClass $user) : comment_search {
        // TODO
        return new comment_search($this->get_area(), $this, $replytoid, $timefrom, $timeto, $page, $pagesize, $sortdirection, false, false, $user);
    }

    /**
     * Get a comment by its id.
     *
     * @param int $commentid
     * @return comment|null
     */
    public function get_comment(int $commentid) : ?comment {
        global $DB;
        $record = $DB->get_record('comments', [
            'component' => $this->get_area()->get_component(),
            'commentarea' => $this->get_area()->get_area(),
            'contextid' => $this->get_context()->id,
            'itemid' => $this->get_item_id(),
            'id' => $commentid
        ]);
        if (!$record) {
            return null;
        }
        return $this->construct_comment_from_db($record);
    }

    /**
     * Get the definition of the available properties in the custom data to a comment.
     *
     * This allows to store some custom/meta data associated with a comment.
     *
     * Example array that can be returned:
     *  return [
     *      'property_name' => [
     *          'type' => PARAM_TYPE,                // Mandatory.
     *          'default' => 'Default value',        // When not set, the property is considered as required.
     *          'message' => new lang_string(...),   // Defaults to invalid data error message.
     *          'choices' => array(1, 2, 3),         // An array of accepted values (optional).
     *          'internal' => true,                  // Do not expose the property value via the external API (defaults to false).
     *      ],
     *  ];
     *
     * @return array
     */
    public function get_comment_custom_data_field_definition() : array {
        return [];
    }

    /**
     * Validate the content of a comment.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     * @return bool|\lang_string[] true when the validation passed or an array of properties with errors (property => lang_string).
     */
    public function validate_comment_content(comment $comment, capability $capability) {
        // TODO
        return true;
    }

    /**
     * Validate the pseudonym of a comment. This is only called if a pseudonym was set. Use the capability manager to
     * ensure that all comments use a pseudonym.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     * @return bool|\lang_string[] true when the validation passed or an array of properties with errors (property => lang_string).
     */
    public function validate_comment_pseudonym(comment $comment, capability $capability) {
        // TODO
        return true;
    }

    /**
     * Validate the custom data of a comment.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     * @return bool|\lang_string[] true when the validation passed or an array of properties with errors (property => lang_string).
     */
    public function validate_comment_custom_data(comment $comment, capability $capability) {
        // TODO
        return true;
    }

    /**
     * A subclass may modify the comment before it is saved.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     */
    public function modify_comment_before_save(comment $comment, capability $capability) {
        // Do nothing by default. TODO Or enforce pseudonym as given by capability manager?
    }

    /**
     * Get the custom data of a comment in order to send it to a user.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     * @return array one-dimensional key-value array
     */
    public function export_comment_custom_data(comment $comment, capability $capability) : array {
        // TODO check default and export only non-internal properties.
    }

    public static function make_unique_key(string $component, string $commentarea, int $contextid, int $itemid) {
        return $component . '_' . $commentarea . '_' . $contextid . '_' . $itemid;
    }

    public function get_unique_key() : string {
        return self::make_unique_key($this->get_area()->get_component(), $this->get_area()->get_area(), $this->get_context()->id, $this->get_item_id());
    }

    /**
     * Get the capability manager for a user in the section.
     *
     * @param \stdClass|null $user
     * @return capability
     */
    public function get_capability(?\stdClass $user = null) : capability {
        return new capability_simple($this, $user, true);
        // TODO
    }

    /**
     * Get the default subscription status of a user.
     *
     * Defines if the user is subscribed to this comment section by default.
     *
     * @param \stdClass $user
     * @return int one of the \core_comment\subscription::NOTIFICATION_* constants
     */
    public function get_default_subscription_status(\stdClass $user) : int {
        // TODO
        return subscription::NOTIFICATION_OFF;
    }

    /**
     * Get the users that are subscribed to this comment section by default.
     *
     * Return an array that defines whether the users should receive immediate notifications or daily digests.
     * You may return an empty array or the \core_comment\subscription::NOTIFICATION_* constants as the key and the users as values.
     *
     * Example:
     *  return [
     *      \core_comment\subscription::NOTIFICATION_IMMEDIATE => get_users_by_capability($this->area->get_context(), ...),
     *      \core_comment\subscription::NOTIFICATION_DAILY_DIGEST => get_users_by_capability(...)
     *  ];
     *
     * @return array \core_comment\subscription::NOTIFICATION_* => user records
     */
    public function get_auto_subscribed_users() : array {
        return [];
    }

    /**
     * Get render options for this section.
     *
     * This may be used to pass arbitrary data to the JavaScript code that displays the comment section.
     *
     * @return array An array with string keys and string values.
     */
    public function get_section_render_options(): array {
        // TODO
        return [];
    }

    public function get_context() : \context {
        return $this->area->get_context();
    }

    public function enable_votes() : bool {
        // TODO take value from options in db/?
    }

    /**
     * Construct a new comment.
     *
     * This only creates the comment object in memory. You need to call ->save() to store the comment in the database.
     *
     * @param string $content
     * @param int $format
     * @param int $usercreated
     * @param string $pseudonym
     * @param int|null $replytoid
     * @param string $customdatajson
     * @return comment
     */
    public function construct_new_comment(string $content, int $format, int $usercreated, string $pseudonym,
            ?int $replytoid, string $customdatajson) : comment {
        return comment::construct_new($this, $content, $format, $usercreated, $pseudonym, $replytoid, $customdatajson);
    }

    /**
     * Construct a comment object from database data.
     *
     * @param \stdClass $record db data
     * @param comment_search|null $search the search this comment
     * @return comment
     */
    public function construct_comment_from_db(\stdClass $record, ?comment_search $search = null) {
        return comment::construct_from_db($this, $record, $search);
    }

    /**
     * Get a title that shortly describes the item belonging to the item id.
     *
     * This title is displayed in notifications and the list of recent comments.
     *
     * @return string
     */
    abstract public function get_item_title() : string;


    /**
     * Get a URL to the item beloging to the item id.
     *
     * This title is displayed in notifications and the list of recent comments.
     *
     * @return \moodle_url
     */
    abstract public function get_item_url() : \moodle_url;

    /**
     * Get the URL to a comment within the section.
     *
     * @param int $commentid
     * @return \moodle_url
     */
    abstract public function get_comment_url(int $commentid): \moodle_url;  // TODO take item_url by default and append an anchor?
}
