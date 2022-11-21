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
 * Comment reply list module.
 *
 * @module     core_comment/comment_replies
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CommentList from 'core_comment/comment_list';

export default class CommentReplies extends CommentList {

    constructor(descriptor) {
        super(descriptor);
        this.replyToId = Number(this.element.dataset.replytoid);
        if (!Number.isInteger(this.replyToId)) {
            throw new Error('replyToId missing in dataset');
        }
    }

    getWatchers() {
        const replyToId = this.element.dataset.replytoid;
        return [
            {watch: `commentReplies[${replyToId}]:updated`, handler: this.render},
            {watch: `highlight.replyId:updated`, handler: async() => {
                if (this._hasHighlight) {
                    await this.render();
                }
            }},
        ];
    }

    getData() {
        return this.getState().commentReplies.get(this.replyToId);
    }

    getHighlightedComment() {
        if (this.getState().highlight.replyId === null ||
            this.replyToId !== this.getState().highlight.commentId) {
            return null;
        }
        return this.getState().comments.get(this.getState().highlight.replyId);
    }

    getReplyTo() {
        return this.getState().comments.get(this.replyToId);
    }

    async getContext() {
        return Object.assign(await super.getContext(), {
            replyto: this.getReplyTo()
        });
    }

    addIntersectionObserver() {
        // Replies don't need an IntersectionObserver.
    }

    async loadMore(above = null) {
        await this.reactive.dispatch('loadMore', this.replyToId, above);
    }

    async postRender() {
        this._hasHighlight = !!this.getHighlightedComment();
    }
}
