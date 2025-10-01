<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'sns_links' => true,
        'icon_path' => true,
    ];

    /**
     * アイコン画像のURLを取得するアクセサ
     *
     * @return string|null 画像が存在する場合は '/img/{icon_path}'、未設定なら null
     */
    protected function _getIconUrl()
    {
        if (!empty($this->icon_path)) {
            return '/img/' . $this->icon_path;
        }

        return null;
    }

    /**
     * JSONやAPI出力時に隠すフィールド
     *
     * @var array<int, string>
     */
    protected $_hidden = ['password'];

    /**
     * パスワードをハッシュ化してセットするミューテータ
     *
     * エンティティに代入されたプレーンテキストのパスワードを
     * 自動的に DefaultPasswordHasher でハッシュ化して保存する。
     *
     * @param string $password プレーンテキストのパスワード
     * @return string|null ハッシュ化済みパスワード
     */
    protected function _setPassword(string $password): ?string
    {
        return (new DefaultPasswordHasher())->hash($password);
    }
}
