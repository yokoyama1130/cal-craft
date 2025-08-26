<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
// ❌ use Cake\Auth\DefaultPasswordHasher;
use Authentication\PasswordHasher\DefaultPasswordHasher;

/**
 * Company Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $website
 * @property string|null $industry
 * @property string|null $size
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $domain
 * @property bool $verified
 * @property string $plan
 * @property string|null $billing_email
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 * @property int $owner_user_id
 *
 * @property \App\Model\Entity\User $user
 */
class Company extends Entity
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
        '*' => true,
        'id' => false,
    ];

    // ★ auth_password を保存時にハッシュ化
    protected function _setAuthPassword(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        return (new DefaultPasswordHasher())->hash($value);
    }
}
