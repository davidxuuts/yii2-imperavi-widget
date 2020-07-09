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
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
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
 * QiniuUploadFileAction for images and files.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'upload-image' => [
 *             'class' => 'davidxu\imperavi\actions\QiniuUploadFileAction',
 *             'path' => 'statics',
 *             'url' => 'http://my-site.com/statics/',
 *             'modelClass' => Attachment::class,
 *             'unique' => true,
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'file-upload' => [
 *             'class' => 'davidxu\imperavi\actions\QiniuUploadFileAction',
 *             'url' => 'http://my-site.com/statics/',
 *             'path' => 'statics',
 *             'modelClass' => Attachment::class,
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
class QiniuUploadFileAction extends Action
{

    /**
     * @var string URL path to directory where files will be uploaded.
     */
    public $url;
    public $dnsBaseUrl;
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
    
    public $modelClass = '';
    
    public $qiniuBucket;
    public $qiniuAccessKey;
    public $qiniuSecretKey;
    public $qiniuReturnBody = [
        'drive' => 'qiniu',
        'specific_type' => '$(mimeType)',
        'path' => '$(key)',
        'hash' => '$(etag)',
        'size' => '$(fsize)',
        'name' => '$(x:name)',
        'extension' => '$(x:extension)',
        'member_id' => '$(x:member_id)',
        'width' => '$(imageInfo.width)',
        'height' => '$(imageInfo.height)',
        'year' => '$(x:year)',
        'month' => '$(x:month)',
        'day' => '$(x:day)',
    ];
    private $_auth;
    
    private $_file;
    private $_postData;
    private $_key;
    private $_originName;
    
    /**
     * @var string Model validator name.
     */
    private $_validator = 'image';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->qiniuBucket)) {
            $this->qiniuBucket = Yii::$app->params['qiniu.bucket'];
            if (empty($this->qiniuBucket) || $this->qiniuBucket === '') {
                throw new InvalidConfigException('The "qiniuBucket" attribute must be set.');
            }
        }
        if (empty($this->qiniuAccessKey)) {
            $this->qiniuAccessKey = Yii::$app->params['qiniu.accessKey'];
            if (empty($this->qiniuAccessKey) || $this->qiniuAccessKey === '') {
                throw new InvalidConfigException('The "qiniuAccessKey" attribute must be set.');
            }
        }
        if (empty($this->qiniuSecretKey)) {
            $this->qiniuSecretKey = Yii::$app->params['qiniu.secretKey'];
            if (empty($this->qiniuSecretKey) || $this->qiniuSecretKey === '') {
                throw new InvalidConfigException('The "qiniuSecretKey" attribute must be set.');
            }
        }
        if (empty($this->url) || $this->url === '') {
            $date = date('Ymd');
            $this->url = 'uploads/' . $date;
        }
        if (empty($this->dnsBaseUrl) || $this->dnsBaseUrl === '') {
            $this->dnsBaseUrl = Url::base();
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
        if (!(Yii::$app->request->isPost)) {
            throw new BadRequestHttpException('Only POST is allowed');
        }
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->handleLocalRequest();
    }
    
    protected function handleLocalRequest()
    {
        $file = UploadedFile::getInstanceByName($this->uploadParam);
        $model = new DynamicModel(['file' => $file]);
        $model->addRule('file', $this->_validator, $this->validatorOptions)->validate();
        if ($model->hasErrors()) {
            return [
                'error' => $model->getFirstError('file')
            ];
        } else {
            if ($file->error === 0 && $file->size > 0) {
                $this->_originName = $model->file->name;
                if ($this->unique === true && $model->file->extension) {
                    $model->file->name = date('His') . '_' . uniqid('', false) . '.' . $model->file->extension;
                } elseif ($this->translit === true && $model->file->extension) {
                    $model->file->name = date('His') . '_' . Inflector::slug($model->file->baseName) 
                        . '.' . $model->file->extension;
                }
                $filePathName = $this->url . DIRECTORY_SEPARATOR . $model->file->name;
                $this->_file = $model->file;
                $this->_postData = Yii::$app->request->post();
                return $this->handleQiniuRequest($filePathName, $model->file->extension);
            } else {
                return [
                    'error' => 'Something goes wrong',
                ];
            }
        }
    }
    
    protected function handleQiniuRequest($key, $ext)
    {
        $postData = $this->_postData;
        $params = [
            'x:year' => $postData['x:year'] ?? date('Y'),
            'x:month' => $postData['x:month'] ?? date('m'),
            'x:day' => $postData['x:day'] ?? date('d'),
            'x:member_id' => $postData['x:member_id'] ?? 0,
            'x:name' => $this->_originName,
            'x:extension' => $ext,
        ];
        $token = $this->getQiniuToken();
        $uploadMgr = new UploadManager();
        $result = $uploadMgr->putFile($token, $key, $this->_file->tempName, $params);

        $attachment = $this->writeToDB($result);

        if (isset($attachment['error'])) {
            return [
                'error' => $attachment['error'],
            ];
        }
        
        $extra = [
            'filelink' => $this->dnsBaseUrl . $attachment->path,
            'filename' => $attachment->name,
        ];
        $result = $attachment->attributes;
        return ArrayHelper::merge($result, $extra);;
    }
    
    /**
     * @param array $result Qiniu ReturnBody
     * @return Attachment
     */
    protected function writeToDB($returnResult)
    {
        if (empty($this->modelClass) || $this->modelClass === '') {
            $this->modelClass = Attachment::class;
        }
        [$result, $error] = $returnResult;
        unlink($this->_file->tempName);
        if ($error !== null) {
            return [
                'error' => 'Upload to qiniu error'
            ];
        } else {
           $model = new $this->modelClass;
           $model->attributes = $result;
           $model->save(false);
           return $model;
        }
    }
    
    /**
     * @return mixed
     */
    protected function getQiniuToken()
    {
        $this->_auth = new Auth($this->qiniuAccessKey, $this->qiniuSecretKey);
        $policy = [
            'returnBody' => Json::encode($this->qiniuReturnBody),
        ];
        return $this->_auth->uploadToken($this->qiniuBucket, null, 3600, $policy);
    }
}
