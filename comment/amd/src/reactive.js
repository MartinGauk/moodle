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
 * Comment reactive module.
 *
 * @module     core_comment/reactive
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Reactive} from 'core/reactive';
import CommentMutations from 'core_comment/mutations';
import {eventTypes, notifyStateChanged} from 'core_comment/events';
import * as templates from 'core/templates';

const COMMENT_HASH_PREFIX = '#comment-';

let counter = 0;

export default class extends Reactive {

    constructor(target, options) {
        options.pageSize = options.pageSize || 10;
        options.sortDirection = (options.sortDirection || 'DESC').toUpperCase();
        super({
            name: `Comments${counter++}`,
            eventName: eventTypes.stateChanged,
            eventDispatch: notifyStateChanged,
            target: target,
            mutations: new CommentMutations(options),
            state: {
                section: undefined,
                sections: [],
                comments: [],
                commentList: {
                    comments: [],
                    startatbottom: options.startAtBottom,
                    sortdirection: options.sortDirection,
                    pagesize: options.pageSize,
                    moreavailableabove: false,
                    moreavailablebelow: true,
                },
                commentReplies: [],
                highlight: {
                    commentId: null,
                    replyId: null
                },
            }
        });
        this.options = options;
        this.renderOptions = {
            commentsectiontemplate: 'core_comment/comment_section',
            commentlisttemplate: 'core_comment/comment_list',
            commentrepliestemplate: 'core_comment/comment_list',
            commentformtemplate: 'core_comment/comment_form',
            commenttemplate: 'core_comment/comment',
            commentitemlinktemplate: 'core_comment/comment_item_link',
            commentheadertemplate: 'core_comment/comment_header',
            commentbodytemplate: 'core_comment/comment_body',
            commentcontenttemplate: 'core_comment/comment_content',
            commenthighlighttemplate: 'core_comment/comment_highlight',
            commentsectionclass: 'core_comment/comment_section',
            commentlistclass: 'core_comment/comment_list',
            commentrepliesclass: 'core_comment/comment_replies',
            commentformclass: 'core_comment/comment_form',
            commentclass: 'core_comment/comment',
            commentitemlinkclass: 'core_comment/comment_item_link',
            commentheaderclass: 'core_comment/comment_header',
            commentbodyclass: 'core_comment/comment_body',
            commentcontentclass: 'core_comment/comment_content',
            commenthighlightclass: 'core_comment/comment_highlight'
        };
    }

    _getHighlightedCommentFromHash(hash) {
        if (!hash.startsWith(COMMENT_HASH_PREFIX)) {
            return null;
        }
        const id = Number(hash.substr(COMMENT_HASH_PREFIX.length));
        if (!Number.isInteger(id)) {
            return null;
        }
        return id;
    }

    async _prefetchTemplates() {
        // Prefetch all templates (i.e. the values of all render options with keys ending in "template").
        templates.prefetchTemplates(
            Object.entries(this.renderOptions)
                // eslint-disable-next-line no-unused-vars
                .filter(([key, value]) => key.endsWith('template'))
                // eslint-disable-next-line no-unused-vars
                .map(([key, value]) => value)
        );
    }

    async _preloadClasses() {
        // Import all classes (i.e. the values of all render options with keys ending in "class").
        for (const key in this.renderOptions) {
            if (key.endsWith('class') && typeof (this.renderOptions[key]) === 'string') {
                const className = this.renderOptions[key];
                this.renderOptions[key] = await import(className);
            }
        }
    }

    async load() {
        const highlightedCommentId = 'highlightedCommentId' in this.options ?
            this.options.highlightedCommentId : this._getHighlightedCommentFromHash(window.location.hash);

        await Promise.all([
            this.dispatch('setHighlightedComment', highlightedCommentId),
            this.dispatch('loadMore').then(async() => {
                Object.assign(this.renderOptions,
                    this.stateManager.state.section ? this.stateManager.state.section.renderoptions : {},
                    this.options.renderOptions || {}
                );
                return Promise.all([
                    this._prefetchTemplates(),
                    this._preloadClasses()
                ]);
            })
        ]);

        if (this.options.startAtBottom != undefined) {
            this.target.dataset.startatbottom = !!this.options.startAtBottom;
        }
        if (this.options.fillHeight != undefined) {
            this.target.dataset.fillheight = !!this.options.fillHeight;
        }
        if (this.options.maxListHeight != undefined) {
            this.target.dataset.maxlistheight = this.options.maxListHeight;
        }
        if (this.options.compact != undefined) {
            this.target.dataset.compact = !!this.options.compact;
        }

        this.commentSection = new this.renderOptions.commentsectionclass({
            element: this.target,
            name: 'commentsection',
            reactive: this
        });
    }

}