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

import CommentSection from 'core_comment/comment_section';
import Notification from 'core/notification';

export const init = () => {
    document.querySelectorAll('[data-commentsection]').forEach((el) => {
        const options = {
            contextid: el.dataset.contextid,
            component: el.dataset.component,
            commentarea: el.dataset.commentarea,
            itemid: el.dataset.itemid,
            sortDirection: 'ASC',
            startAtBottom: true
        };
        if ('modal' in el.dataset) {
            require(['jquery', 'core/modal_factory', 'core_comment/modal_comment_section'],
                function($, ModalFactory, ModalCommentSection) {
                    ModalFactory.create({type: ModalCommentSection.TYPE, large: true, scrollable: false}, $(el))
                        .then((modal) => modal.setOptions(options))
                        .catch(Notification.exception);
                });
        } else {
            initCommentSection(el, options);
        }
    });
};

export const initCommentSection = (el, options) => {
    if (!el.commentSection) {
        el.commentSection = new CommentSection(el, options);
    }
};