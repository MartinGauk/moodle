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
 * Comment section module.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Component from 'core_comment/component';
import CommentForm from 'core_comment/comment_form';
import CommentList from 'core_comment/comment_list';

class CommentSection extends Component {

    constructor(el, options) {
        super(el);
        this.contextId = options.contextid;
        this.component = options.component;
        this.commentArea = options.commentarea;
        this.itemId = options.itemid;
        this.context = options.commentSection || null;
        this.comments = null;
        if (this.context) {
            this.renderOptions = this.context.renderoptions;
            window.setTimeout(() => this.load());
        } else {
            this.renderOptions = null;
            this.fetchContext().then(() => this.load());
        }
    }

    async load() {
        const template = this.renderOptions.commentsectiontemplate || 'core_comment/comment_section';
        await this.render(template, this.context);
        this.initChildren();
    }

    initChildren() {
        this.commentForm = new CommentForm(this.el.querySelector('[data-commentform]'), this);
        this.commentList = new CommentForm(this.el.querySelector('[data-commentlist]'), this);
        this.children.push(this.commentForm, this.commentList);
    }

    async fetchContext() {
        const response = await Ajax.call([
            {
                methodname: 'core_comment_get_comments', args: {
                    contextid: this.contextid,
                    component: this.component,
                    commentarea: this.commentarea,
                    itemid: this.itemid
                }
            },
        ])[0];
        this.context = response.commentsections[0];
        this.renderOptions = this.context.renderOptions;
        this.comments = response.comments;
    }

}

export const init = async(el, options) => {
    if (!el.commentSection) {
        el.commentSection = new CommentSection(el, options);
    }
    return el.commentSection;
};
