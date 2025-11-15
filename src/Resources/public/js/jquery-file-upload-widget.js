/**
 * jQuery File Upload Widget JavaScript
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @requires jQuery - библиотека должна быть загружена до этого скрипта
 * @requires blueimp-file-upload - библиотека должна быть загружена до этого скрипта
 */

(function (window) {
    'use strict';

    /**
     * Инициализирует jQuery File Upload виджет
     *
     * @param {Object} config - Конфигурация виджета
     * @param {string} config.containerId - ID контейнера виджета
     * @param {string} config.hiddenFieldId - ID скрытого поля формы
     * @param {string} config.uploadUrl - URL для загрузки файлов
     * @param {string} config.fieldName - Имя поля формы
     * @param {number} config.maxFiles - Максимальное количество файлов
     * @param {number} config.maxFileSize - Максимальный размер файла в байтах
     * @param {string|null} config.allowedExtensions - Разрешенные расширения файлов (MIME типы через запятую)
     * @param {Object} config.params - Параметры для отправки на сервер
     * @param {string} config.params.component - Компонент
     * @param {string} config.params.filearea - Область файлов
     * @param {number} config.params.itemid - ID элемента
     * @param {number} config.params.contextid - ID контекста
     * @param {number|null} config.params.userid - ID пользователя
     * @param {Array} config.fileData - Массив существующих файлов
     * @param {Object} config.translations - Переводы для интерфейса
     */
    window.SlcorpFileBundle = window.SlcorpFileBundle || {};
    window.SlcorpFileBundle.JQueryFileUploadWidget = function (config) {
        if (typeof jQuery === 'undefined') {
            console.error('[SlcorpFileBundle] jQuery is not loaded');
            return;
        }

        if (typeof jQuery.fn.fileupload === 'undefined') {
            console.error('[SlcorpFileBundle] jQuery File Upload plugin is not loaded');
            return;
        }

        var $ = jQuery;
        var $container = $('#' + config.containerId);
        var $filesContainer = $container.find('.files');
        var $hiddenField = $('#' + config.hiddenFieldId);
        var uploadedFiles = [];

        // Нормализуем скрытое поле - всегда должен быть массив (JSON)
        var currentValue = $hiddenField.val() || '[]';
        try {
            var parsed = JSON.parse(currentValue);
            if (!Array.isArray(parsed)) {
                $hiddenField.val(parsed ? JSON.stringify([parsed]) : '[]');
            } else {
                $hiddenField.val(JSON.stringify(parsed));
            }
        } catch (e) {
            if (currentValue && currentValue !== '[]') {
                $hiddenField.val(JSON.stringify([currentValue]));
            } else {
                $hiddenField.val('[]');
            }
        }

        // Загружаем существующие файлы
        if (config.fileData && Array.isArray(config.fileData) && config.fileData.length > 0) {
            config.fileData.forEach(function (file) {
                addExistingFile(file);
            });
        }

        function addExistingFile(file) {
            var $template = $('<div class="template-download">' +
                '<div class="preview">' +
                (file.mimetype && file.mimetype.startsWith('image/') ?
                    '<img src="' + (file.download_url || '') + '" alt="' + escapeHtml(file.filename) + '">' :
                    '<span>📄</span>') +
                '</div>' +
                '<div class="name">' + escapeHtml(file.filename) + '</div>' +
                '<div class="size">' + formatFileSize(file.filesize) + '</div>' +
                '<button type="button" class="delete" data-draftitemid="' + file.draftitemid + '">' + config.translations.delete + '</button>' +
                '</div>');

            $filesContainer.append($template);
            uploadedFiles.push({
                draftitemid: file.draftitemid,
                filename: file.filename
            });
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        function updateHiddenField() {
            var draftItemIds = uploadedFiles.map(function (file) {
                return file.draftitemid;
            });
            $hiddenField.val(JSON.stringify(draftItemIds));
        }

        // Функция для показа модального окна с ошибкой
        function showErrorModal(message) {
            var $modal = $('#error-modal-' + config.hiddenFieldId);
            var $message = $modal.find('.error-message');
            $message.text(message);
            $modal.fadeIn(200);
        }

        // Функция для скрытия модального окна
        function hideErrorModal() {
            var $modal = $('#error-modal-' + config.hiddenFieldId);
            $modal.fadeOut(200);
        }

        // Обработчики закрытия модального окна
        $('#error-modal-' + config.hiddenFieldId + ' .error-modal-close, #error-modal-' + config.hiddenFieldId + ' .error-modal-ok, #error-modal-' + config.hiddenFieldId + ' .error-modal-overlay').on('click', function () {
            hideErrorModal();
        });

        // Предотвращаем закрытие при клике на контент
        $('#error-modal-' + config.hiddenFieldId + ' .error-modal-content').on('click', function (e) {
            e.stopPropagation();
        });

        // Инициализация jQuery File Upload
        var allowedMimeTypes = [];
        if (config.allowedExtensions) {
            allowedMimeTypes = config.allowedExtensions.split(',').map(function (type) {
                return type.trim();
            }).filter(function (type) {
                return type.length > 0;
            });
        }

        $container.find('input[type="file"]').fileupload({
            url: config.uploadUrl,
            dataType: 'json',
            autoUpload: true,
            maxNumberOfFiles: config.maxFiles,
            maxFileSize: config.maxFileSize,
            accept: function (file) {
                if (allowedMimeTypes.length === 0) {
                    return true;
                }
                return allowedMimeTypes.indexOf(file.type) !== -1;
            },
            formData: function () {
                var formData = [
                    {name: 'component', value: config.params.component},
                    {name: 'filearea', value: config.params.filearea},
                    {name: 'itemid', value: config.params.itemid},
                    {name: 'contextid', value: config.params.contextid}
                ];
                if (config.params.userid !== null) {
                    formData.push({name: 'userid', value: config.params.userid});
                }
                return formData;
            },
            add: function (e, data) {
                // Если maxFiles = 1, удаляем предыдущий файл
                if (config.maxFiles === 1 && uploadedFiles.length >= 1) {
                    $filesContainer.find('.template-download').each(function () {
                        var $template = $(this);
                        var draftitemid = $template.find('.delete').data('draftitemid');
                        uploadedFiles = uploadedFiles.filter(function (file) {
                            return file.draftitemid !== draftitemid;
                        });
                        $template.remove();
                    });
                    updateHiddenField();
                }

                // Проверяем максимальное количество файлов
                if (uploadedFiles.length >= config.maxFiles) {
                    showErrorModal(config.translations.max_files_exceeded.replace('%maxFiles%', config.maxFiles));
                    return false;
                }

                // Проверяем размер файла
                if (data.files[0].size > config.maxFileSize) {
                    showErrorModal(config.translations.file_too_big.replace('%maxFileSize%', formatFileSize(config.maxFileSize)));
                    return false;
                }

                // Добавляем файл в очередь
                var $template = $('<div class="template-upload">' +
                    '<div class="preview"></div>' +
                    '<div class="name">' + escapeHtml(data.files[0].name) + '</div>' +
                    '<div class="size">' + formatFileSize(data.files[0].size) + '</div>' +
                    '<div class="progress"><div class="progress-bar" style="width: 0%"></div></div>' +
                    '<button type="button" class="cancel">' + config.translations.cancel + '</button>' +
                    '</div>');

                $filesContainer.append($template);

                // Если это изображение, показываем превью
                if (data.files[0].type && data.files[0].type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $template.find('.preview').html('<img src="' + e.target.result + '" alt="' + escapeHtml(data.files[0].name) + '">');
                    };
                    reader.readAsDataURL(data.files[0]);
                } else {
                    $template.find('.preview').html('<span>📄</span>');
                }

                // Обработчик отмены
                $template.find('.cancel').on('click', function () {
                    data.abort();
                    $template.remove();
                });

                // Начинаем загрузку
                data.submit();
            },
            progress: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                var $template = data.context || $filesContainer.find('.template-upload').last();
                $template.find('.progress-bar').css('width', progress + '%');
            },
            done: function (e, data) {
                var $template = data.context || $filesContainer.find('.template-upload').last();

                if (data.result && Array.isArray(data.result) && data.result.length > 0) {
                    var file = data.result[0];
                    if (file.draftitemid) {
                        // Заменяем шаблон загрузки на шаблон загруженного файла
                        var $downloadTemplate = $('<div class="template-download">' +
                            '<div class="preview">' +
                            (file.thumbnailUrl ?
                                '<img src="' + file.thumbnailUrl + '" alt="' + escapeHtml(file.name) + '">' :
                                '<span>📄</span>') +
                            '</div>' +
                            '<div class="name">' + escapeHtml(file.name) + '</div>' +
                            '<div class="size">' + formatFileSize(file.size) + '</div>' +
                            '<button type="button" class="delete" data-draftitemid="' + file.draftitemid + '">' + config.translations.delete + '</button>' +
                            '</div>');

                        $template.replaceWith($downloadTemplate);

                        // Добавляем в массив загруженных файлов
                        uploadedFiles.push({
                            draftitemid: file.draftitemid,
                            filename: file.name
                        });

                        // Обновляем скрытое поле
                        updateHiddenField();
                    } else {
                        $template.remove();
                    }
                } else {
                    $template.remove();
                }
            },
            processfail: function (e, data) {
                var $template = data.context || $filesContainer.find('.template-upload').last();
                var errorMessage = config.translations.validation_error;

                if (data.files && data.files[0]) {
                    if (data.files[0].error) {
                        errorMessage = data.files[0].error;
                    } else if (allowedMimeTypes.length > 0 && data.files[0].type && allowedMimeTypes.indexOf(data.files[0].type) === -1) {
                        errorMessage = config.translations.file_type_not_allowed.replace('%allowedTypes%', allowedMimeTypes.join(', '));
                    } else if (data.files[0].size > config.maxFileSize) {
                        errorMessage = config.translations.file_too_big.replace('%maxFileSize%', formatFileSize(config.maxFileSize));
                    }
                }

                showErrorModal(errorMessage);
                if ($template.length) {
                    $template.remove();
                }
            },
            fail: function (e, data) {
                var $template = data.context || $filesContainer.find('.template-upload').last();
                var errorMessage = config.translations.upload_error;

                // Пытаемся извлечь конкретное сообщение об ошибке
                if (data.result) {
                    if (data.result.error) {
                        errorMessage = data.result.error;
                    } else if (typeof data.result === 'string') {
                        errorMessage = data.result;
                    } else if (data.result[0] && data.result[0].error) {
                        errorMessage = data.result[0].error;
                    }
                } else if (data.jqXHR) {
                    if (data.jqXHR.responseJSON) {
                        if (data.jqXHR.responseJSON.error) {
                            errorMessage = data.jqXHR.responseJSON.error;
                        } else if (data.jqXHR.responseJSON.message) {
                            errorMessage = data.jqXHR.responseJSON.message;
                        }
                    } else if (data.jqXHR.responseText) {
                        try {
                            var parsed = JSON.parse(data.jqXHR.responseText);
                            if (parsed.error) {
                                errorMessage = parsed.error;
                            } else if (parsed.message) {
                                errorMessage = parsed.message;
                            }
                        } catch (e) {
                            var responseText = data.jqXHR.responseText.trim();
                            if (responseText.length > 0 && responseText.length < 500) {
                                errorMessage = responseText;
                            }
                        }
                    }

                    if (data.jqXHR.status) {
                        var statusMessages = {
                            400: config.translations.error_bad_request,
                            401: config.translations.error_unauthorized,
                            403: config.translations.error_forbidden,
                            404: config.translations.error_not_found,
                            413: config.translations.error_file_too_large,
                            415: config.translations.error_unsupported_media_type,
                            500: config.translations.error_server,
                            502: config.translations.error_bad_gateway,
                            503: config.translations.error_service_unavailable
                        };

                        if (statusMessages[data.jqXHR.status] && errorMessage === config.translations.upload_error) {
                            errorMessage = statusMessages[data.jqXHR.status];
                        }
                    }
                } else if (data.textStatus) {
                    var statusMessages = {
                        'error': config.translations.upload_error,
                        'timeout': config.translations.error_timeout,
                        'abort': config.translations.error_abort,
                        'parsererror': config.translations.error_parser
                    };
                    errorMessage = statusMessages[data.textStatus] || data.textStatus;
                }

                if (errorMessage === config.translations.upload_error && data.files && data.files[0]) {
                    errorMessage = config.translations.upload_error + ': "' + data.files[0].name + '"';
                }

                showErrorModal(errorMessage);
                if ($template.length) {
                    $template.remove();
                }
            }
        });

        // Обработчик удаления файла
        $filesContainer.on('click', '.delete', function () {
            var $button = $(this);
            var draftitemid = $button.data('draftitemid');
            var $template = $button.closest('.template-download');

            if (confirm(config.translations.delete_confirmation)) {
                uploadedFiles = uploadedFiles.filter(function (file) {
                    return file.draftitemid !== draftitemid;
                });

                updateHiddenField();
                $template.remove();
            }
        });
    };
})(window);

