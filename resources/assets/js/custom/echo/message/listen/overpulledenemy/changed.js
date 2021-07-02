/**
 * @property {Number} enemy_id
 * @property {Number} kill_zone_id
 */
class OverpulledEnemyChangedMessage extends Message {
    static getName() {
        return 'overpulledenemy-changed';
    }
}