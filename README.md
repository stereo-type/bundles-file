# Slcorp File Bundle

Бандл для работы с файлами в Symfony, построенный на принципах чистой архитектуры.

## Установка

Бандл находится в разработке и расположен в `src/Bundles/FileBundle`.

### Установка ассетов

Ассеты устанавливаются автоматически при загрузке бандла. Бандл автоматически:

1. Проверяет наличие npm
2. Устанавливает npm зависимости (Dropzone и другие UI библиотеки)
3. Копирует необходимые файлы в `src/Resources/public/vendor/`

Если npm не установлен, вы можете вручную установить ассеты:

```bash
cd vendor/slcorp/file-bundle  # или src/Bundles/FileBundle если в разработке
npm run install-assets
```

Затем установите ассеты в публичную директорию:

```bash
php bin/console assets:install
```

## Конфигурация

```yaml
slcorp_file:
    adapter: vich # sonata или vich
    ui_library: dropzone # fineuploader, dropzone, jquery_file_upload, plupload, bluimp
    storage_path: '%kernel.project_dir%/public/files'
    debug: false # включить режим отладки
    validation:
        # Разрешенные MIME-типы (пустой массив = разрешены все типы)
        mime_types:
            - image/jpeg
            - image/png
            - image/gif
            - image/webp
        # Максимальный размер файла (можно использовать суффиксы: K, M, G)
        max_size: 20M # 20 мегабайт
        # Максимальное количество файлов
        max_files: 1
```

## Кастомизация

### Переопределение шаблона Dropzone

Для переопределения шаблона Dropzone в вашем проекте:

1. Скопируйте шаблон из бандла:

```bash
cp vendor/slcorp/file-bundle/src/Resources/views/Form/dropzone_widget.html.twig templates/bundles/SlcorpFileBundle/Form/dropzone_widget.html.twig
```

2. Или если бандл в разработке:

```bash
cp src/Bundles/FileBundle/src/Resources/views/Form/dropzone_widget.html.twig templates/bundles/SlcorpFileBundle/Form/dropzone_widget.html.twig
```

3. Отредактируйте шаблон по своему усмотрению. Теперь вы можете изменить структуру HTML, добавить свои классы или
   изменить логику инициализации.

### Переопределение стилей Dropzone

Для переопределения стилей:

1. Скопируйте оригинальный SCSS файл в ваш проект:

```bash
cp vendor/slcorp/file-bundle/assets/scss/dropzone-widget.scss assets/scss/dropzone-widget-custom.scss
# или если бандл в разработке:
cp src/Bundles/FileBundle/assets/scss/dropzone-widget.scss assets/scss/dropzone-widget-custom.scss
```

2. Отредактируйте файл `assets/scss/dropzone-widget-custom.scss`:

```scss
// Ваши кастомные стили
.slcorp-file-uploader.dropzone {
  // Ваши стили здесь
  .dz-image {
    border-radius: 12px; // например, изменить скругление
  }
}
```

3. Подключите ваш файл стилей в основном SCSS файле проекта (например, в `assets/styles/app.scss`):

```scss
@import '../scss/dropzone-widget-custom.scss';
```

4. Или переопределите шаблон и измените путь к CSS файлу:

```twig
{# В templates/bundles/SlcorpFileBundle/Form/dropzone_widget.html.twig #}
<link href="{{ asset('build/dropzone-widget-custom.css') }}" rel="stylesheet">
```

### Переопределение переводов

Для переопределения или добавления переводов:

1. Создайте файл переводов в вашем проекте:

```bash
# Для русского языка
translations/messages.ru.yaml
```

2. Добавьте переводы:

```yaml
slcorp_file:
  dropzone:
    default_message: "Ваш кастомный текст"
    remove_file: "Удалить"
    # ... другие переводы
```

3. Для добавления нового языка создайте файл:

```bash
translations/messages.de.yaml  # для немецкого
```

4. Добавьте переводы в новый файл:

```yaml
slcorp_file:
  dropzone:
    default_message: "Dateien hier ablegen oder klicken zum Auswählen"
    remove_file: "Entfernen"
    # ... остальные переводы
```

5. Убедитесь, что язык добавлен в конфигурацию Symfony:

```yaml
# config/packages/translation.yaml
framework:
  default_locale: ru
  translator:
    default_path: '%kernel.project_dir%/translations'
    fallbacks:
      - ru
      - en
```

## Поддерживаемые UI библиотеки

Бандл поддерживает следующие библиотеки для загрузки файлов:

- **Dropzone** - современная библиотека с drag & drop
- **jQuery File Upload** - популярная библиотека на основе jQuery
- **Plupload** - кроссплатформенная библиотека
- **Bluimp** (Blueimp) - библиотека для загрузки файлов
- **Fine Uploader** - продвинутая библиотека с множеством функций

## Лицензия

MIT

