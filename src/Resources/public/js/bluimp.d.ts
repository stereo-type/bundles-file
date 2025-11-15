/**
 * TypeScript definitions for jQuery File Upload Plugin
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @see https://github.com/blueimp/jQuery-File-Upload
 */

interface JQuery {
    fileupload(options?: JQueryFileUploadOptions): JQuery;

    fileupload(action: string, ...args: any[]): JQuery;
}

interface JQueryFileUploadOptions {
    url?: string;
    type?: string;
    dataType?: string;
    autoUpload?: boolean;
    singleFileUploads?: boolean;
    limitMultiFileUploads?: number;
    limitMultiFileUploadSize?: number;
    limitMultiFileUploadSizeOverhead?: number;
    sequentialUploads?: boolean;
    limitConcurrentUploads?: number;
    forceIframeTransport?: boolean;
    redirect?: string;
    redirectParamName?: string;
    postMessage?: string;
    multipart?: boolean;
    maxChunkSize?: number;
    formData?: any;
    add?: (e: JQueryEventObject, data: JQueryFileUploadData) => void | boolean;
    submit?: (e: JQueryEventObject, data: JQueryFileUploadData) => void | boolean;
    send?: (e: JQueryEventObject, data: JQueryFileUploadData) => void | boolean;
    done?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fail?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    always?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    progress?: (e: JQueryEventObject, data: JQueryFileUploadProgressData) => void;
    progressall?: (e: JQueryEventObject, data: JQueryFileUploadProgressAllData) => void;
    start?: (e: JQueryEventObject) => void;
    stop?: (e: JQueryEventObject) => void;
    change?: (e: JQueryEventObject, data: JQueryFileUploadChangeData) => void;
    paste?: (e: JQueryEventObject, data: JQueryFileUploadPasteData) => void;
    drop?: (e: JQueryEventObject, data: JQueryFileUploadDropData) => void;
    dragover?: (e: JQueryEventObject) => void;
    chunksend?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void | boolean;
    chunkdone?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void;
    chunkfail?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void;
    chunkalways?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void;
    processstart?: (e: JQueryEventObject) => void;
    process?: (e: JQueryEventObject, data: JQueryFileUploadProcessData) => void;
    processdone?: (e: JQueryEventObject, data: JQueryFileUploadProcessData) => void;
    processfail?: (e: JQueryEventObject, data: JQueryFileUploadProcessData) => void;
    processalways?: (e: JQueryEventObject, data: JQueryFileUploadProcessData) => void;
    processstop?: (e: JQueryEventObject) => void;
    fileuploadadd?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadprocessstart?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadprocess?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadprocessdone?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadprocessfail?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadprocessalways?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadprocessstop?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadsubmit?: (e: JQueryEventObject, data: JQueryFileUploadData) => void | boolean;
    fileuploadsend?: (e: JQueryEventObject, data: JQueryFileUploadData) => void | boolean;
    fileuploaddone?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadfail?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadalways?: (e: JQueryEventObject, data: JQueryFileUploadData) => void;
    fileuploadprogress?: (e: JQueryEventObject, data: JQueryFileUploadProgressData) => void;
    fileuploadprogressall?: (e: JQueryEventObject, data: JQueryFileUploadProgressAllData) => void;
    fileuploadstart?: (e: JQueryEventObject) => void;
    fileuploadstop?: (e: JQueryEventObject) => void;
    fileuploadchange?: (e: JQueryEventObject, data: JQueryFileUploadChangeData) => void;
    fileuploadpaste?: (e: JQueryEventObject, data: JQueryFileUploadPasteData) => void;
    fileuploaddrop?: (e: JQueryEventObject, data: JQueryFileUploadDropData) => void;
    fileuploaddragover?: (e: JQueryEventObject) => void;
    fileuploadchunksend?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void | boolean;
    fileuploadchunkdone?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void;
    fileuploadchunkfail?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void;
    fileuploadchunkalways?: (e: JQueryEventObject, data: JQueryFileUploadChunkData) => void;
}

interface JQueryFileUploadData {
    files: File[];
    fileInput?: JQuery;
    form?: JQuery;
    originalFiles?: File[];
    paramName?: string | ((file: File) => string);
    url?: string;
    type?: string;
    dataType?: string;
    context?: JQuery;
    submit?: (data?: JQueryFileUploadData) => JQueryPromise<any>;
    abort?: () => void;
    result?: any;
    jqXHR?: JQueryXHR;
    textStatus?: string;
    errorThrown?: any;
}

interface JQueryFileUploadProgressData extends JQueryFileUploadData {
    loaded?: number;
    total?: number;
    bitrate?: number;
}

interface JQueryFileUploadProgressAllData {
    loaded?: number;
    total?: number;
    bitrate?: number;
}

interface JQueryFileUploadChangeData {
    files?: FileList | File[];
}

interface JQueryFileUploadPasteData {
    files?: FileList | File[];
}

interface JQueryFileUploadDropData {
    files?: FileList | File[];
    dataTransfer?: DataTransfer;
}

interface JQueryFileUploadChunkData extends JQueryFileUploadData {
    chunkSize?: number;
    contentRange?: string;
    blob?: Blob;
    formData?: FormData;
}

interface JQueryFileUploadProcessData extends JQueryFileUploadData {
    processQueue?: Array<{
        name: string;
        action: (data: JQueryFileUploadData, options: JQueryFileUploadOptions) => void;
    }>;
}

