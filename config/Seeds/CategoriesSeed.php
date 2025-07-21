<?php
use Migrations\AbstractSeed;

class CategoriesSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'name' => '機械系', 'slug' => 'mechanical'],
            ['id' => 2, 'name' => 'プログラミング', 'slug' => 'programming'],
            ['id' => 3, 'name' => '化学', 'slug' => 'chemistry'],
            ['id' => 4, 'name' => '法学', 'slug' => 'law'],
            ['id' => 5, 'name' => '経済', 'slug' => 'economics'],
            ['id' => 6, 'name' => 'その他', 'slug' => 'other'],
            ['id' => 7, 'name' => '電気・電子系', 'slug' => 'electrical'],
            ['id' => 8, 'name' => '情報・AI', 'slug' => 'ai_data'],
            ['id' => 9, 'name' => '建築・土木', 'slug' => 'architecture'],
            ['id' => 10, 'name' => 'デザイン', 'slug' => 'design'],
            ['id' => 11, 'name' => '医療・看護', 'slug' => 'medical'],
            ['id' => 12, 'name' => '教育・心理', 'slug' => 'education_psych'],
            ['id' => 13, 'name' => '経営・ビジネス', 'slug' => 'business'],
            ['id' => 14, 'name' => '数学・物理', 'slug' => 'math_physics'],
            ['id' => 15, 'name' => '文学・言語', 'slug' => 'literature'],
            ['id' => 16, 'name' => '芸術・音楽', 'slug' => 'art_music'],
            ['id' => 17, 'name' => '公務員試験対策', 'slug' => 'public_exam'],
            ['id' => 18, 'name' => '留学・TOEIC/英検', 'slug' => 'language_study'],
            ['id' => 19, 'name' => '就活／転職', 'slug' => 'career'],
        ];

        $table = $this->table('categories');
        $table->insert($data)->save();
    }
}
