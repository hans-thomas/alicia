# Alicia

it is an uploader that has below features:

- easy-to-use
- customizable
- upload pictures and videos and files
- store external links
- HLS support
- collect file's details

## configuration

- `base folder` : is a place that Alicia stores all files and directories.
- `temp` : the temp is an address of a temporary folder that files stores there before classification and optimization
  applies. (set `false` to disable this feature)
- `classification` : Alicia let you determine how to classify your files.
- `extensions` : you can define your allowed file extensions base on file types.
- `sizes` : specifies the valid size of files based on their types.
- `validation` : Alicia validates the files, but you can add more validations rule.
- `optimization` : optimizes pictures and videos by default, however you can enable/disable the optimization for each
  file's type
- `naming` : there are several drivers to determine that how to generate files name
- `link` : you can customize file's download link.

> notice: you should not add or remove any parameter

- `signed` : this option creates a signed route with a hash key and an expiration time. this option prevent users
  sharing download links. (if you want a permanent link, just set this `false`)

> info: hash key will create using user's ip and user-agent

- `secret`: the string that hash key encrypts and decrypts.
- `expiration`: expiration time for signed routes.
- `attributes`: custom attributes for download link. for example, you can add a middleware.
- `onlyPublishedFiles`: files will not publish until classification and optimization jobs be done. so, if you want to
  show only published files, you need to set this option `true`.
- `hls`: you can en/disable hls and customize hls exporter's parameter.
- `export`: custom resolutions to export from images.
