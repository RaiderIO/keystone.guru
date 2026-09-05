/**
 * An in-game world position, together with the floor it lives on.
 *
 * 1:1 port of app/Logic/Structs/IngameXY.php - keep the two in sync.
 */
class IngameXY {
    /**
     * @param x {Number}
     * @param y {Number}
     * @param floor {Object|null}
     */
    constructor(x = 0, y = 0, floor = null) {
        this.x = x;
        this.y = y;
        this.floor = floor;
    }

    /**
     * @param precision {Number|null}
     * @returns {Number}
     */
    getX(precision = null) {
        // Stabilize the float value by adding a very small number to it
        return precision === null ? this.x : roundHalfAwayFromZero(this.x + 1e-9, precision);
    }

    /**
     * @param x {Number}
     * @returns {IngameXY}
     */
    setX(x) {
        this.x = x;

        return this;
    }

    /**
     * @param precision {Number|null}
     * @returns {Number}
     */
    getY(precision = null) {
        // Stabilize the float value by adding a very small number to it
        return precision === null ? this.y : roundHalfAwayFromZero(this.y + 1e-9, precision);
    }

    /**
     * @param y {Number}
     * @returns {IngameXY}
     */
    setY(y) {
        this.y = y;

        return this;
    }

    /**
     * @returns {Object|null}
     */
    getFloor() {
        return this.floor;
    }

    /**
     * @param floor {Object|null}
     * @returns {IngameXY}
     */
    setFloor(floor) {
        this.floor = floor;

        return this;
    }

    /**
     * @returns {{x: Number, y: Number}}
     */
    toArray() {
        return {
            x: this.getX(),
            y: this.getY()
        };
    }

    /**
     * @returns {{x: Number, y: Number, floor_id: Number|null}}
     */
    toArrayWithFloor() {
        return {
            x: this.x,
            y: this.y,
            floor_id: this.floor?.id ?? null
        };
    }

    /**
     * @returns {IngameXY}
     */
    clone() {
        return new IngameXY(this.x, this.y, this.floor);
    }

    /**
     * @param ingameXY {{x: Number, y: Number}}
     * @param floor {Object|null}
     * @returns {IngameXY}
     */
    static fromArray(ingameXY, floor = null) {
        return new IngameXY(ingameXY.x, ingameXY.y, floor);
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = IngameXY;
}
