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
 * Comment component module.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as templates from 'core/templates';

export class Component {
    constructor(el) {
        this.el = el;
        this.children = [];
    }

    async preRender(template, context) {
        return context;
    }

    async postRender() {
        // Nop.
    }

    async render(template, context) {
        context = this.preRender(template, context);
        const html = await templates.render(template, context);
        await this.disposeChildren();
        templates.replaceNodeContents(this.el, html, '');
        await this.postRender();
    }

    async disposeChildren() {
        await Promise.all(this.children.map((child) => child.dispose()));
        this.children = [];
    }

    async dispose() {
        await this.disposeChildren();
    }
}