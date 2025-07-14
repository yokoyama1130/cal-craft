<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Portfolio Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string $thumbnail
 *
 * @property \App\Model\Entity\User $user
 */
class Portfolio extends Entity
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
        'title' => true,
        'description' => true,
        'thumbnail' => true,
        'user' => true,
    ];
}
