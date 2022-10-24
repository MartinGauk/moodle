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
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';

export default class CommentSection extends Component {

    constructor(descriptor) {
        super(descriptor);
        this.startAtBottom = this.element.dataset.startatbottom === 'true';
        this.fillHeight = this.element.dataset.fillheight === 'true';
        this.maxListHeight = Number(this.element.dataset.maxlistheight);
        this.compact = this.element.dataset.compact === 'true';
        if (!Number.isInteger(this.maxListHeight)) {
            this.maxListHeight = null;
        }
    }

    create() {
        this.selectors = {
            COMMENT_FORM: `[data-for="commentform"]`,
            COMMENT_LIST: `[data-for="commentlist"]`
        };
    }

    getSection() {
        return this.getState().section || null;
    }

    async getContext() {
        const section = this.getSection();
        return {
            startatbottom: this.startAtBottom,
            fillheight: this.fillHeight,
            maxlistheight: this.maxListHeight,
            showform: section && section.canpost,
            compact: this.compact,
            section: section
        };
    }

    async addChildren() {
        this.commentFormEl = await this.addChild(this.selectors.COMMENT_FORM, 'commentform');
        this.commentListEl = await this.addChild(this.selectors.COMMENT_LIST, 'commentlist');
    }

}