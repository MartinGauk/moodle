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
 * Manager class of the Comment API.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Manager class of the Comment API.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Get a comment area object.
     *
     * @param string $component component name
     * @param string $area comment area name
     * @param \context $context context that this area belongs to
     * @param \stdClass $course course object
     * @return area
     */
    static public function get_comment_area(string $component, string $area, \context $context, \stdClass $course) : area {
        // TODO
    }

    /**
     * Get a comment by its id.
     *
     * @param int $commentid
     * @return comment
     */
    static public function get_comment(int $commentid) : comments_found {
        // TODO
    }

    /**
     * Get the names of the comment areas as defined in component's db/comments.php file.
     *
     * @param string $component
     * @return array names of comment areas
     */
    static public function get_comment_areas_in_component(string $component) : array {
        // TODO
    }

    /**
     * Delete all comments from a component.
     *
     * TODO call this in uninstall_plugin() in lib/adminlib.php
     *
     * @param string $component
     */
    static public function delete_component_comments(string $component) {
        // TODO
    }

    /**
     * Delete all comments in a context and its child contexts.
     *
     * This deletes comments in an efficient way and does not call each delete method on the comment area objects.
     * If a component has some additional book-keeping of comments, it should delete their data first before calling this function.
     *
     * @param \context $context
     */
    static public function delete_comments_in_context(\context $context) {
        // TODO
    }

    /**
     * Delete all votes that a user did.
     *
     * @param int $userid
     */
    static public function delete_user_votes(int $userid) {
        // TODO
    }
}
