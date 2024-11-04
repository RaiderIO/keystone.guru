/**
 * @typedef {Object} MessageCoordinate
 * @property {Number} lat
 * @property {Number} lng
 * @property {Number} floor_id
 */

/**
 * @typedef {Object} MessageCoordinates
 * @property {MessageCoordinate} split_floors
 * @property {MessageCoordinate} facade
 */

/**
 * @typedef {Object} MessageCoordinatesPolyline
 * @property {MessageCoordinate[]} split_floors
 * @property {MessageCoordinate[]} facade
 */

/**
 * @typedef {Object} MessageCoordinates
 * @property {Number} lat
 * @property {Number} lng
 */

/**
 * @property {Object} user
 * @property {Number} floor_id
 * @property {string} __name
 */
class ModelMessage extends Message {

}
