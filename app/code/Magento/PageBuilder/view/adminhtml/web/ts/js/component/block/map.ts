/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

import _ from "underscore";
import EventBus from "../event-bus";
import Block from "./block";

export default class Map extends Block {

    /**
     * Open edit menu on map content type drop with a delay of 300ms
     */
    public bindEvents() {
        super.bindEvents();

        EventBus.on("map:block:mount", (event: Event, params: {[key: string]: any}) => {
            if (params.id === this.id) {
                setTimeout(() => {
                    params.block.edit.open();
                    }, 300);
            }
        });
    }

    /**
     * Gets the map styles
     *
     * @returns {object}
     */
    public getStyle() {
        const style: {} = _.clone(super.getStyle());

        return this.hasMarker() ? style : Object.assign(style, {display: "none"});
    }

    /**
     * Check if current map has a marker
     *
     * @returns {boolean}
     */
    private hasMarker() {
        const data = this.getData();
        return data.position !== "";
    }
}