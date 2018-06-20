/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

import $t from "mage/translate";
import Config from "../../config";
import ContentTypeCollectionInterface from "../../content-type-collection.d";
import ColumnPreview from "../column/preview";
import {updateColumnWidth} from "../column/resize";
import ColumnGroupPreview from "./preview";

/**
 * Retrieve default  grid size
 *
 * @returns {number}
 */
export function getDefaultGridSize(): number {
    return parseInt(Config.getConfig<string>("column_grid_default"), 10);
}

/**
 * Retrieve the max grid size
 *
 * @returns {number}
 */
export function getMaxGridSize(): number {
    return parseInt(Config.getConfig<string>("column_grid_max"), 10);
}

/**
 * Apply the new grid size, adjusting the existing columns as needed.
 *
 * Rules for resizing the grid:
 *  - The grid size can be increased up to the configured maximum value.
 *  - The grid size can be decreased only if the number of non-empty columns is less than or equal to the new size.
 *  - If the new grid size is less than the number of columns currently in the grid, empty columns will be deleted
 *    to accommodate the new size.
 *
 * @param {ContentTypeCollectionInterface<Preview>} columnGroup
 * @param {number} newGridSize
 */
export function resizeGrid(columnGroup: ContentTypeCollectionInterface<ColumnGroupPreview>, newGridSize: number) {
    validateNewGridSize(columnGroup, newGridSize);

    // if we have more columns than the new grid size allows, remove empty columns until we are at the correct size
    if (newGridSize < columnGroup.getChildren()().length) {
        removeEmptyColumnsToFit(columnGroup, newGridSize);
    }

    // update column widths
    redistributeColumnWidths(columnGroup, newGridSize);
}

/**
 * Validate that the new grid size is within the configured limits and can be achieved.
 *
 * @param {ContentTypeCollectionInterface<Preview>} columnGroup
 * @param {number} newGridSize
 */
function validateNewGridSize(columnGroup: ContentTypeCollectionInterface<ColumnGroupPreview>, newGridSize: number) {
    // Validate against the max grid size
    if (newGridSize > getMaxGridSize()) {
        throw new GridSizeError($t(`The maximum grid size supported is ${getMaxGridSize()}.`));
    } else if (newGridSize < numColumns) {
        throw new GridSizeError($t("Grid size cannot be smaller than the number of columns."));
    }

    // Validate that the operation will be successful
    const currentGridSize = parseInt(columnGroup.dataStore.getKey("gridSize").toString(), 10);
    if (newGridSize < currentGridSize && columnGroup.getChildren()().length > newGridSize) {
        let numEmptyColumns = 0;
        columnGroup.getChildren()().forEach(
            (column: ContentTypeCollectionInterface<ColumnPreview>) => {
                if (column.getChildren()().length === 0) {
                    numEmptyColumns++;
                }
            });
        if (newGridSize < currentGridSize - numEmptyColumns) {
            throw new GridSizeError(
                $t("Grid size cannot be smaller than the current total amount of columns, minus any empty columns."),
            );
        }
    }
}

/**
 * Remove empty columns so we can accommodate the new grid size
 *
 * @param {ContentTypeCollectionInterface<Preview>} columnGroup
 * @param {number} newGridSize
 */
function removeEmptyColumnsToFit(columnGroup: ContentTypeCollectionInterface<ColumnGroupPreview>, newGridSize: number) {
    let numColumns = columnGroup.getChildren()().length;
    columnGroup.getChildren()().forEach((column: ContentTypeCollectionInterface<ColumnPreview>) => {
        if (newGridSize < numColumns && column.getChildren()().length === 0) {
            columnGroup.removeChild(column);
            numColumns--;
        }
    });
}

/**
 * Adjust columns widths across the new grid size, making sure each column is at least one grid size in width
 * and the entire grid size is distributed.
 *
 * @param {ContentTypeCollectionInterface<Preview>} columnGroup
 * @param {number} newGridSize
 */
function redistributeColumnWidths(
    columnGroup: ContentTypeCollectionInterface<ColumnGroupPreview>, newGridSize: number,
) {
    const resizeUtils = columnGroup.preview.getResizeUtils();
    const minColWidth = parseFloat((100 / newGridSize).toString()).toFixed(
        Math.round(100 / newGridSize) !== 100 / newGridSize ? 8 : 0,
    );
    let totalNewWidths = 0;
    const numColumns = columnGroup.getChildren()().length;
    columnGroup.getChildren()().forEach(
        (column: ContentTypeCollectionInterface<ColumnPreview>, index: number) => {
            let newWidth = (100 * Math.floor((resizeUtils.getColumnWidth(column) / 100) * newGridSize) / newGridSize)
                .toFixed(Math.round(100 / newGridSize) !== 100 / newGridSize ? 8 : 0);

            // make sure the column is at least one grid size wide
            if (parseFloat(newWidth) < parseFloat(minColWidth)) {
                newWidth = minColWidth;
            }
            // make sure we leave enough space for other columns
            const maxAvailableWidth = 100 - totalNewWidths - ((numColumns - index - 1) * parseFloat(minColWidth));
            if (parseFloat(newWidth) > maxAvailableWidth) {
                newWidth = maxAvailableWidth.toFixed(Math.round(100 / newGridSize) !== 100 / newGridSize ? 8 : 0);
            }
            totalNewWidths += parseFloat(newWidth);
            updateColumnWidth(column, parseFloat(newWidth));
        },
    );

    // persist new grid size so upcoming calls to get column widths are calculated correctly
    columnGroup.dataStore.update(newGridSize, "gridSize");

    // apply leftover columns if the new grid size did not distribute evenly into existing columns
    if (Math.round(resizeUtils.getColumnsWidth()) < 100) {
        applyLeftoverColumns(columnGroup, newGridSize);
    }
}

/**
 * Make sure the full grid size is distributed across the columns
 *
 * @param {ContentTypeCollectionInterface<Preview>} columnGroup
 * @param {number} newGridSize
 */
function applyLeftoverColumns(columnGroup: ContentTypeCollectionInterface<ColumnGroupPreview>, newGridSize: number) {
    const resizeUtils = columnGroup.preview.getResizeUtils();
    const minColWidth = parseFloat((100 / newGridSize).toString()).toFixed(
        Math.round(100 / newGridSize) !== 100 / newGridSize ? 8 : 0,
    );
    let column: ContentTypeCollectionInterface<ColumnPreview>;
    for (column of (columnGroup.getChildren()() as Array<ContentTypeCollectionInterface<ColumnPreview>>)) {
        if (Math.round(resizeUtils.getColumnsWidth()) < 100) {
            updateColumnWidth(
                column,
                parseFloat(resizeUtils.getColumnWidth(column).toString()) + parseFloat(minColWidth),
            );
        } else {
            break;
        }
    }
}

export class GridSizeError extends Error {
    constructor(m: string) {
        super(m);
        Object.setPrototypeOf(this, GridSizeError.prototype);
    }
}
