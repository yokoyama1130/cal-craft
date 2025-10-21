<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * 一括代入可能フィールド
     * （'*' => true は避け、明示的に）
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'name' => true,
        'email' => true,
        'password' => true,
        'bio' => true,
        'icon_path' => true,
        'sns_links' => true,
        'email_verified' => true,
        'email_token' => true,
        'created' => true,
        'modified' => true,
        // 'id' は false のまま
    ];

    /**
     * JSON/API出力で隠すフィールド
     *
     * @var array<int, string>
     */
    protected $_hidden = [
        'password',
        'email_token',
    ];

    /**
     * JSONに出す仮想フィールド
     *
     * @var array<int, string>
     */
    protected $_virtual = [
        'icon_url',
    ];

    /**
     * アイコンURL（仮想フィールド）
     *
     * @return string|null '/img/{icon_path}' or null
     */
    protected function _getIconUrl(): ?string
    {
        if (!empty($this->icon_path)) {
            return '/img/' . ltrim((string)$this->icon_path, '/');
        }

        return null;
    }

    /**
     * パスワードのハッシュ化ミューテータ
     * - 空/nullなら変更しない
     * - 既にbcrypt（$2y$...）なら再ハッシュしない
     */
    protected function _setPassword(?string $password): ?string
    {
        if ($password === null || $password === '') {
            return $this->password ?? null;
        }
        if (\preg_match('/^\$2y\$/', $password) === 1) {
            return $password;
        }

        return (new DefaultPasswordHasher())->hash($password);
    }
}
