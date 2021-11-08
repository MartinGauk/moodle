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
import Notification from 'core/notification';

export default class Component {

    constructor(name, el, parent) {
        this.name = name;
        this.el = el;
        this.parent = parent;
        if (parent) {
            this.renderOptions = parent.renderOptions;
        } else {
            this.renderOptions = {};
        }
        this.children = {};
    }

    async getTemplate() {
        const templateKey = this.name + 'template';
        if (!(templateKey in this.renderOptions)) {
            throw new Error('Template key not found in renderoptions: ' + templateKey);
        }
        return this.renderOptions[templateKey];
    }

    async getContext() {
        return {};
    }

    async preRender(template, context) {
        return context;
    }

    // eslint-disable-next-line no-unused-vars
    async postRender(template, context) {
        // Nop.
    }

    async render() {
        try {
            const template = await this.getTemplate();
            let context = await this.getContext();
            // Copy context to prevent accidental modification of the original object.
            context = Object.assign({}, context);
            context = await this.preRender(template, context);
            context = this.callback('prerender', [template, context]) || context;

            // TODO remove logging
            // eslint-disable-next-line no-console
            console.log('rendering template ' + template + ' with context ', context);
            const {html, js} = await templates.renderForPromise(template, context);

            this.detachChildren();
            templates.replaceNodeContents(this.el, html, js);

            await this.postRender(template, context);
            this.callback('postrender', [template, context, this.el]);

            // TODO remove logging
            // eslint-disable-next-line no-console
            console.log('postrender template ' + template + ' with context ', context);
        } catch (e) {
            Notification.exception(e);
        }
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

    async addChild(selector, childName, options = {}, render = true) {
        const childEl = this.el.querySelector(selector);
        if (childEl) {
            if (this.children[selector]) {
                const child = this.children[selector];
                childEl.replaceWith(child.el);
                return child;
            } else {
                const childClassKey = childName + 'class';
                if (!(childClassKey in this.renderOptions)) {
                    throw new Error('Component class key not found in renderoptions: ' + childClassKey);
                }
                const child = new this.renderOptions[childClassKey](childEl, this, options);
                this.children[selector] = child;
                if (render) {
                    await child.render();
                }
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

    callback(callbackName, args, defaultValue = undefined) {
        const callbackKey = this.name + callbackName;
        if (this.renderOptions[callbackKey]) {
            const type = typeof this.renderOptions[callbackKey];
            if (type !== 'function') {
                throw new Error("Expected callback function to be of type 'function', got '" + type + "' instead.");
            }
            try {
                return this.renderOptions[callbackKey](...args);
            } catch (e) {
                Notification.exception(e);
            }
        }
        return defaultValue;
    }

    async disposeChildren() {
        await Promise.all(this.getChildren().map((child) => child.dispose()));
        this.children = {};
    }

    async dispose() {
        await this.disposeChildren();
    }
}