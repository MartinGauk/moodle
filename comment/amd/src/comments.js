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
import Ajax from 'core/ajax';
import * as templates from 'core/templates';

export const init = async() => {
    for (const el in document.querySelectorAll('[data-commentsection]')) {
        const options = {
            contextId: el.dataset.contextid,
            component: el.dataset.component,
            commentArea: el.dataset.commentarea,
            itemId: el.dataset.itemid,
            sortDirection: 'ASC',
            startAtBottom: true,
            fillHeight: true
        };
        if ('modal' in el.dataset) {
            require(['jquery', 'core/modal_factory', 'core_comment/modal_comment_section'],
                function($, ModalFactory, ModalCommentSection) {
                    ModalFactory.create({type: ModalCommentSection.TYPE, large: true, scrollable: false}, $(el))
                        .then((modal) => modal.setOptions(options))
                        .catch(Notification.exception);
                });
        } else {
            await initCommentSection(el, options);
        }
    }
};

export const initCommentSection = async(el, options) => {
    if (!el.commentSection) {
        el.commentSection = await createCommentSection(el, options);
        await el.commentSection.render();
    }
};

const createCommentSection = async(el, options) => {
    options.pageSize = options.pageSize || 10;
    options.sortDirection = (options.sortDirection || 'DESC').toUpperCase();
    let sortDirection = options.sortDirection;
    if (options.startAtBottom) {
        sortDirection = (sortDirection === 'DESC') ? 'ASC' : 'DESC';
    }
    const response = await getComments(
        options.contextId, options.component, options.commentArea, options.itemId,
        options.pageSize + 1, sortDirection
    );
    if (response.comments.length > options.pageSize) {
        if (options.startAtBottom) {
            options.moreAvailableAbove = true;
        } else {
            options.moreAvailableBelow = true;
        }
    }
    options.comments = response.comments.slice(0, options.pageSize);
    if (options.startAtBottom) {
        options.comments.reverse();
    }

    if (options.itemid) {
        options.section = response.commentsections[0];
    }

    options.renderOptions = Object.assign({
        commentsectiontemplate: 'core_comment/comment_section',
        commentlisttemplate: 'core_comment/comment_list',
        commentformtemplate: 'core_comment/comment_form',
        commenttemplate: 'core_comment/comment',
        commentitemlinktemplate: 'core_comment/comment_item_link',
        commentheadertemplate: 'core_comment/comment_header',
        commentbodytemplate: 'core_comment/comment_body',
        commentsectionclass: await import('core_comment/comment_section'),
        commentlistclass: await import('core_comment/comment_list'),
        commentformclass: await import('core_comment/comment_form'),
        commentclass: await import('core_comment/comment'),
        commentitemlinkclass: await import('core_comment/comment_item_link'),
        commentheaderclass: await import('core_comment/comment_header'),
        commentbodyclass: await import('core_comment/comment_body')
    },
        Object.fromEntries(response.renderoptions),
        options.section ? options.section.renderoptions : {},
        options.renderOptions
    );

    // Prefetch all templates (i.e. the values of all render options with keys ending in "template").
    templates.prefetchTemplates(
        Object.entries(options.renderOptions)
            // eslint-disable-next-line no-unused-vars
            .filter(([key, value]) => key.endsWith('template'))
            // eslint-disable-next-line no-unused-vars
            .map(([key, value]) => value)
    );

    return new options.renderOptions.commentsectionclass(el, null, options);
};

export const getComments = async(
    contextId, component, commentArea, itemId,
    pageSize, sortDirection, replyToId = null, timeFrom = null, timeTo = null
) => {
    const response = await Ajax.call([
        {
            methodname: 'core_comment_get_comments', args: {
                contextid: contextId,
                component: component,
                commentarea: commentArea,
                itemid: itemId,
                replytoid: replyToId || undefined,
                timefrom: timeFrom,
                timeto: timeTo,
                pagesize: pageSize,
                sortdirection: sortDirection
            }
        },
    ])[0];

    const sections = Object.fromEntries(response.commentsections.map(section => [section.itemid, section]));
    for (let i = 0; i < response.comments.length; i++) {
        Object.assign(response.comments[i], {section: sections[response.comments[i].itemid]});
    }
    return response;
};

export const saveComment = async(comment) => {
    return await Ajax.call([
        {methodname: comment.id ? 'core_comment_update_comment' : 'core_comment_create_comment', args: {
                comment: {
                    contextid: comment.contextid,
                    component: comment.component,
                    commentarea: comment.commentarea,
                    itemid: comment.itemid,
                    id: comment.id || undefined,
                    replytoid: comment.replytoid || undefined,
                    content: comment.content,
                    pseudonymous: comment.pseudonymous,
                    customdata: comment.customdata || '',
                }
            }},
    ])[0];
};

export const deleteComments = async(commentIds) => {
    return await Ajax.call([
        {methodname: 'core_comment_delete_comments', args: {comments: commentIds}},
    ])[0];
};