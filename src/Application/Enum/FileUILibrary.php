<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Enum;

/**
 * Поддерживаемые UI библиотеки для загрузки файлов.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum FileUILibrary: string
{
    case FINE_UPLOADER = 'fineuploader';
    case DROPZONE = 'dropzone';
    case JQUERY_FILE_UPLOAD = 'jquery_file_upload';
    case PLUPLOAD = 'plupload';
    case UPLOADIFY = 'uploadify';
    case BLUIMP = 'bluimp';
}
