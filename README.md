# Slcorp File Bundle

A clean architecture File bundle for Symfony.

## Installation

This bundle is currently in development and located at `src/Bundles/FileBundle`.

## Configuration

```yaml
slcorp_file:
    adapter: vich # sonata или vich
    ui_library: dropzone # fineuploader, dropzone, jquery_file_upload, plupload, uploadify, bluimp
    storage_path: '%kernel.project_dir%/public/files'
    validation:
        # Разрешенные MIME-типы (пустой массив = разрешены все типы)
        mime_types:
            - image/jpeg
            - image/png
            - image/gif
            - image/webp
        # Максимальный размер файла (можно использовать суффиксы: K, M, G)
        max_size: 20M # 5 мегабайт
        # Максимальное количество файлов
        max_files: 1


```

## License

MIT

