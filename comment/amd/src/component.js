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

export default class Component {

    constructor(el) {
        this.el = el;
        this.children = {};
    }

    async getTemplate() {
        throw new Error('Not implemented.');
    }

    async getContext() {
        return {};
    }

    async preRender(template, context) {
        return context;
    }

    async postRender() {
        // Nop.
    }

    async render() {
        const template = await this.getTemplate();
        const context = await this.preRender(template, await this.getContext());
        // eslint-disable-next-line no-console
        console.log('rendering template ' + template + ' with context ', context);
        const html = await templates.render(template, context);
        this.detachChildren();
        templates.replaceNodeContents(this.el, html, '');
        await this.postRender();
    }

    getChildren() {
        return Object.values(this.children);
    }

    detachChildren() {
        this.getChildren().forEach((child) => {
            if (child.el.parentElement) {
                child.el.parentElement.removeChild(child.el);
            }
        });
    }

    addChild(selector, childCallback) {
        const childEl = this.el.querySelector(selector);
        if (childEl) {
            if (this.children[selector]) {
                const child = this.children[selector];
                childEl.replaceWith(child.el);
                return child;
            } else {
                const child = childCallback(childEl);
                this.children[selector] = child;
                return child;
            }
        }
        return null;
    }

    removeChild(child) {
        Object.entries(this.children)
            // eslint-disable-next-line no-unused-vars
            .filter(([key, value]) => value === child)
            // eslint-disable-next-line no-unused-vars
            .forEach(([key, value]) => {
                this.children[key].dispose();
                delete this.children[key];
            });
    }

    addListener(selector, event, callback) {
        const targetEl = this.el.querySelector(selector);
        if (targetEl) {
            targetEl.addEventListener(event, callback);
        }
    }

    focus() {
        this.el.focus();
    }

    async disposeChildren() {
        await Promise.all(this.getChildren().map((child) => child.dispose()));
        this.children = {};
    }

    async dispose() {
        await this.disposeChildren();
    }
}