/**
 * @property {Number[]} enemy_ids
 */
class InCombatEnemiesChangedMessage extends Message {
    static getName() {
        return 'incombat-changed';
    }
}
