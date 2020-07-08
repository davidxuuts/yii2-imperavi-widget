<?php

namespace davidxu\imperavi\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%attachment}}".
 *
 * @property int $id ID
 * @property int $member_id Member ID
 * @property string $drive Drive
 * @property string $specific_type Specific type
 * @property string $path File path
 * @property string $hash File hash
 * @property string $name Original name
 * @property string $extension Extension
 * @property int $size File size
 * @property int $year Year
 * @property int $month Month
 * @property int $day Day
 * @property int $width Width
 * @property int $height Height
 * @property int $created_at Created at
 * @property int $updated_at Updated at
 */
class Attachment extends ActiveRecord
{
    
    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%attachment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['member_id', 'size', 'year', 'month', 'day', 'width', 'height'], 'integer'],
            [['drive', 'extension'], 'string', 'max' => 50],
            [['drive'], 'in', 'range' => ['local', 'qiniu']],
            [['specific_type', 'hash'], 'string', 'max' => 100],
            [['path', 'name'], 'string', 'max' => 1024],
            [['name'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'member_id' => 'Member id',
            'drive' => 'Driver',
            'specific_type' => 'Specific type',
            'hash' => 'File hash',
            'path' => 'File path',
            'name' =>  'Original name',
            'extension' => 'Extension',
            'size' => 'File size',
            'year' => 'Year',
            'month' => 'Month',
            'day' =>  'Day',
            'width' => 'Width',
            'height' => 'Height',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
        ];
    }
}
