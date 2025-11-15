/**
 * Plupload Widget JavaScript
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @requires jQuery - библиотека должна быть загружена до этого скрипта
 * @requires plupload - библиотека должна быть загружена до этого скрипта
 */

(function (window) {
    'use strict';

    /**
     * Инициализирует Plupload виджет
     *
     * @param {Object} config - Конфигурация виджета
     * @param {string} config.containerId - ID контейнера виджета
     * @param {string} config.buttonContainerId - ID контейнера кнопки
     * @param {string} config.fileListId - ID контейнера списка файлов
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
    window.SlcorpFileBundle.PluploadWidget = function (config) {
        if (typeof plupload === 'undefined') {
            console.error('[SlcorpFileBundle] Plupload is not loaded');
            return;
        }

        var $ = typeof jQuery !== 'undefined' ? jQuery : null;
        var uploadedFiles = [];
        var hiddenField = document.getElementById(config.hiddenFieldId);
        var buttonContainer = document.getElementById(config.buttonContainerId);
        var fileList = document.getElementById(config.fileListId);

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

        // Подготовка разрешенных MIME типов для Plupload
        var allowedMimeTypes = [];
        if (config.allowedExtensions) {
            allowedMimeTypes = config.allowedExtensions.split(',').map(function (type) {
                return type.trim();
            }).filter(function (type) {
                return type.length > 0;
            });
        }

        // Преобразуем MIME типы в формат для Plupload
        var mimeTypes = [];
        if (allowedMimeTypes.length > 0) {
            mimeTypes = [{
                title: config.translations.allowed_files || "Allowed files",
                extensions: allowedMimeTypes.map(function (mime) {
                    var parts = mime.split('/');
                    if (parts.length === 2) {
                        var ext = parts[1];
                        if (ext === 'jpeg') ext = 'jpg,jpeg';
                        return ext;
                    }
                    return '';
                }).filter(function (ext) {
                    return ext.length > 0;
                }).join(',')
            }];
        }

        // Инициализация Plupload
        var uploaderConfig = {
            browse_button: config.buttonContainerId,
            url: config.uploadUrl,
            chunk_size: '1mb',
            max_file_size: (config.maxFileSize / 1048576) + 'mb',
            filters: {
                max_file_size: (config.maxFileSize / 1048576) + 'mb',
                prevent_duplicates: true
            },
            multipart_params: {
                component: config.params.component,
                filearea: config.params.filearea,
                itemid: config.params.itemid,
                contextid: config.params.contextid
            },
            init: {
                PostInit: function () {
                    // Создаем кнопку выбора файлов
                    var buttonText = config.maxFiles > 1 ? config.translations.select_files : config.translations.select_file;
                    buttonContainer.innerHTML = '<button type="button" class="plupload_button">' + buttonText + '</button>';
                },
                FilesAdded: function (up, files) {
                    // Если maxFiles = 1, удаляем предыдущий файл
                    if (config.maxFiles === 1 && uploadedFiles.length >= 1) {
                        fileList.innerHTML = '';
                        uploadedFiles = [];
                        updateHiddenField();
                        up.files = [];
                    }

                    // Проверяем максимальное количество файлов
                    if (uploadedFiles.length + files.length > config.maxFiles) {
                        showErrorModal(config.translations.max_files_exceeded.replace('%maxFiles%', config.maxFiles));
                        up.removeFile(files[files.length - 1]);
                        return;
                    }

                    // Создаем элементы для отображения файлов
                    plupload.each(files, function (file) {
                        var fileItem = document.createElement('div');
                        fileItem.id = file.id;
                        fileItem.className = 'plupload_file';
                        var isImage = file.type && file.type.startsWith('image/');
                        fileItem.innerHTML =
                            '<div class="plupload_file_content">' +
                            '<div class="plupload_file_preview">' +
                            (isImage ? '<img src="" alt="' + escapeHtml(file.name) + '">' : '<span style="font-size: 40px; color: #ccc;">📄</span>') +
                            '</div>' +
                            '<div class="plupload_file_info">' +
                            '<div class="plupload_file_name">' + escapeHtml(file.name) + '</div>' +
                            '<div class="plupload_file_size">' + plupload.formatSize(file.size) + '</div>' +
                            '<div class="plupload_file_progress"><div class="plupload_file_progress_bar" style="width: 0%"></div></div>' +
                            '</div>' +
                            '<span class="plupload_file_status uploading">' + config.translations.uploading + '</span>' +
                            '</div>';
                        fileList.appendChild(fileItem);

                        // Превью для изображений
                        if (isImage) {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                var img = fileItem.querySelector('.plupload_file_preview img');
                                if (img) img.src = e.target.result;
                            };
                            reader.readAsDataURL(file.getNative());
                        }
                    });

                    // Начинаем загрузку автоматически
                    up.start();
                },
                UploadProgress: function (up, file) {
                    var fileItem = document.getElementById(file.id);
                    if (fileItem) {
                        var progress = file.percent;
                        var progressBar = fileItem.querySelector('.plupload_file_progress_bar');
                        if (progressBar) {
                            progressBar.style.width = progress + '%';
                        }
                    }
                },
                FileUploaded: function (up, file, response) {
                    var fileItem = document.getElementById(file.id);
                    if (!fileItem) return;

                    try {
                        var result = JSON.parse(response.response);
                        if (result.result && result.result.draftitemid) {
                            var isImage = result.result.url && file.name.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                            var previewHtml = '';
                            if (isImage && result.result.url) {
                                previewHtml = '<img src="' + result.result.url + '" alt="' + escapeHtml(result.result.name) + '">';
                            } else {
                                previewHtml = '<span style="font-size: 40px; color: #ccc;">📄</span>';
                            }

                            fileItem.setAttribute('data-draftitemid', result.result.draftitemid);
                            fileItem.innerHTML =
                                '<div class="plupload_file_content">' +
                                '<div class="plupload_file_preview">' + previewHtml + '</div>' +
                                '<div class="plupload_file_info">' +
                                '<div class="plupload_file_name">' + escapeHtml(result.result.name) + '</div>' +
                                '<div class="plupload_file_size">' + plupload.formatSize(result.result.size) + '</div>' +
                                '</div>' +
                                '<button type="button" class="plupload_file_action delete" data-draftitemid="' + result.result.draftitemid + '">' + config.translations.delete + '</button>' +
                                '</div>';

                            uploadedFiles.push({
                                draftitemid: result.result.draftitemid,
                                filename: result.result.name
                            });
                            updateHiddenField();
                        }
                    } catch (e) {
                        console.error('Ошибка парсинга ответа', e);
                        fileItem.remove();
                    }
                },
                Error: function (up, err) {
                    var errorMessage = err.message || config.translations.upload_error;
                    if (err.code === plupload.FILE_SIZE_ERROR) {
                        errorMessage = config.translations.file_too_big.replace('%maxFileSize%', (config.maxFileSize / 1048576).toFixed(2) + ' MB');
                    } else if (err.code === plupload.FILE_EXTENSION_ERROR) {
                        errorMessage = config.translations.file_type_not_allowed.replace('%allowedTypes%', allowedMimeTypes.join(', '));
                    } else if (err.response) {
                        try {
                            var errorResponse = JSON.parse(err.response);
                            if (errorResponse.error && errorResponse.error.message) {
                                errorMessage = errorResponse.error.message;
                            }
                        } catch (e) {
                            // Используем стандартное сообщение
                        }
                    }

                    if (err.file && err.file.id) {
                        var fileItem = document.getElementById(err.file.id);
                        if (fileItem) {
                            fileItem.remove();
                        }
                    }

                    showErrorModal(errorMessage);
                }
            }
        };

        // Добавляем mime_types в filters если есть разрешенные типы
        if (mimeTypes.length > 0) {
            uploaderConfig.filters.mime_types = mimeTypes;
        }

        if (config.params.userid !== null) {
            uploaderConfig.multipart_params.userid = config.params.userid;
        }

        var uploader = new plupload.Uploader(uploaderConfig);
        uploader.init();

        // Загружаем существующие файлы
        if (config.fileData && Array.isArray(config.fileData) && config.fileData.length > 0) {
            config.fileData.forEach(function (file) {
                var fileItem = document.createElement('div');
                fileItem.className = 'plupload_file';
                fileItem.setAttribute('data-draftitemid', file.draftitemid);
                var isImage = file.mimetype && file.mimetype.startsWith('image/');
                var previewHtml = '';
                if (isImage && file.download_url) {
                    previewHtml = '<img src="' + file.download_url + '" alt="' + escapeHtml(file.filename) + '">';
                } else {
                    previewHtml = '<span style="font-size: 40px; color: #ccc;">📄</span>';
                }

                fileItem.innerHTML =
                    '<div class="plupload_file_content">' +
                    '<div class="plupload_file_preview">' + previewHtml + '</div>' +
                    '<div class="plupload_file_info">' +
                    '<div class="plupload_file_name">' + escapeHtml(file.filename) + '</div>' +
                    '<div class="plupload_file_size">' + (file.filesize / 1024).toFixed(2) + ' KB</div>' +
                    '</div>' +
                    '<button type="button" class="plupload_file_action delete" data-draftitemid="' + file.draftitemid + '">' + config.translations.delete + '</button>' +
                    '</div>';
                fileList.appendChild(fileItem);

                uploadedFiles.push({
                    draftitemid: file.draftitemid,
                    filename: file.filename
                });
            });
            updateHiddenField();
        }

        // Обработчик удаления файла
        if ($) {
            $(document).on('click', '#' + config.fileListId + ' .delete', function () {
                var draftitemid = $(this).data('draftitemid');
                if (confirm(config.translations.delete_confirmation)) {
                    uploadedFiles = uploadedFiles.filter(function (file) {
                        return file.draftitemid !== draftitemid;
                    });
                    updateHiddenField();
                    $(this).closest('.plupload_file').remove();
                }
            });
        } else {
            fileList.addEventListener('click', function (e) {
                if (e.target.classList.contains('delete') || e.target.closest('.delete')) {
                    var button = e.target.classList.contains('delete') ? e.target : e.target.closest('.delete');
                    var draftitemid = button.getAttribute('data-draftitemid');
                    if (confirm(config.translations.delete_confirmation)) {
                        uploadedFiles = uploadedFiles.filter(function (file) {
                            return file.draftitemid !== draftitemid;
                        });
                        updateHiddenField();
                        button.closest('.plupload_file').remove();
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

