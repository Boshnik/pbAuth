<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Связь «аккаунт у провайдера → пользователь сайта».
 *
 * Отдельная таблица, а не поле в профиле: при каждом входе через соцсеть по
 * паре (провайдер, id у провайдера) нужно найти пользователя, а искать это
 * внутри JSON в `extended` — полный перебор таблицы.
 *
 * Пользователь может привязать несколько сетей, но один аккаунт у провайдера
 * принадлежит ровно одному пользователю — это гарантирует уникальный индекс.
 */
final class CreatePbaSocialAccountsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('pba_social_accounts', ['id' => true, 'primary_key' => ['id']]);
        $table
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('provider', 'string', ['limit' => 32, 'null' => false, 'default' => ''])
            // Идентификатор у провайдера. Строкой: у кого-то это число, у
            // кого-то — строка вида "1090303..." длиной под сотню знаков.
            ->addColumn('provider_id', 'string', ['limit' => 191, 'null' => false, 'default' => ''])
            ->addColumn('email', 'string', ['limit' => 191, 'null' => false, 'default' => ''])
            ->addColumn('nickname', 'string', ['limit' => 191, 'null' => false, 'default' => ''])
            ->addColumn('avatar', 'string', ['limit' => 500, 'null' => false, 'default' => ''])
            // Что ещё прислал провайдер — на случай, если сайту нужно больше.
            ->addColumn('properties', 'json', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['provider', 'provider_id'], ['unique' => true, 'name' => 'idx_provider_account'])
            ->addIndex(['user_id'], ['name' => 'idx_user'])
            ->create();
    }
}
