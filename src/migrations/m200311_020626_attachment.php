<?php

use yii\db\Migration;

class m200311_020626_attachment extends Migration
{
    private $tableName = '{{%attachment}}';
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql' || $this->db->driverName === 'mariadb') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        $this->execute('SET foreign_key_checks = 0');
        
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey()->comment('ID'),
            'member_id' => $this->integer()->null()->defaultValue(0)->comment('Member id'),
            'drive' => $this->string(50)->null()->defaultValue('local')->comment('Driver'),
            'specific_type' => $this->string(100)->null()->comment('Specific type'),
            'path' => $this->string(1024)->null()->comment('File path'),
            'hash' => $this->string(100)->null()->comment('File hash'),
            'name' => $this->string(200)->null()->comment('Original name'),
            'extension' => $this->string(50)->null()->defaultValue(0)->comment('Extension'),
            'size' => $this->integer()->null()->defaultValue(0)->comment('File size'),
            'year' => $this->integer()->null()->defaultValue(0)->comment('Year'),
            'month' => $this->integer()->null()->defaultValue(0)->comment('Month'),
            'day' => $this->integer()->null()->defaultValue(0)->comment('Day'),
            'width' => $this->integer()->null()->defaultValue(0)->comment('Width'),
            'height' => $this->integer()->null()->defaultValue(0)->comment('Height'),
            'created_at' => $this->integer()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
                ->comment('Created at'),
            'updated_at' => $this->integer()->notNull()
                ->defaultExpression('CURRENT_TIMESTAMP')
                ->comment('Updated at')
        ], $tableOptions);
        $this->addCommentOnTable($this->tableName, 'Common attachment table');
        $this->createIndex('hash',$this->tableName,'hash',0);
        $this->execute('SET foreign_key_checks = 1');
    }

    public function down()
    {
        $this->execute('SET foreign_key_checks = 0');
        $this->dropIndex('hash', $this->tableName);
        $this->dropTable($this->tableName);
        $this->execute('SET foreign_key_checks = 1');
    }
}
