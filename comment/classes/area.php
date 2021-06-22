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
 * Class that represents a comment area in a plugin.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Class that represents one comment area in a plugin.
 *
 * Comments always belong to a context, component, comment area and item id.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class area {
    /** @var string component this area belongs to */
    protected $component;

    /** @var string name of this area */
    protected $area;

    /** @var \stdClass course object */
    protected $course;

    /** @var \context context that this area belongs to */
    protected $context;

    /** @var array options as defined in component's db/comments.php */
    protected $options;

    /**
     * Comment area constructor.
     *
     * Do not use this function directly. You can instantiate this class with \core_comment\manager::get_comment_area().
     *
     * @param string $component component name
     * @param string $area comment area name
     * @param \context $context context that this area belongs to
     * @param \stdClass $course course object
     * @param array $options options as defined in component's db/comments.php
     */
    public function __construct(string $component, string $area, \context $context, \stdClass $course, array $options) {
        $this->component = $component;
        $this->area = $area;
        $this->course = $course;
        $this->context = $context;
        $this->options = $options;
    }

    /**
     * Get the component this area belongs to.
     *
     * @return string
     */
    public function get_component() : string {
        return $this->component;
    }

    /**
     * Get the name of this area.
     *
     * @return string
     */
    public function get_area() : string {
        return $this->area;
    }

    /**
     * Get the course object.
     *
     * @return \stdClass
     */
    public function get_course() : \stdClass {
        return $this->course;
    }

    /**
     * Get the context that this area belongs to.
     *
     * @return \context
     */
    public function get_context(): \context {
        return $this->context;
    }


    /**
     * Get the options as defined in component's db/comments.php.
     *
     * @return array
     */
    public function get_options() : array {
        return $this->options;
    }


    /**
     * Called when the subscription status is modified.
     *
     * A user can subscribe to a specific comment area, to a comment section and to replies of a comment.
     *
     * @param \stdClass $user user object
     * @param int $subscription one of the \core_comment\subscription::NOTIFICATION_* constants
     * @param section|null $section
     * @param comment|null $comment
     */
    public function on_update_subscription_status(\stdClass $user, int $subscription, section $section = null, comment $comment = null) {
        // TODO trigger event?
    }

    /**
     * Fetch the comments that were posted anywhere in the area.
     *
     * Pass a user if only comments should be returned that this user is allowed to view.
     *
     * @param int|null $timefrom only comments that were created at or after this time
     * @param int|null $timeto only comments that were created at or before this time
     * @param int $page
     * @param int $pagesize maximum number of comments to fetch
     * @param string $sortdirection ASC or DESC (comments are ordered by timecreated)
     * @param bool $includechildcontexts also fetch comments from child contexts (only if the context is a \course_context)
     * @param \stdClass|null $user
     * @return comment_search
     */
    public function get_comments_in_area(?int $timefrom, ?int $timeto, int $page, int $pagesize, string $sortdirection,
            bool $includechildcontexts, ?\stdClass $user) : comment_search {
        // TODO
    }

    /**
     * Helper function for get_comments_in_area.
     *
     * This function should return the SQL clauses in order to limit the query in get_comments_in_area to only fetch
     * comments that $user is allowed to view.
     *
     * A plugin needs to override this method if it wants to display the (most recent) comments in a comment area.
     * By default, this method ensures that no comments are returned (since the API does not have any knowledge about the
     * items that a comment refers to).
     *
     * TODO maybe move this to section as a static function?
     *
     * @param \stdClass $user
     * @return null|array with three elements (joins, where, params)
     *     1. joins: any joins with other tables that are needed.
     *     2. where: WHERE clauses
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        used named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
     */
    protected function get_comments_sql_where(\stdClass $user) {
        return null;
    }

    /** MIT DER ANDEREN FUNKTION VEREINEN?
     * Get child context ids in a course.
     *
     * Helper function for get_comments_in_area when fetching comments in child contexts of a course context.
     * When the component is an activity module, this returns the context ids that belong to the plugin and that
     * the user is allowed to view.
     *
     * A plugin needs to override this method if it is not an activity module.
     *
     * @param \stdClass $user user object
     * @return int[] context ids
     */
    protected function get_component_course_child_contextids(\stdClass $user) {
        list($type, $plugin) = \core_component::normalize_component($this->component);
        if ($type === 'mod') {
            $modinfo = get_fast_modinfo($this->course->id, $user->id);
            $cms = $modinfo->get_instances_of($plugin);
            $contextids = [];
            foreach ($cms as $cm) {
                if ($cm->uservisible) {
                    $contextids[] = $cm->context->id;
                }
            }
            return $contextids;
        }

        // Throw exception telling developers that they need to override this method because their component is not a mod?
    }

    /**
     * Delete the whole comment area in the context.
     */
    public function delete() {
        // TODO
    }

    /**
     * Get a comment section (related to an item id) within the comment area.
     *
     * You may pass an item object that belongs to the item id.
     *
     * @param int $itemid
     * @param mixed $item
     * @return section
     */
    public function get_section(int $itemid, $item = null) : section {
        // TODO
    }

    /**
     * Get the renderer for this comment area.
     *
     * @param string $subtype
     * @param string $target one of rendering target constants
     * @return \renderer_base the requested renderer.
     */
    public function get_renderer(?string $subtype = null, ?string $target = null) : \renderer_base {
        global $PAGE;
        return $PAGE->get_renderer('core_comment', $subtype, $target);
    }

    /**
     * Show the most recent comments within a comment area.
     *
     *
     * @param int $pagesize maximum number of comments to show initially (TODO better variable name?)
     * @param bool $includechildcontexts also show comments from child contexts (only if the context is a \course_context)
     * @return string HTML to display
     */
    public function output_recent_comments(int $pagesize, bool $includechildcontexts) : string {
        // TODO move to renderer
    }
}
