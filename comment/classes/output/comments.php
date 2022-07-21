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
 * Comment section renderable.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Comments renderable. Can render either a comment section or recent comments in an area.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comments implements \renderable, \templatable {

    /** @var \core_comment\area The comment area to render */
    public $area;
    /** @var \core_comment\section|null The comment section to render */
    public $section;
    /** @var \stdClass|null Display options. Refer to constructor for further information. */
    public $displayoptions;

    /**
     * Comments renderable constructor.
     *
     * @param \core_comment\area $area Comment area to get comments from.
     * @param \core_comment\section|null $section Comment section to show. Recent comments from the area will be shown
     * if this is left empty.
     * @param \stdClass|null $displayoptions Object with the following optional properties:
     * <ul>
     * <li>int <b>displaymode</b>: One of \core_comment\output\renderer::DISPLAYMODE_*. Defaults to DISPLAYMODE_MODAL.</li>
     * <li>bool <b>fillheight</b>: Whether the comment section will fill the available height. Defaults to true for modals.</li>
     * <li>bool <b>startatbottom</b>: Whether the comment form is below the comment list. Defaults to true if fillheight is true.</li>
     * <li>string <b>sortdirection</b>: One of 'ASC' and 'DESC'. Comments are sorted based on creation date. Defaults to 'DESC' if startatbottom is false and 'ASC' otherwise.</li>
     * <li>int <b>maxlistheight</b>: Maximum height of the list of comments in pixels.</li>
     * <li>int <b>pagesize</b>: Number of comments to load at the same time. Defaults to 10.</li>
     * <li>bool <b>showpictures</b>: Whether to show profile pictures. Defaults to true.</li>
     * </ul>
     */
    public function __construct(\core_comment\area $area, \core_comment\section $section = null, \stdClass $displayoptions = null) {
        $this->area = $area;
        $this->section = $section;
        $this->displayoptions = $displayoptions;
    }

    protected function count_comments(): int {
        global $USER;
        if ($this->section) {
            return $this->section->count_comments($USER);
        } else {
            return $this->area->count_comments_in_area($USER);
        }
    }

    protected function getModalButtonText() {
        $ncomments = $this->count_comments();
        if ($this->section) {
            $str = $ncomments ? 'showcomments' : 'shownocomments';
        } else {
            $str = $ncomments ? 'showrecentcomments' : 'shownorecentcomments';
        }
        return get_string($str, 'core_comment', $ncomments);
    }

    /**
     * Export this data, so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $renderer the render to be used for formatting the message
     * @return array data ready for use in a mustache template
     */
    public function export_for_template(\renderer_base $renderer) : array {
        $o = $this->displayoptions;
        $modal = !property_exists($o, 'displaymode') || $o->displaymode === renderer::DISPLAYMODE_MODAL;
        $modalbuttontext = $modal ? $this->getModalButtonText() : null;
        $fillheight = property_exists($o, 'fillheight') ? $o->fillheight : $modal;
        $startatbottom = property_exists($o, 'startatbottom') ? $o->startatbottom : $fillheight;
        $sortdirection = property_exists($o, 'sortdirection') ?
            $o->sortdirection : ($startatbottom ? 'ASC' : 'DESC');
        // TODO validate values (sortdirection, max pagesize etc)
        return [
            'component' => $this->area->get_component(),
            'area' => $this->area->get_area(),
            'contextid' => $this->area->get_context()->id,
            'section' => $this->section,
            'itemid' => $this->section ? $this->section->get_item_id() : null,
            'modal' => $modal,
            'fillheight' => $fillheight,
            'startatbottom' => $startatbottom,
            'sortdirection' => $sortdirection,
            'maxlistheight' => $o->maxlistheight ?? 0,
            'pagesize' => $o->pagesize ?? 10,
            'showpictures' => property_exists($o, 'showpictures') ? $o->showpictures : true,
            'modalbuttontext' => $modalbuttontext
        ];
    }
}
