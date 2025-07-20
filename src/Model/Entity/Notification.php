<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Notification Entity
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $sender_id
 * @property int|null $portfolio_id
 * @property string|null $type
 * @property bool|null $is_read
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Portfolio $portfolio
 */
class Notification extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'user_id' => true,
        'sender_id' => true,
        'portfolio_id' => true,
        'type' => true,
        'is_read' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'portfolio' => true,
        'sender_user' => true,
    ];
}
