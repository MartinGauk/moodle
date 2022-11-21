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
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import * as Str from 'core/str';
import CommentReactive from 'core_comment/reactive';

const COMPACT_LAYOUT_WIDTH = 300;

export const init = async() => {
    for (const el of document.querySelectorAll('[data-commentsection]')) {
        const options = {
            contextId: el.dataset.contextid,
            component: el.dataset.component,
            commentArea: el.dataset.commentarea,
            itemId: el.dataset.itemid,
            sortDirection: el.dataset.sortdirection || 'ASC',
            startAtBottom: !('startatbottom' in el.dataset) || el.dataset.startatbottom !== 'false',
            fillHeight: 'fillheight' in el.dataset ? el.dataset.fillheight !== 'false' : 'modal' in el.dataset,
            maxListHeight: el.dataset.maxlistheight,
            pageSize: el.dataset.pagesize || 10
        };
        if ('modal' in el.dataset) {
            await initCommentModal(el, options);
        } else {
            await initCommentSection(el, options);
        }
    }
};

export const initCommentModal = async(triggerEl, options) => {
    const title = options.itemId === null || options.itemId === undefined ?
        await Str.get_string('recentcomments', 'core_comment') :
        await Str.get_string('comments', 'core');
    return await new Promise((resolve) => {
        require(['jquery', 'core/modal_factory', 'core_comment/modal_comment_section'],
            function($, ModalFactory, ModalCommentSection) {
                ModalFactory.create({
                    title: title,
                    type: ModalCommentSection.TYPE,
                    large: true,
                    scrollable: false
                }, $(triggerEl))
                    .then((modal) => {
                        modal.setOptions(options);
                        resolve(modal);
                        return modal;
                    })
                    .catch(Notification.exception);
            });
    });
};

export const initCommentSection = async(el, options) => {
    if (!el.comments) {
        if (options.compact === null || options.compact === undefined) {
            options.compact = el.offsetWidth < COMPACT_LAYOUT_WIDTH;
        }
        el.comments = new CommentReactive(el, options);
        await el.comments.load();
    }
};