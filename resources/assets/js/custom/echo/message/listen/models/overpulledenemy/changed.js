/**
 * @property {Number} enemy_id
 * @property {Number} kill_zone_id
 */
class OverpulledEnemyChangedMessage extends ModelMessage {
    static getName() {
        return 'overpulledenemy-changed';
    }
}
