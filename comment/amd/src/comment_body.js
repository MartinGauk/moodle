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
 * Comment body module.
 *
 * @module     core_comment/comment_body
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';

export default class CommentBody extends Component {

    constructor(descriptor) {
        super(descriptor);
        this.commentId = Number(this.element.dataset.commentid);
        if (!Number.isInteger(this.commentId)) {
            throw new Error('commentId missing in dataset');
        }
        if (!Number.isInteger(this.height)) {
            this.height = 110;
        }
    }

    create() {
        this.selectors = {
            COMMENT_CONTENT: `[data-for="commentcontent"]`,
            SHOW_LESS: `[data-for="showless"]`,
            SHOW_LESS_GRADIENT: `[data-for="showless-gradient"]`,
            SHOW_LESS_EXPAND: `[data-for="showless-expand"]`,
            SHOW_LESS_COLLAPSE: `[data-for="showless-collapse"]`
        };
    }

    getWatchers() {
        const commentId = this.element.dataset.commentid;
        return [
            {watch: `comments[${commentId}].expanded:updated`, handler: this.update},
            {watch: `comments[${commentId}].content:updated`, handler: this.update},
        ];
    }

    getComment() {
        return this.getState().comments.get(this.commentId);
    }

    async getContext() {
        return this.getComment();
    }

    registerObserver() {
        if (!window.MutationObserver) {
            return;
        }
        this.observer = new MutationObserver(() => this.update);
        this.observer.observe(this.getElement(this.selectors.COMMENT_CONTENT), {
            attributes: true,
            childList: true,
            characterData: true,
            subtree: true
        });
    }

    update() {
        const expanded = this.getComment().expanded;
        const collapsible = this.getElement(this.selectors.COMMENT_CONTENT).clientHeight > this.height;
        this.getElement(this.selectors.SHOW_LESS_GRADIENT).style.display = (collapsible && !expanded) ? 'block' : 'none';
        this.getElement(this.selectors.SHOW_LESS_EXPAND).style.display = (collapsible && !expanded) ? 'inline-block' : 'none';
        this.getElement(this.selectors.SHOW_LESS_COLLAPSE).style.display = (collapsible && expanded) ? 'inline-block' : 'none';
        this.getElement(this.selectors.SHOW_LESS).style.maxHeight = (collapsible && !expanded) ? this.height + 'px' : 'initial';
    }

    async addListeners() {
        this.addListener(this.selectors.SHOW_LESS_EXPAND, 'click', (e) => {
            this.reactive.dispatch('setCommentExpanded', this.commentId, true);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.SHOW_LESS_COLLAPSE, 'click', (e) => {
            this.reactive.dispatch('setCommentExpanded', this.commentId, false);
            e.preventDefault();
            return false;
        });
    }

    async addChildren() {
        const content = await this.addChild(this.selectors.COMMENT_CONTENT, 'commentcontent');
        await content.renderPromise;
    }

    async postRender() {
        this.update();
        this.registerObserver();
    }

    async destroy() {
        this.observer.disconnect();
        await super.destroy();
    }
}
