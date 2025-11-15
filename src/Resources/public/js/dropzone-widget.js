/**
 * Dropzone Widget JavaScript
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @requires Dropzone - библиотека должна быть загружена до этого скрипта
 * @see https://www.dropzone.dev/js/
 */

/// <reference path="./dropzone.d.ts" />

/**
 * @typedef {Object} DropzoneConfig
 * @property {string} url - URL для загрузки файлов
 * @property {string} [paramName='file'] - Имя параметра для файла
 * @property {number} [maxFiles=1] - Максимальное количество файлов
 * @property {boolean} [addRemoveLinks=true] - Показывать кнопки удаления
 * @property {boolean} [autoProcessQueue=true] - Автоматически загружать файлы
 * @property {number} [thumbnailWidth=200] - Ширина превью
 * @property {number} [thumbnailHeight=200] - Высота превью
 * @property {string} [thumbnailMethod='contain'] - Метод масштабирования: 'contain' или 'crop'
 * @property {string|null} [acceptedFiles] - Разрешенные MIME типы (через запятую)
 * @property {number} [maxFilesize] - Максимальный размер файла в МБ
 * @property {string} [dictRemoveFile] - Текст кнопки удаления
 * @property {string} [dictCancelUpload] - Текст кнопки отмены
 * @property {string} [dictCancelUploadConfirmation] - Подтверждение отмены
 * @property {string} [dictDefaultMessage] - Сообщение по умолчанию
 * @property {string} [dictFallbackMessage] - Сообщение для старых браузеров
 * @property {string} [dictFileTooBig] - Сообщение о большом файле
 * @property {string} [dictInvalidFileType] - Сообщение о неверном типе
 * @property {string} [dictResponseError] - Сообщение об ошибке сервера
 * @property {string} [dictMaxFilesExceeded] - Сообщение о превышении лимита
 * @property {Function} [sending] - Callback перед отправкой
 * @property {Function} [init] - Callback инициализации
 * @property {Function} [success] - Callback успешной загрузки
 * @property {Function} [error] - Callback ошибки
 */

/**
 * @typedef {Object} DropzoneInstance
 * @property {Function} emit - Отправить событие
 * @property {Array} files - Массив файлов
 * @property {Function} on - Подписаться на событие
 * @property {Function} off - Отписаться от события
 */

(function (window) {
    'use strict';

    /**
     * Инициализирует Dropzone виджет
     *
     * @param {Object} config - Конфигурация виджета
     * @param {string} config.fieldId - ID контейнера Dropzone
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
     * @returns {DropzoneInstance} Экземпляр Dropzone
     */
    window.SlcorpFileBundle = window.SlcorpFileBundle || {};
    window.SlcorpFileBundle.DropzoneWidget = function (config) {
        // Отключаем автодискавери Dropzone
        if (typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
        }

        /**
         * @type {DropzoneConfig}
         */
        var dropzoneConfig = {
            url: config.uploadUrl,
            paramName: 'file',
            maxFiles: config.maxFiles || 1,
            addRemoveLinks: true,
            autoProcessQueue: true,
            thumbnailWidth: 200,
            thumbnailHeight: 200,
            thumbnailMethod: 'contain',
            acceptedFiles: config.allowedExtensions || null,
            maxFilesize: (config.maxFileSize / 1048576).toFixed(2),
            // Переводы
            dictRemoveFile: config.translations.remove_file,
            dictCancelUpload: config.translations.cancel_upload,
            dictCancelUploadConfirmation: config.translations.cancel_upload_confirmation,
            dictDefaultMessage: config.translations.default_message,
            dictFallbackMessage: config.translations.fallback_message,
            dictFileTooBig: config.translations.file_too_big.replace(/%filesize%/g, '{{filesize}}').replace(/%maxFilesize%/g, '{{maxFilesize}}'),
            dictInvalidFileType: config.translations.invalid_file_type,
            dictResponseError: config.translations.response_error.replace(/%statusCode%/g, '{{statusCode}}'),
            dictMaxFilesExceeded: config.translations.max_files_exceeded,
            sending: function (file, xhr, formData) {
                formData.append('component', config.params.component);
                formData.append('filearea', config.params.filearea);
                formData.append('itemid', config.params.itemid);
                formData.append('contextid', config.params.contextid);
                if (config.params.userid !== null) {
                    formData.append('userid', config.params.userid);
                }
            },
            init: function () {
                const thisDropzone = this;
                const fileData = config.fileData || [];

                // Добавляем существующие файлы
                if (fileData && Array.isArray(fileData) && fileData.length > 0) {
                    fileData.forEach(function (file) {
                        var mockFile = {
                            name: file.filename,
                            size: file.filesize,
                            type: file.mimetype || 'application/octet-stream',
                            status: 'success',
                            accepted: true,
                            draftitemid: file.draftitemid
                        };

                        thisDropzone.emit('addedfile', mockFile);
                        if (file.mimetype && file.mimetype.startsWith('image/')) {
                            thisDropzone.emit('thumbnail', mockFile, file.download_url || '');
                        }
                        thisDropzone.emit('complete', mockFile);
                        thisDropzone.files.push(mockFile);
                    });
                }

                // Нормализуем скрытое поле - всегда должен быть массив (JSON)
                const hiddenField = document.getElementById(config.hiddenFieldId || config.fieldId);
                if (hiddenField) {
                    const currentValue = hiddenField.value || '[]';
                    try {
                        const parsed = JSON.parse(currentValue);
                        if (!Array.isArray(parsed)) {
                            hiddenField.value = parsed ? JSON.stringify([parsed]) : '[]';
                        } else {
                            hiddenField.value = JSON.stringify(parsed);
                        }
                    } catch (e) {
                        if (currentValue && currentValue !== '[]') {
                            hiddenField.value = JSON.stringify([currentValue]);
                        } else {
                            hiddenField.value = '[]';
                        }
                    }
                }
            },
            success: function (file, response) {
                // Добавляем draftitemid в массив в скрытом поле (всегда массив)
                if (response && response.draftitemid) {
                    const hiddenField = document.getElementById(config.hiddenFieldId || config.fieldId);
                    if (hiddenField) {
                        const currentValue = hiddenField.value || '[]';
                        let draftItemIds = [];

                        try {
                            draftItemIds = JSON.parse(currentValue);
                            if (!Array.isArray(draftItemIds)) {
                                draftItemIds = [];
                            }
                        } catch (e) {
                            draftItemIds = [];
                        }

                        // Добавляем новый draftitemid
                        if (!draftItemIds.includes(response.draftitemid)) {
                            draftItemIds.push(response.draftitemid);
                            file.draftitemid = response.draftitemid;
                        }

                        hiddenField.value = JSON.stringify(draftItemIds);
                    }
                }
            }
        };

        /**
         * @type {DropzoneInstance}
         */
        var dropzone = new Dropzone('#' + config.fieldId, dropzoneConfig);

        // Обработчик удаления файла
        dropzone.on('removedfile', function (file) {
            // Удаляем draftitemid из массива в скрытом поле (всегда массив)
            var hiddenField = document.getElementById(config.hiddenFieldId || config.fieldId);
            if (hiddenField) {
                var currentValue = hiddenField.value || '[]';
                var draftItemIds = [];

                try {
                    draftItemIds = JSON.parse(currentValue);
                    if (!Array.isArray(draftItemIds)) {
                        draftItemIds = [];
                    }
                } catch (e) {
                    draftItemIds = [];
                }

                // Удаляем draftitemid этого файла
                if (file.draftitemid) {
                    draftItemIds = draftItemIds.filter(function (id) {
                        return id !== file.draftitemid;
                    });
                }

                hiddenField.value = JSON.stringify(draftItemIds);
            }
        });

        return dropzone;
    };
})(window);
