<?php

use humhub\components\Migration;

/**
 * Class m261015_000000_initial
 */
class m261015_000000_initial extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->safeCreateTable('email_log', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'user_id' => $this->integer(11)->null(),
            'email' => $this->string(255)->notNull(),
            'recipient_type' => $this->string(3)->notNull()->defaultValue('to'),
            'subject' => $this->string(255)->null(),
            'html_body' => 'MEDIUMTEXT NULL',
            'text_body' => 'MEDIUMTEXT NULL',
            'status' => $this->string(20)->notNull(),
            'error' => $this->text()->null(),
        ]);

        // Add indexes and foreign keys
        $this->safeCreateIndex('idx-email_log-created_at', 'email_log', ['created_at']);
        $this->safeCreateIndex('idx-email_log-status', 'email_log', ['status']);
        $this->safeCreateIndex('idx-email_log-email', 'email_log', ['email']);
        // Keep the logs when the user is deleted
        $this->safeAddForeignKey('fk-email_log-user', 'email_log', 'user_id', 'user', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m261015_000000_initial cannot be reverted.\n";

        return false;
    }
}
