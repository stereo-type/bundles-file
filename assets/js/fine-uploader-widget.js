/**
 * Fine Uploader Widget JavaScript
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @requires fine-uploader - библиотека должна быть загружена до этого скрипта
 */

(function (window) {
    'use strict';

    /**
     * Инициализирует Fine Uploader виджет
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
    window.SlcorpFileBundle.FineUploaderWidget = function (config) {
        if (typeof qq === 'undefined' || typeof qq.FineUploader === 'undefined') {
            console.error('[SlcorpFileBundle] Fine Uploader is not loaded');
            return;
        }

        var $ = typeof jQuery !== 'undefined' ? jQuery : null;
        var hiddenField = document.getElementById(config.hiddenFieldId);
        var uploadedFiles = [];

        // Нормализуем скрытое поле
        var currentValue = hiddenField.value || '[]';
        try {
            var parsed = JSON.parse(currentValue);
            hiddenField.value = Array.isArray(parsed) ? JSON.stringify(parsed) : JSON.stringify(parsed ? [parsed] : []);
        } catch (e) {
            hiddenField.value = currentValue && currentValue !== '[]' ? JSON.stringify([currentValue]) : '[]';
        }

        function updateHiddenField() {
            var draftItemIds = uploadedFiles.map(function (file) {
                return file.draftitemid;
            });
            hiddenField.value = JSON.stringify(draftItemIds);
        }

        function showErrorModal(message) {
            var modal = document.getElementById('error-modal-' + config.hiddenFieldId);
            var messageEl = modal.querySelector('.error-message');
            messageEl.textContent = message;
            modal.style.display = 'flex';
        }

        function hideErrorModal() {
            var modal = document.getElementById('error-modal-' + config.hiddenFieldId);
            modal.style.display = 'none';
        }

        // Обработчики закрытия модального окна
        var modal = document.getElementById('error-modal-' + config.hiddenFieldId);
        if (modal) {
            var closeButtons = modal.querySelectorAll('.error-modal-close, .error-modal-ok, .error-modal-overlay');
            closeButtons.forEach(function (btn) {
                btn.addEventListener('click', hideErrorModal);
            });
            var content = modal.querySelector('.error-modal-content');
            if (content) {
                content.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        }

        // Подготовка разрешенных MIME типов для Fine Uploader
        var allowedExtensions = [];
        if (config.allowedExtensions) {
            var allowedMimeTypes = config.allowedExtensions.split(',').map(function (type) {
                return type.trim();
            }).filter(function (type) {
                return type.length > 0;
            });

            // Преобразуем MIME типы в расширения
            allowedExtensions = allowedMimeTypes.map(function (mime) {
                var parts = mime.split('/');
                if (parts.length === 2) {
                    var ext = parts[1];
                    if (ext === 'jpeg') return 'jpg,jpeg';
                    return ext;
                }
                return '';
            }).filter(function (ext) {
                return ext.length > 0;
            });
        }

        // Инициализация Fine Uploader
        var uploaderConfig = {
            element: document.getElementById(config.containerId),
            request: {
                endpoint: config.uploadUrl,
                params: {
                    component: config.params.component,
                    filearea: config.params.filearea,
                    itemid: config.params.itemid,
                    contextid: config.params.contextid
                }
            },
            validation: {
                allowedExtensions: allowedExtensions.length > 0 ? allowedExtensions : null,
                sizeLimit: config.maxFileSize,
                itemLimit: config.maxFiles
            },
            multiple: config.maxFiles > 1,
            callbacks: {
                onSubmit: function (id, fileName) {
                    // Если maxFiles = 1, удаляем предыдущие файлы
                    if (config.maxFiles === 1 && uploadedFiles.length >= 1) {
                        var allFiles = uploader.getUploads();
                        allFiles.forEach(function (file) {
                            if (file.status === qq.status.UPLOAD_SUCCESSFUL) {
                                uploader.deleteFile(file.id);
                            }
                        });
                        uploadedFiles = [];
                        updateHiddenField();
                    }
                    return true;
                },
                onComplete: function (id, fileName, responseJSON) {
                    if (responseJSON.success && responseJSON.draftitemid) {
                        var fileItem = uploader.getItemByFileId(id);
                        if (fileItem) {
                            fileItem.setAttribute('data-draftitemid', responseJSON.draftitemid);
                        }

                        uploadedFiles.push({
                            draftitemid: responseJSON.draftitemid,
                            filename: responseJSON.name || fileName
                        });
                        updateHiddenField();
                    }
                },
                onError: function (id, name, errorReason, xhr) {
                    var errorMessage = errorReason || config.translations.upload_error;
                    if (xhr && xhr.responseText) {
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.error) {
                                errorMessage = errorResponse.error;
                            }
                        } catch (e) {
                            // Используем стандартное сообщение
                        }
                    }
                    showErrorModal(errorMessage);
                },
                onValidate: function (data, buttonContainer) {
                    return true;
                },
                onValidateBatch: function (fileOrBlobDataArray, buttonContainer) {
                    if (uploadedFiles.length + fileOrBlobDataArray.length > config.maxFiles) {
                        showErrorModal(config.translations.max_files_exceeded.replace('%maxFiles%', config.maxFiles));
                        return false;
                    }
                    return true;
                }
            }
        };

        if (config.params.userid !== null) {
            uploaderConfig.request.params.userid = config.params.userid;
        }

        var uploader = new qq.FineUploader(uploaderConfig);

        // Загружаем существующие файлы
        if (config.fileData && Array.isArray(config.fileData) && config.fileData.length > 0) {
            var uploadList = document.querySelector('#' + config.containerId + ' .qq-upload-list');
            if (!uploadList && $) {
                uploadList = $('#' + config.containerId).find('.qq-upload-list')[0];
            }
            if (!uploadList) {
                uploadList = document.createElement('ul');
                uploadList.className = 'qq-upload-list';
                document.getElementById(config.containerId).appendChild(uploadList);
            }

            config.fileData.forEach(function (file) {
                var isImage = file.mimetype && file.mimetype.startsWith('image/');
                var previewHtml = '';
                if (isImage && file.download_url) {
                    previewHtml = '<img src="' + file.download_url + '" alt="' + escapeHtml(file.filename) + '">';
                } else {
                    previewHtml = '<span style="font-size: 40px; color: #ccc;">📄</span>';
                }

                var fileItem = document.createElement('li');
                fileItem.className = 'qq-upload-success';
                fileItem.setAttribute('data-draftitemid', file.draftitemid);
                fileItem.innerHTML =
                    '<div class="qq-thumbnail-wrapper">' + previewHtml + '</div>' +
                    '<div class="qq-file-info">' +
                    '<div class="qq-file-name">' + escapeHtml(file.filename) + '</div>' +
                    '<div class="qq-file-size">' + (file.filesize / 1024).toFixed(2) + ' KB</div>' +
                    '</div>' +
                    '<span class="qq-upload-status-text qq-upload-status-success">' + config.translations.uploaded + '</span>' +
                    '<button type="button" class="qq-upload-delete" data-draftitemid="' + file.draftitemid + '">' + config.translations.delete + '</button>';
                uploadList.appendChild(fileItem);

                uploadedFiles.push({
                    draftitemid: file.draftitemid,
                    filename: file.filename
                });
            });
            updateHiddenField();
        }

        // Обработчик удаления файла
        if ($) {
            $(document).on('click', '#' + config.containerId + ' .qq-upload-delete', function () {
                var draftitemid = $(this).data('draftitemid');
                if (confirm(config.translations.delete_confirmation)) {
                    uploadedFiles = uploadedFiles.filter(function (file) {
                        return file.draftitemid !== draftitemid;
                    });
                    updateHiddenField();
                    $(this).closest('li').remove();
                }
            });
        } else {
            var container = document.getElementById(config.containerId);
            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('qq-upload-delete') || e.target.closest('.qq-upload-delete')) {
                    var button = e.target.classList.contains('qq-upload-delete') ? e.target : e.target.closest('.qq-upload-delete');
                    var draftitemid = button.getAttribute('data-draftitemid');
                    if (confirm(config.translations.delete_confirmation)) {
                        uploadedFiles = uploadedFiles.filter(function (file) {
                            return file.draftitemid !== draftitemid;
                        });
                        updateHiddenField();
                        button.closest('li').remove();
                    }
                }
            });
        }
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

