# Imperavi Redactor Widget for Yii 2

[![Latest Version](https://img.shields.io/github/tag/davidxuuts/yii2-imperavi-widget.svg?style=flat-square&label=release)](https://github.com/davidxuuts/yii2-imperavi-widget/releases)
[![Software License](https://img.shields.io/badge/license-BSD-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/davidxuuts/yii2-imperavi-widget/master.svg?style=flat-square)](https://travis-ci.org/davidxuuts/yii2-imperavi-widget)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/vova07/yii2-imperavi-widget.svg?style=flat-square)](https://scrutinizer-ci.com/g/davidxuuts/yii2-imperavi-widget/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/davidxuuts/yii2-imperavi-widget.svg?style=flat-square)](https://scrutinizer-ci.com/g/davidxuuts/yii2-imperavi-widget)
[![Total Downloads](https://img.shields.io/packagist/dt/davidxuuts/yii2-imperavi-widget.svg?style=flat-square)](https://packagist.org/packages/davidxuuts/yii2-imperavi-widget)

`Imperavi Redactor Widget` is a wrapper for [Imperavi Redactor 10.2.5](https://imperavi.com/assets/pdf/redactor-documentation-10.pdf),
a high quality WYSIWYG editor.

**Note that Imperavi Redactor itself is a proprietary commercial copyrighted software
but since Yii community bought OEM license you can use it for free with Yii.**

## Install

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ php composer.phar require --prefer-dist davidxu/yii2-imperavi-widget "*"
```

or add

```json
"davidxu/yii2-imperavi-widget": "*"
```

to the `require` section of your `composer.json` file.


## Usage

If file/image info stores in DB or use qiniu bucket, please implement migrations below first:

```code
yii migrate/up --migration-path @davidxu/imperavi/migrations/
```

Once the extension is installed, simply use it in your code:

### Like a widget

```php
echo \davidxu\imperavi\Redactor::widget([
    'name' => 'redactor',
    'settings' => [
        'lang' => 'zh_cn',
        'imageUpload' => '/api/v1/redactor/upload',
        'minHeight' => 200,
        'plugins' => [
            'clips',
            'fullscreen',
        ],
        'clips' => [
            ['Lorem ipsum...', 'Lorem...'],
            ['red', '<span class="label-red">red</span>'],
            ['green', '<span class="label-green">green</span>'],
            ['blue', '<span class="label-blue">blue</span>'],
        ],
    ],
]);
```

### Like an ActiveForm widget

```php
use davidxu\imperavi\Redactor;

echo $form->field($model, 'content')->widget(Redactor::class, [
    'settings' => [
        'lang' => 'zh_cn',
        'imageUpload' => '/api/v1/redactor/upload',
        'minHeight' => 200,
        'plugins' => [
            'clips',
            'fullscreen',
        ],
        'clips' => [
            ['Lorem ipsum...', 'Lorem...'],
            ['red', '<span class="label-red">red</span>'],
            ['green', '<span class="label-green">green</span>'],
            ['blue', '<span class="label-blue">blue</span>'],
        ],
    ],
]);
```

### Like a widget for a predefined textarea

```php
echo \davidxu\imperavi\Redactor::widget([
    'selector' => '#my-textarea-id',
    'settings' => [
        'lang' => 'zh_cn',
        'imageUpload' => '/api/v1/redactor/upload',
        'minHeight' => 200,
        'plugins' => [
            'clips',
            'fullscreen',
        ],
        'clips' => [
            ['Lorem ipsum...', 'Lorem...'],
            ['red', '<span class="label-red">red</span>'],
            ['green', '<span class="label-green">green</span>'],
            ['blue', '<span class="label-blue">blue</span>'],
        ],
    ],
]);
```

### Upload image

```php
// DefaultController.php
public function actions()
{
    return [
        'upload-local' => [
            'class' => 'davidxu\imperavi\actions\LocalUploadFileAction',
            'dnsBaseUrl' => 'http://my-site.com/', // Domain name or uri where files are stored, filelink will be dnsBaseUrl + url 
            'url' => 'images/', // Directory URL address, where files are stored.
            'path' => '@alias/to/my/path', // Or absolute path to directory where files are stored.
            'modelClass' => Attachment::class, // Please use migration first, if file/image info stored in DB
            'storeInDB' => true, // Default true
        ],
        'upload-qiniu' => [
            'class' => 'davidxu\imperavi\actions\QiniuUploadFileAction',
            'dnsBaseUrl' => 'http://my-site.com/', // Qiniu DNS domain name, filelink will be dnsBaseUrl + url 
            'url' => 'images/', // Qiniu key prefix, key will be url + filename.
            'modelClass' => Attachment::class, // Please use migration first
        ],
    ];
}

// View.php
echo \davidxu\imperavi\Redactor::widget([
    'selector' => '#my-textarea-id',
    'settings' => [
        'lang' => 'zh_cn',
        'minHeight' => 200,
        'imageUpload' => Url::to(['/default/image-upload']),
        'plugins' => [
            'imagemanager',
        ],
    ],
]);
```

### Add custom plugins

```php
echo \davidxu\imperavi\Redactor::widget([
    'selector' => '#my-textarea-id',
    'settings' => [
        'lang' => 'zh_cn',
        'minHeight' => 200,
        'plugins' => [
            'clips',
            'fullscreen'
        ]
    ],
    'plugins' => [
        'my-custom-plugin' => 'app\assets\MyPluginBundle',
    ],
]);
```

## Testing

``` bash
$ phpunit
```

## Further Information

Please, check the [Imperavi Redactor v10](https://imperavi.com/assets/pdf/redactor-documentation-10.pdf) documentation for further information about its configuration options.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Vasile Crudu](https://github.com/vova07)
- [David Xu](https://github.com/davidxuuts)
- [All Contributors](../../contributors)

## License

The BSD License (BSD). Please see [License File](LICENSE.md) for more information.

## Upgrade guide

Please check the [UPGRADE GUIDE](UPGRADE.md) for details. 
