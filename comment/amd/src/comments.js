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
 * Comments module.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as CommentSection from 'core_comment/comment-section';

export const init = () => {
    document.querySelectorAll('.js-comment-section').forEach((el) => {
        const options = {
            contextid: el.dataset.contextid,
            component: el.dataset.component,
            commentarea: el.dataset.commentarea,
            itemid: el.dataset.itemid,
        };
        CommentSection.init(el, options);
    });
};
