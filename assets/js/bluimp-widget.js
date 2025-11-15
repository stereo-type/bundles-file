/**
 * Bluimp (Blueimp File Upload) Widget JavaScript
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
     * Инициализирует Bluimp виджет
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
    window.SlcorpFileBundle.BluimpWidget = function (config) {
        if (typeof jQuery === 'undefined') {
            console.error('[SlcorpFileBundle] jQuery is not loaded');
            return;
        }

        if (typeof jQuery.fn.fileupload === 'undefined') {
            console.error('[SlcorpFileBundle] Blueimp File Upload plugin is not loaded');
            return;
        }

        var $ = jQuery;
        var $hiddenField = $('#' + config.hiddenFieldId);
        var $container = $('#' + config.containerId);
        var uploadedFiles = [];

        // Нормализуем скрытое поле
        var currentValue = $hiddenField.val() || '[]';
        try {
            var parsed = JSON.parse(currentValue);
            $hiddenField.val(Array.isArray(parsed) ? JSON.stringify(parsed) : JSON.stringify(parsed ? [parsed] : []));
        } catch (e) {
            $hiddenField.val(currentValue && currentValue !== '[]' ? JSON.stringify([currentValue]) : '[]');
        }

        function updateHiddenField() {
            var draftItemIds = uploadedFiles.map(function (file) {
                return file.draftitemid;
            });
            $hiddenField.val(JSON.stringify(draftItemIds));
        }

        function showErrorModal(message) {
            var $modal = $('#error-modal-' + config.hiddenFieldId);
            $modal.find('.error-message').text(message);
            $modal.fadeIn(200);
        }

        function hideErrorModal() {
            $('#error-modal-' + config.hiddenFieldId).fadeOut(200);
        }

        // Обработчики закрытия модального окна
        $('#error-modal-' + config.hiddenFieldId + ' .error-modal-close, #error-modal-' + config.hiddenFieldId + ' .error-modal-ok, #error-modal-' + config.hiddenFieldId + ' .error-modal-overlay').on('click', hideErrorModal);
        $('#error-modal-' + config.hiddenFieldId + ' .error-modal-content').on('click', function (e) {
            e.stopPropagation();
        });

        // Подготовка разрешенных MIME типов для Blueimp
        var allowedMimeTypes = [];
        if (config.allowedExtensions) {
            allowedMimeTypes = config.allowedExtensions.split(',').map(function (type) {
                return type.trim();
            }).filter(function (type) {
                return type.length > 0;
            });
        }

        // Инициализация Blueimp File Upload
        $container.fileupload({
            url: config.uploadUrl,
            dataType: 'json',
            autoUpload: true,
            maxFileSize: config.maxFileSize,
            maxNumberOfFiles: config.maxFiles,
            acceptFileTypes: allowedMimeTypes.length > 0 ? new RegExp('(' + allowedMimeTypes.map(function (mime) {
                var parts = mime.split('/');
                if (parts.length === 2) {
                    var ext = parts[1];
                    if (ext === 'jpeg') ext = 'jpg|jpeg';
                    return ext;
                }
                return '';
            }).filter(function (ext) {
                return ext.length > 0;
            }).join('|') + ')$', 'i') : undefined,
            formData: {
                component: config.params.component,
                filearea: config.params.filearea,
                itemid: config.params.itemid,
                contextid: config.params.contextid
            },
            filesContainer: $container.find('.files'),
            add: function (e, data) {
                // Если maxFiles = 1, удаляем предыдущие файлы
                if (config.maxFiles === 1 && uploadedFiles.length >= 1) {
                    $container.find('.files').empty();
                    uploadedFiles = [];
                    updateHiddenField();
                }

                // Проверяем максимальное количество файлов
                if (uploadedFiles.length + data.files.length > config.maxFiles) {
                    showErrorModal(config.translations.max_files_exceeded.replace('%maxFiles%', config.maxFiles));
                    return false;
                }

                // Создаем элемент для отображения файла
                data.context = $('<div class="template-upload">' +
                    '<div class="preview"><span style="font-size: 40px; color: #ccc;">📄</span></div>' +
                    '<div style="flex: 1;">' +
                    '<div class="name"></div>' +
                    '<div class="size"></div>' +
                    '<div class="progress"><div class="progress-bar" style="width: 0%"></div></div>' +
                    '</div>' +
                    '<button type="button" class="cancel">' + config.translations.cancel + '</button>' +
                    '</div>').appendTo($container.find('.files'));

                // Превью для изображений
                if (data.files[0].type && data.files[0].type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        data.context.find('.preview').html('<img src="' + e.target.result + '">');
                    };
                    reader.readAsDataURL(data.files[0]);
                }

                data.context.find('.name').text(data.files[0].name);
                data.context.find('.size').text((data.files[0].size / 1024).toFixed(2) + ' KB');

                // Автоматически начинаем загрузку
                data.submit();
            },
            progress: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                data.context.find('.progress-bar').css('width', progress + '%');
            },
            done: function (e, data) {
                try {
                    var result = data.result[0];
                    if (result && result.draftitemid) {
                        var isImage = result.url && result.name.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                        var previewHtml = '';
                        if (isImage && result.url) {
                            previewHtml = '<img src="' + result.url + '" alt="' + escapeHtml(result.name) + '">';
                        } else {
                            previewHtml = '<span style="font-size: 40px; color: #ccc;">📄</span>';
                        }

                        data.context.attr('data-draftitemid', result.draftitemid);
                        data.context.html(
                            '<div class="preview">' + previewHtml + '</div>' +
                            '<div style="flex: 1;">' +
                            '<div class="name">' + escapeHtml(result.name) + '</div>' +
                            '<div class="size">' + (result.size / 1024).toFixed(2) + ' KB</div>' +
                            '</div>' +
                            '<button type="button" class="delete" data-draftitemid="' + result.draftitemid + '">' + config.translations.delete + '</button>'
                        );

                        uploadedFiles.push({
                            draftitemid: result.draftitemid,
                            filename: result.name
                        });
                        updateHiddenField();
                    }
                } catch (e) {
                    console.error('Ошибка парсинга ответа', e);
                    data.context.remove();
                }
            },
            fail: function (e, data) {
                var errorMessage = config.translations.upload_error;
                if (data.errorThrown) {
                    errorMessage = data.errorThrown;
                } else if (data.result && data.result[0] && data.result[0].error) {
                    errorMessage = data.result[0].error;
                }
                showErrorModal(errorMessage);
                data.context.remove();
            }
        }).on('fileuploadprocessalways', function (e, data) {
            if (data.files.error) {
                showErrorModal(data.files[0].error);
            }
        });

        if (config.params.userid !== null) {
            $container.fileupload('option', 'formData', {
                component: config.params.component,
                filearea: config.params.filearea,
                itemid: config.params.itemid,
                contextid: config.params.contextid,
                userid: config.params.userid
            });
        }

        // Загружаем существующие файлы
        if (config.fileData && Array.isArray(config.fileData) && config.fileData.length > 0) {
            var $files = $container.find('.files');
            config.fileData.forEach(function (file) {
                var isImage = file.mimetype && file.mimetype.startsWith('image/');
                var previewHtml = '';
                if (isImage && file.download_url) {
                    previewHtml = '<img src="' + file.download_url + '" alt="' + escapeHtml(file.filename) + '">';
                } else {
                    previewHtml = '<span style="font-size: 40px; color: #ccc;">📄</span>';
                }

                var $fileItem = $('<div class="template-download" data-draftitemid="' + file.draftitemid + '">' +
                    '<div class="preview">' + previewHtml + '</div>' +
                    '<div style="flex: 1;">' +
                    '<div class="name">' + escapeHtml(file.filename) + '</div>' +
                    '<div class="size">' + (file.filesize / 1024).toFixed(2) + ' KB</div>' +
                    '</div>' +
                    '<button type="button" class="delete" data-draftitemid="' + file.draftitemid + '">' + config.translations.delete + '</button>' +
                    '</div>');
                $files.append($fileItem);

                uploadedFiles.push({
                    draftitemid: file.draftitemid,
                    filename: file.filename
                });
            });
            updateHiddenField();
        }

        // Обработчик удаления файла
        $(document).on('click', '#' + config.containerId + ' .delete', function () {
            var draftitemid = $(this).data('draftitemid');
            if (confirm(config.translations.delete_confirmation)) {
                uploadedFiles = uploadedFiles.filter(function (file) {
                    return file.draftitemid !== draftitemid;
                });
                updateHiddenField();
                $(this).closest('.template-upload, .template-download').remove();
            }
        });
    };

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
})(window);

