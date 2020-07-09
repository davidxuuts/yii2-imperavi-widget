# Upgrade guide from version `2.0` to `2.1`

- For local storage, replace any `UploadFileAction` entry with new `LocalUploadFileAction` instance.
- For qiniu bucket, please use `QiniuUploadFileAction` accordingly
- Replace any `Widget` entry with new `Redactor` instance.

**Please read the documentation to get in touch with new futures**

#

# Upgrade guide from version `1.*` to `2.0`

- Replace any `UploadAction` entry with new `UploadFileAction` instance.
- Replace `GetAction` with one of appropriate new actions `GetFilesAction` for file uploading or `GetImagesAction` for image uploading.

**Please read the documentation to get in touch with new futures**
