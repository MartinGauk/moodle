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
 * @module     core_comment/comments
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';

export default class CommentBody extends Component {

    constructor(el, parent, options = {}) {
        super('commentbody', el, parent);
        this.commentEl = options.commentEl;
        this.comment = options.commentEl.comment;
        this.height = 110;
        this.expanded = false;
    }

    async getContext() {
        return this.comment;
    }

    registerObserver() {
        if (!window.MutationObserver) {
            return;
        }
        this.observer = new MutationObserver(() => this.update);
        this.observer.observe(this.contentElement, {
            attributes: true,
            childList: true,
            characterData: true,
            subtree: true
        });
    }

    update() {
        const collapsible = this.contentElement.clientHeight > this.height;
        this.gradientElement.style.display = (collapsible && !this.expanded) ? 'block' : 'none';
        this.expandElement.style.display = (collapsible && !this.expanded) ? 'inline-block' : 'none';
        this.collapseElement.style.display = (collapsible && this.expanded) ? 'inline-block' : 'none';
        this.showlessElement.style.maxHeight = (collapsible && !this.expanded) ? this.height + 'px' : 'initial';
    }

    expand() {
        this.expanded = true;
        this.update();
    }

    collapse() {
        this.expanded = false;
        this.update();
    }

    async postRender() {
        await this.addChild(`[data-commentcontent="${this.comment.id}"]`, 'commentcontent', {
            commentEl: this.commentEl
        }, true, false);

        this.addListener(`[data-showless-expand="${this.uniqid}"]`, 'click', (e) => {
            this.expand();
            e.preventDefault();
            return false;
        });
        this.addListener(`[data-showless-collapse="${this.uniqid}"]`, 'click', (e) => {
            this.collapse();
            e.preventDefault();
            return false;
        });

        this.showlessElement = this.el.querySelector(`[data-showless="${this.uniqid}"]`);
        this.contentElement = this.el.querySelector(`[data-commentcontent="${this.comment.id}"]`);
        this.gradientElement = this.el.querySelector(`[data-showless-gradient="${this.uniqid}"]`);
        this.expandElement = this.el.querySelector(`[data-showless-expand="${this.uniqid}"]`);
        this.collapseElement = this.el.querySelector(`[data-showless-collapse="${this.uniqid}"]`);

        this.update();
        this.registerObserver();
    }

    async dispose() {
        this.observer.disconnect();
        await super.dispose();
    }
}
