<?php
/**
 * This file is part of yii2-imperavi-widget.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/davidxuuts/yii2-imperavi-widget
 */

namespace davidxu\imperavi\actions;

use davidxu\imperavi\Redactor;
use davidxu\imperavi\models\Attachment;
use Yii;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\helpers\Url;
use Qiniu\Etag;

/**
 * LocalUploadFileAction for images and files.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'upload-image' => [
 *             'class' => 'davidxu\imperavi\actions\LocalUploadFileAction',
 *             'url' => 'http://my-site.com/statics/',
 *             'path' => '/var/www/my-site.com/web/statics',
 *             'unique' => true,
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'file-upload' => [
 *             'class' => 'davidxu\imperavi\actions\LocalUploadFileAction',
 *             'url' => 'http://my-site.com/statics/',
 *             'path' => '/var/www/my-site.com/web/statics',
 *             'uploadOnlyImage' => false,
 *             'translit' => true,
 *             'validatorOptions' => [
 *                 'maxSize' => 40000
 *             ]
 *         ]
 *     ];
 * }
 * ```
 *
 * @author Vasile Crudu <bazillio07@yandex.ru>
 * @author David Xu <david.xu.uts@163.com>
 *
 * @link https://github.com/davidxuuts/yii2-imperavi-widget
 */
class LocalUploadFileAction extends Action
{
    /**
     * @var string Path to directory where files will be uploaded.
     */
    public $path;

    /**
     * @var string URL path to directory where files will be uploaded.
     */
    public $url;

    /**
     * @var string Validator name
     */
    public $uploadOnlyImage = false;

    /**
     * @var string Variable's name that Imperavi Redactor sent upon image/file upload.
     */
    public $uploadParam = 'file';

    /**
     * @var bool Whether to replace the file with new one in case they have same name or not.
     */
    public $replace = false;

    /**
     * @var boolean If `true` unique filename will be generated automatically.
     */
    public $unique = true;

    /**
     * In case of `true` this option will be ignored if `$unique` will be also enabled.
     *
     * @var bool Whether to translit the uploaded file name or not.
     */
    public $translit = false;

    /**
     * @var array Model validator options.
     */
    public $validatorOptions = [];
    
    public $storeInDB = true;
    public $modelClass = '';
   
    /**
     * @var string Model validator name.
     */
    private $_validator = 'image';
    private $_originFileName;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->path) || $this->path === '') {
            $this->path = Yii::getAlias('@webroot/uploads');
        }
        if (empty($this->url) || $this->url === '') {
            $this->url = Url::to('@web/uploads');
        }        
        if ($this->uploadOnlyImage !== true) {
            $this->_validator = 'file';
        }
        Redactor::registerTranslations();

    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!file_exists($this->path)) {
            if (!mkdir($this->path, 0755, true)) {
                $result = [
                    'error' => Yii::t('davidxu/imperavi', '{path} can not be created', [
                        'path' => $this->path,
                    ]),
                ];
            }
            return $result;
        } else {
            if (!is_dir($this->path)) {
                $result = [
                    'error' => Yii::t('vova07/imperavi', '{path} is not a dir', [
                        'path' => $this->path,
                    ]),
                ];
            } else {
                if (!is_writable($this->path)) {
                    $result = [
                        'error' => Yii::t('vova07/imperavi', '{path} is not writable', [
                            'path' => $this->path,
                        ]),
                    ];
                }
            }
            return $result;
        }
        
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $file = UploadedFile::getInstanceByName($this->uploadParam);
            $model = new DynamicModel(['file' => $file]);
            $model->addRule('file', $this->_validator, $this->validatorOptions)->validate();
            if ($model->hasErrors()) {
                $result = [
                    'error' => $model->getFirstError('file')
                ];
            } else {
                if ($file->error === 0 && $file->size > 0) {
                    $date = date('Ymd');
                    $this->_originFileName = $model->file->name;
                    if ($this->unique === true && $model->file->extension) {
                        $model->file->name = date('His') . '_' . uniqid('', false) . '.' . $model->file->extension;
                    } elseif ($this->translit === true && $model->file->extension) {
                        $model->file->name =  date('His') . '_' . Inflector::slug($model->file->baseName) 
                            . '.' . $model->file->extension;
                    }

                    $filePathName = $date . DIRECTORY_SEPARATOR . $model->file->name;
                    [$saveResult, $dest] = $this->save(
                        $file->tempName,  $filePathName
                    );
        
                    if ($saveResult) {
                        $savePath = $this->path . DIRECTORY_SEPARATOR . $filePathName;
                        $result = [
                            'filelink' => $this->url . DIRECTORY_SEPARATOR . $dest,
                            'id' => $dest,
                            'filename' => $model->file->name,
                        ];
                        if ($this->storeInDB) {
                            $attachment = $this->writeToDB(
                                $file,
                                $this->url . DIRECTORY_SEPARATOR . $filePathName,
                                $savePath,
                                Yii::$app->request->post(),
                                $model->file->extension
                            );
                            $result = $attachment->attributes;
                            $extra = [
                                'filelink' => $attachment->path,
                                'filename' => $attachment->name,
                            ];
                            $result = ArrayHelper::merge($result, $extra);
                        }
                    } else {
                        $result = [
                            'error' => Yii::t('davidxu/imperavi', 'Upload failed:[{error}]', [
                                'error' => $dest,
                            ]),
                        ];
                    }
                }
            }
            return $result;
        } else {
            throw new BadRequestHttpException('Only POST is allowed');
        }
    }
    
    /**
     * @param string $src Uploaded file source path
     * @param string $dest Uploaded file destination path
     * @return array(bool,string,string)
     */
    protected function save($src, $dest)
    {
        if (strpos($dest, DIRECTORY_SEPARATOR) > 0) {
            $dir = rtrim($this->path, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . ltrim(substr($dest, 0, strrpos($dest, DIRECTORY_SEPARATOR)),
                    DIRECTORY_SEPARATOR);
            if (file_exists($dir)) {
                if (!is_dir($dir)) {
                    return [
                        'error' => $dir . Yii::t('davidxu/imperavi', 'File exists, create dir failed')
                    ];
                }
            } else {
                @mkdir($dir, 0755, true);
            }
        }
        $path = rtrim($this->path, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . ltrim($dest, DIRECTORY_SEPARATOR);
        if (!(move_uploaded_file($src, $path) || !file_exists($path))) {
            return [false, '', Yii::t('davidxu/imperavi', 'ERROR_SAVE_FILE')];
        } else {
            return [true, $dest, Yii::t('davidxu/imperavi', 'SUCCESS_SAVE_FILE')];
        }
    }
    
    /**
     * @param array $file File
     * @param string File name in DB
     * @param string|null $savePath File path in Harddisk
     * @param array $postData
     * @param string $ext File extension
     * @return Attachment
     */
    protected function writeToDB($file, $filename, $savePath, array $postData, $ext)
    {
        if (empty($this->modelClass) || $this->modelClass === '') {
            $this->modelClass = Attachment::class;
        }

        $params = [
            'x:year' => $postData['x:year'] ?? date('Y'),
            'x:month' => $postData['x:month'] ?? date('m'),
            'x:day' => $postData['x:day'] ?? date('d'),
            'x:member_id' => $postData['x:member_id'] ?? 0,
        ];
        
        $model = new $this->modelClass;
        $model->member_id = $postData['x:member_id'] ?? 0;
        $model->drive = 'local';
        $model->specific_type = $file->type;
        $model->name = $_originFileName;
        $model->size = $file->size;
        $model->path = $filename;
        $model->extension = $ext;
        $model->year = $postData['x:year'] ?? date('Y');
        $model->month = $postData['x:month'] ?? date('m');
        $model->day = $postData['x:day'] ?? date('d');
        [$etag, $err] = Etag::sum($savePath);
        if ($err === null) {
            $model->hash = $etag;
        }
        $model->save(false);
        return $model;
    }
}
