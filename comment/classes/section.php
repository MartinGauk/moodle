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
 * Abstract class that represents a comment section in a component.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Abstract class that represents a comment section in a component.
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
     * @param int $displaymode One of \core_comment\output\renderer::DISPLAYMODE_*
     * TODO more options
     * @return string HTML to display
     * @throws \coding_exception
     */
    public function output(int $pagesize, int $displaymode) : string {
        return $this->area->get_renderer()->render(new output\section($this, $displaymode));
    }

    /**
     * Delete all comments in this section.
     *
     * @throws \dml_exception
     */
    public function delete() {
        global $DB;
        $DB->delete_records('comments', [
            'component' => $this->get_area()->get_component(),
            'commentarea' => $this->get_area()->get_area(),
            'contextid' => $this->get_context()->id,
            'itemid' => $this->get_item_id()
        ]);
    }

    /**
     * Get the number of comments in this section or the number of replies to a comment.
     *
     * @param int|null $replytoid comment id
     * @param \stdClass|null $user if specified, only count comments that this user can see
     * @return int
     */
    public function count_comments(?\stdClass $user, int $replytoid = null) : int {
        // TODO
        return $this->get_comments($user, $replytoid)->count();
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
     * @param \stdClass|null $user if specified, only return comments that this user can see
     * @return comment_search
     */
    public function get_comments(?\stdClass $user, ?int $replytoid = null, ?int $timefrom = null, ?int $timeto = null, int $page = 0, int $pagesize = -1, string $sortdirection = 'ASC') : comment_search {
        // TODO
        return new comment_search($this->get_area(), $this, $replytoid, $timefrom, $timeto, $page, $pagesize, $sortdirection, false, false, $user);
    }

    /**
     * Get a comment by its id.
     *
     * @param int $commentid
     * @return comment|null
     * @throws \dml_exception
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
     * Get the definition of the available properties in the custom data of a comment.
     *
     * This allows to store some custom/meta data associated with a comment.
     *
     * @return \external_single_structure|null null if comments in this section do not have custom data
     */
    public function get_comment_custom_data_field_definition() : ?\external_single_structure {
        return null;
    }

    /**
     * Validate the custom data of a comment.
     *
     * Read the custom data of a comment and write back the validated data.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     * @throws \invalid_parameter_exception
     */
    protected function validate_comment_custom_data(comment $comment, capability $capability) {
        $definition = $this->get_comment_custom_data_field_definition();
        if ($definition) {
            $data = \external_api::validate_parameters($definition, $comment->get_custom_data());
            $comment->set_custom_data($data);
        } else {
            $comment->set_custom_data([]);
        }
    }

    /**
     * Validate a comment.
     *
     * A subclass may add more validations, e.g. validate/restrict the pseudonym or content.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     * @throws \invalid_parameter_exception|\comment_exception
     */
    public function validate_comment(comment $comment, capability $capability) {
        if (!strlen($comment->get_content())) {
            throw new \comment_exception(); //TODO error message
        }
        $this->validate_comment_custom_data($comment, $capability);
    }

    /**
     * A subclass may modify the comment before it is saved.
     *
     * This can be used to add internal custom data.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     */
    public function modify_comment_before_save(comment $comment, capability $capability) {
        // Do nothing by default.
    }

    /**
     * Validate a new or updated comment and modify it if necessary.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to save the comment
     * @throws \invalid_parameter_exception
     * @throws \comment_exception
     */
    public final function validate_and_modify_comment(comment $comment, capability $capability) {
        $this->validate_comment($comment, $capability);
        $this->modify_comment_before_save($comment, $capability);
    }

    /**
     * Get the custom data (as JSON) of a comment in order to send it to a user.
     *
     * A subclass may modify the custom data, e.g. removing (internal) data that is not meant for the user.
     *
     * @param comment $comment
     * @param capability $capability capability manager of the user who wants to view the comment
     * @return string JSON encoded data
     */
    public function export_comment_custom_data_json(comment $comment, capability $capability): string {
        return $comment->get_custom_data_json();
    }

    /**
     * Returns a string that uniquely identifies a comment section by its properties.
     *
     * @param string $component
     * @param string $commentarea
     * @param int $contextid
     * @param int $itemid
     * @return string
     */
    public static function make_unique_key(string $component, string $commentarea, int $contextid, int $itemid): string {
        return $component . '_' . $commentarea . '_' . $contextid . '_' . $itemid;
    }

    /**
     * Returns a string that uniquely identifies this comment section.
     *
     * @return string
     */
    public function get_unique_key(): string {
        return self::make_unique_key($this->get_area()->get_component(), $this->get_area()->get_area(), $this->get_context()->id, $this->get_item_id());
    }

    /**
     * Get the capability manager for a user in the section.
     *
     * @param \stdClass $user
     * @return capability
     * @throws \coding_exception
     */
    public function get_capability(\stdClass $user): capability {
        $options = $this->area->get_options();
        $postmodes = ($options['postrealname']) ? capability::POST_REALNAME : 0;
        $postmodes |=  ($options['postpseudonym']) ? capability::POST_PSEUDONYM : 0;
        return new capability_simple($this, $user, true, $options['replies'], $postmodes);
    }

    /**
     * Get render options for this section.
     *
     * This may be used to pass arbitrary data to the JavaScript code that displays the comment section.
     * These override the comment area's render options in the context of this section.
     *
     * @return array An array with string keys and string values.
     */
    public function get_section_render_options(): array {
        // TODO
        return [];
    }

    public function get_context(): \context {
        return $this->area->get_context();
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
     * @param comment|null $replytoid
     * @param string $customdatajson
     * @return comment
     */
    public function construct_new_comment(string $content, int $format, int $usercreated, string $pseudonym,
            ?comment $replyto, string $customdatajson): comment {
        return comment::construct_new($this, $content, $format, $usercreated, $pseudonym, $replyto, $customdatajson);
    }

    /**
     * Construct a comment object from database data.
     *
     * @param \stdClass $record db data
     * @param comment_search|null $search the search this comment
     * @return comment
     */
    public function construct_comment_from_db(\stdClass $record, ?comment_search $search = null): comment {
        return comment::construct_from_db($this, $record, $search);
    }

    /**
     * Checks whether another section object actually represents the same section.
     *
     * @param section $other
     * @return bool
     */
    public function is_equal(section $other): bool {
        if ($this === $other) {
            return true;
        }

        $thisarea = $this->area;
        $otherarea = $other->area;
        return $thisarea->get_component() === $otherarea->get_component() && $thisarea->get_area() === $otherarea->get_area() &&
            $thisarea->get_context()->id === $otherarea->get_context()->id &&
            $this->itemid === $other->itemid;
    }

    /**
     * Get a title that shortly describes the item belonging to the item id.
     *
     * This title is displayed in notifications and the list of recent comments.
     *
     * @return string
     */
    abstract public function get_item_title(): string;


    /**
     * Get a URL to the item beloging to the item id.
     *
     * This title is displayed in notifications and the list of recent comments.
     *
     * @return \moodle_url
     */
    abstract public function get_item_url(): \moodle_url;

    /**
     * Get the URL to a comment within the section.
     *
     * @param int $commentid
     * @return \moodle_url
     */
    public function get_comment_url(int $commentid): \moodle_url {
        $url = $this->get_item_url();
        $url->set_anchor('comment-' . $commentid);
        return $url;
    }

    /**
     * Trigger a comment created/updated/deleted event.
     *
     * @param comment $comment
     * @param string $action created, updated or deleted
     * @return void
     * @throws \coding_exception
     */
    public function trigger_comment_event(comment $comment, string $action) {
        if (!in_array($action, ['created', 'updated', 'deleted'])) {
            throw new \coding_exception('$action must be created, updated or deleted.');
        }

        if (!$comment->get_section()->is_equal($this)) {
            throw new \coding_exception('$comment belongs to another comment section.');
        }

        $options = $this->area->get_options();
        $eventclass = $options['events']['comment' . $action] ?? null;

        if ($eventclass) {
            $event = $eventclass::create([
                'context' => $this->get_context(),
                'objectid' => $comment->get_id(),
                'other' => [
                    'itemid' => $this->itemid,
                ],
                'anonymous' => $comment->is_pseudonymous_author(),
            ]);
            $event->trigger();
        }
    }

    /**
     * Trigger a comments viewed event.
     *
     * By default, no event is triggered.
     *
     * @return void
     */
    public function trigger_comments_viewed_event() {
    }
}
