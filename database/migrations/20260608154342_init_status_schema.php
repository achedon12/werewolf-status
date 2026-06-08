<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitStatusSchema extends AbstractMigration
{
    public function up(): void
    {
        $this->table('endpoints', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false,])
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false,])
            ->addColumn('check_url', 'string', ['limit' => 500, 'null' => false,])
            ->addColumn('public_url', 'string', ['limit' => 500, 'null' => true,])
            ->addColumn('uptime_unit', 'string', ['limit' => 50, 'null' => false, 'default' => 'seconds',])
            ->addColumn('is_enabled', 'boolean', ['null' => false, 'default' => true,])
            ->addColumn('discord_notifications_enabled', 'boolean', ['null' => false, 'default' => true,])
            ->addColumn('discord_webhook_url', 'string', ['limit' => 500, 'null' => true,])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP',])
            ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP',])
            ->addIndex(['is_enabled'])
            ->create();

        $this->table('downtimes', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false,])
            ->addColumn('endpoint_id', 'integer', ['signed' => false, 'null' => false,])
            ->addColumn('down_at', 'datetime', ['null' => false,])
            ->addColumn('up_at', 'datetime', ['null' => true,])
            ->addColumn('http_code', 'integer', ['null' => true,])
            ->addColumn('reason', 'string', ['limit' => 500, 'null' => true,])
            ->addColumn('discord_down_notified_at', 'datetime', ['null' => true,])
            ->addColumn('discord_up_notified_at', 'datetime', ['null' => true,])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP',])
            ->addIndex(['endpoint_id'])
            ->addIndex(['down_at'])
            ->addIndex(['up_at'])
            ->addIndex(['endpoint_id', 'up_at'])
            ->addForeignKey('endpoint_id', 'endpoints', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION',])
            ->create();

        $this->table('settings', ['id' => false, 'primary_key' => ['setting_key']])
            ->addColumn('setting_key', 'string', ['limit' => 100, 'null' => false,])
            ->addColumn('setting_value', 'string', ['limit' => 255, 'null' => false,])
            ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP',])
            ->create();

        $this->table('admin_users', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false,])
            ->addColumn('username', 'string', ['limit' => 100, 'null' => false,])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false,])
            ->addColumn('role', 'string', ['limit' => 50, 'null' => false, 'default' => 'admin',])
            ->addColumn('is_enabled', 'boolean', ['null' => false, 'default' => true,])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP',])
            ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['is_enabled'])
            ->create();
    }

    public function down(): void
    {
        $this->table('downtimes')->drop()->save();
        $this->table('endpoints')->drop()->save();
        $this->table('settings')->drop()->save();
        $this->table('admin_users')->drop()->save();
    }
}