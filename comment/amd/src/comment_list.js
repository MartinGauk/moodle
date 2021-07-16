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
 * Comment list module.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import Comment from 'core_comment/comment';
import Ajax from 'core/ajax';

export default class CommentList extends Component {

    constructor(el, commentSection, replyTo = null, pageSize = 10, sortDirection = 'DESC', autoload = false) {
        super(el);
        this.commentSection = commentSection;
        this.renderOptions = commentSection.renderOptions;
        this.replyTo = replyTo;
        this.sortDirection = sortDirection;
        this.comments = [];
        this.moreAvailableBefore = false;
        this.moreAvailableAfter = true;
        this.pageSize = pageSize;
        if (autoload) {
            this.loadMore();
        } else {
            window.setTimeout(() => this.render());
        }
    }

    async getTemplate() {
        return this.renderOptions.commentlisttemplate;
    }

    async getContext() {
        return {
            replyto: this.replyTo ? this.replyTo.comment : null,
            comments: this.comments || [],
            count: this.comments ? this.comments.length : 0,
            moreavailablebefore: this.moreAvailableBefore,
            moreavailableafter: this.moreAvailableAfter
        };
    }

    async loadComments(pageSize, timeFrom = null, timeTo = null) {
        const response = await Ajax.call([
            {
                methodname: 'core_comment_get_comments', args: {
                    contextid: this.commentSection.contextId,
                    component: this.commentSection.component,
                    commentarea: this.commentSection.commentArea,
                    itemid: this.commentSection.itemId,
                    replytoid: this.replyTo ? this.replyTo.comment.id : undefined,
                    timefrom: timeFrom,
                    timeto: timeTo,
                    pagesize: pageSize,
                    sortdirection: this.sortDirection
                }
            },
        ])[0];
        return response.comments;
    }

    async loadMore(before = false) {
        if (!this.comments || !this.comments.length) {
            const newComments = await this.loadComments(this.pageSize + 1);
            this.moreAvailableBefore = false;
            this.moreAvailableAfter = newComments.length > this.pageSize;
            this.comments = newComments.slice(0, this.pageSize);
            await this.render();
            return;
        }

        let time;
        let overlap = 0;
        if (before) {
            time = this.comments[0].timecreated;
            while (this.comments[overlap].timecreated === time) {
                overlap++;
            }
        } else {
            time = this.comments[this.comments.length - 1].timecreated;
            while (this.comments[this.comments.length - 1 - overlap].timecreated === time) {
                overlap++;
            }
        }

        let timeFrom = null;
        let timeTo = null;
        if ((this.sortDirection.toUpperCase() === 'DESC') !== before) {
            timeTo = time;
        } else {
            timeFrom = time;
        }
        let pageSize = this.pageSize + overlap + 1;
        const newComments = await this.loadComments(pageSize, timeFrom, timeTo);
        const moreAvailable = newComments.length === pageSize;
        if (before) {
            this.moreAvailableBefore = moreAvailable;
            this.comments.unshift(...newComments.slice(-this.pageSize - overlap, -overlap));
        } else {
            this.moreAvailableAfter = moreAvailable;
            this.comments.push(...newComments.slice(overlap, this.pageSize + overlap));
        }

        await this.render();
    }

    async removeComment(id) {
        for (let i = 0; i < this.comments.length; i++) {
            if (this.comments[i].id === id) {
                this.comments.splice(i, 1);
                break;
            }
        }
        await this.render();
        this.getChildren().filter((child) => child instanceof Comment && child.comment.id === id).forEach((child) => {
            child.dispose();
            this.removeChild(child);
        });
    }

    async insertComment(comment) {
        if (this.sortDirection === 'DESC') {
            this.comments.unshift(comment);
        } else {
            this.comments.push(comment);
        }
        await this.render();
    }

    async postRender() {
        this.comments.forEach((comment) => {
            this.addChild(`[data-comment="${comment.id}"]`, (el) => new Comment(el, this, comment));
        });
        this.addListener('[data-loadmorebefore]', 'click', (e) => {
            this.loadMore(true);
            e.preventDefault();
            return false;
        });
        this.addListener('[data-loadmoreafter]', 'click', (e) => {
            this.loadMore(false);
            e.preventDefault();
            return false;
        });
    }

}
