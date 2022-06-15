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
    /** @var array defaults for the comment areas options in component's db/comments.php file */
    const DEFAULT_OPTIONS = [
        'areaclass' => '\\core_comment\\area',
        'sectionclass' => '\\core_comment\\section',
        'votes' => true,
        'subscriptions' => true,
        'replies' => false,
        'postrealname' => true,
        'postpseudonym' => false,

        // Users can view the (most recent) comments in all comment sections within a context and the area.
        'viewallcommentsinarea' => false,

        // Users can also view all comments in child contexts.
        'viewchildcontexts' => false,

        'events' => [
            'commentcreated' => null,
            'commentupdated' => null,
            'commentdeleted' => null,
        ],
    ];

    /** @var array cached comment area definitions (component name => area name => area options) */
    static private $cachedareas = [];


    /**
     * Get a comment area object.
     *
     * @param string $component component name
     * @param string $area comment area name
     * @param \context $context context that this area belongs to
     * @return area
     */
    static public function get_comment_area(string $component, string $area, \context $context) : area {
        $areas = self::get_component_comment_area_definitions($component);
        if (isset($areas[$area])) {
            $class = $areas[$area]['areaclass'];
            return new $class($component, $area, $context, $areas[$area]);
        }

        throw new \coding_exception("Component {$component} has no comment area with the name {$area}.");
    }

    /**
     * Get definitions of component's comment areas.
     *
     * @param string $component
     * @return array comment area name => options
     */
    static private function get_component_comment_area_definitions(string $component) {
        if (isset(self::$cachedareas[$component])) {
            return self::$cachedareas[$component];
        }

        $file = \core_component::get_component_directory($component) . '/db/comments.php';
        if (file_exists($file)) {
            $commentareas = [];
            include($file);

            foreach ($commentareas as $area => $options) {
                $commentareas[$area] = array_merge(self::DEFAULT_OPTIONS, $options);
            }

            self::$cachedareas[$component] = $commentareas;
            return self::$cachedareas[$component];
        } else {
            return [];
        }
    }

    /**
     * Get a comment section object.
     *
     * @param string $component component name
     * @param string $area comment area name
     * @param \context $context context that this area belongs to
     * @param int $itemid
     * @param mixed|null $item
     * @return section
     */
    static public function get_comment_section(string $component, string $area, \context $context, int $itemid, $item = null) : section {
        return self::get_comment_area($component, $area, $context)->get_section($itemid, $item);
    }

    /**
     * Get a comment by its id.
     *
     * @param int $commentid
     * @return comment|null
     */
    static public function get_comment(int $commentid) : ?comment {
        global $DB, $SITE;
        $record = $DB->get_record('comments', ['id' => $commentid]);
        if (!$record) {
            return null;
        }

        $context = \context::instance_by_id($record->contextid);
        $area = self::get_comment_area($record->component, $record->commentarea, $context);
        $section = $area->get_section($record->itemid);
        return $section->construct_comment_from_db($record);
    }

    /**
     * Get the names of the comment areas as defined in component's db/comments.php file.
     *
     * @param string $component
     * @return array names of comment areas
     */
    static public function get_comment_areas_in_component(string $component) : array {
        return array_keys(self::get_component_comment_area_definitions($component));
    }

    /**
     * Delete all comments from a component.
     *
     * This is called by uninstall_plugin() in lib/adminlib.php.
     *
     * @param string $component
     */
    static public function delete_component_comments(string $component) {
        global $DB;
        $DB->delete_records('comments', ['component' => $component]);
    }

    /**
     * Delete all comments in a context and optionally its child contexts.
     *
     * This deletes comments in an efficient way and does not call each delete method on the comment area objects.
     * TODO Das wird uns allerdings Probleme bereiten, wenn wir auch Dateien in den Kommentaren erlauben wollen,
     *      da der file storage keine Möglichkeit bietet, auch alle Dateien in child contexts zu löschen.
     *      Wir sollten also lieber unique Paare (component, area, contextid) holen und auf jeder area delete aufrufen.
     *
     * @param \context $context
     * @param bool $includechildcontexts do also delete comments in child contexts
     */
    static public function delete_comments_in_context(\context $context, bool $includechildcontexts = false) {
        global $DB;

        $contextids = [$context->id];
        if ($includechildcontexts) {
            foreach ($context->get_child_contexts() as $childctx) {
                $contextids[] = $childctx->id;
            }
        }
        $DB->delete_records_list('comments', 'context', $contextids);
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
