/**
 * TypeScript definitions for Dropzone.js
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @see https://www.dropzone.dev/js/
 */

declare class Dropzone {
    static autoDiscover: boolean;
    static instances: Dropzone[];
    static options: DropzoneOptions;

    constructor(selector: string | HTMLElement, options?: DropzoneOptions);

    files: DropzoneFile[];
    element: HTMLElement;
    options: DropzoneOptions;
    hiddenFileInput: HTMLInputElement | null;
    url: string;
    acceptedFiles: string[];
    maxFiles: number | null;
    maxFilesize: number;
    parallelUploads: number;
    uploadMultiple: boolean;
    createImageThumbnails: boolean;
    maxThumbnailFilesize: number;
    thumbnailWidth: number;
    thumbnailHeight: number;
    thumbnailMethod: 'contain' | 'crop' | 'cropresize';
    resizeWidth: number | null;
    resizeHeight: number | null;
    resizeMimeType: string | null;
    resizeQuality: number;
    resizeMethod: 'contain' | 'crop';
    filesizeBase: number;
    maxFilesExceeded: boolean;
    params: Record<string, string | number>;
    headers: Record<string, string>;
    timeout: number;
    clickable: boolean | string | HTMLElement | (string | HTMLElement)[];
    ignoreHiddenFiles: boolean;
    acceptedMimeTypes: string[] | null;
    autoProcessQueue: boolean;
    autoQueue: boolean;
    addRemoveLinks: boolean;
    previewsContainer: HTMLElement | null;
    hiddenInputContainer: HTMLElement;
    capture: string | null;
    renameFile: ((file: DropzoneFile) => string) | null;
    forceFallback: boolean;
    fallback: (() => void) | null;
    dictDefaultMessage: string;
    dictFallbackMessage: string;
    dictFallbackText: string;
    dictFileTooBig: string;
    dictInvalidFileType: string;
    dictResponseError: string;
    dictCancelUpload: string;
    dictUploadCanceled: string;
    dictCancelUploadConfirmation: string;
    dictRemoveFile: string;
    dictRemoveFileConfirmation: string | null;
    dictMaxFilesExceeded: string;
    dictFileSizeUnits: Record<string, string>;

    // Methods
    accept(file: DropzoneFile, done: (error?: string) => void): void;

    addFile(file: DropzoneFile): void;

    removeFile(file: DropzoneFile): void;

    removeAllFiles(cancelIfNecessary?: boolean): void;

    processQueue(): void;

    cancelUpload(file: DropzoneFile): void;

    processFiles(files: DropzoneFile[]): void;

    uploadFile(file: DropzoneFile): void;

    getAcceptedFiles(): DropzoneFile[];

    getRejectedFiles(): DropzoneFile[];

    getQueuedFiles(): DropzoneFile[];

    getUploadingFiles(): DropzoneFile[];

    getActiveFiles(): DropzoneFile[];

    destroy(): void;

    disable(): void;

    enable(): void;

    filesize(size: number): string;

    updateTotalUploadProgress(): void;

    getFallbackForm(): HTMLFormElement | null;

    getExistingFallback(): HTMLElement | null;

    setupEventListeners(): void;

    removeEventListeners(): void;

    setupHiddenFileInput(): void;

    setupDragAndDrop(): void;

    removeDragAndDrop(): void;

    createThumbnail(file: DropzoneFile, callback: () => void): void;

    createThumbnailFromUrl(file: DropzoneFile, width: number, height: number, resizeMethod: string, fixOrientation: boolean, callback: () => void): void;

    on(event: string, callback: (...args: any[]) => void): void;

    off(event: string, callback?: (...args: any[]) => void): void;

    emit(event: string, ...args: any[]): void;
}

interface DropzoneOptions {
    url?: string;
    method?: 'post' | 'put';
    withCredentials?: boolean;
    timeout?: number;
    parallelUploads?: number;
    uploadMultiple?: boolean;
    maxFilesize?: number;
    paramName?: string;
    createImageThumbnails?: boolean;
    maxThumbnailFilesize?: number;
    thumbnailWidth?: number;
    thumbnailHeight?: number;
    thumbnailMethod?: 'contain' | 'crop' | 'cropresize';
    resizeWidth?: number;
    resizeHeight?: number;
    resizeMimeType?: string;
    resizeQuality?: number;
    resizeMethod?: 'contain' | 'crop';
    filesizeBase?: number;
    maxFiles?: number | null;
    params?: Record<string, string | number>;
    headers?: Record<string, string>;
    clickable?: boolean | string | HTMLElement | (string | HTMLElement)[];
    ignoreHiddenFiles?: boolean;
    acceptedFiles?: string;
    acceptedMimeTypes?: string[];
    autoProcessQueue?: boolean;
    autoQueue?: boolean;
    addRemoveLinks?: boolean;
    previewsContainer?: HTMLElement | string | null;
    hiddenInputContainer?: HTMLElement;
    capture?: string | null;
    renameFile?: (file: DropzoneFile) => string;
    forceFallback?: boolean;
    fallback?: () => void;
    dictDefaultMessage?: string;
    dictFallbackMessage?: string;
    dictFallbackText?: string;
    dictFileTooBig?: string;
    dictInvalidFileType?: string;
    dictResponseError?: string;
    dictCancelUpload?: string;
    dictUploadCanceled?: string;
    dictCancelUploadConfirmation?: string;
    dictRemoveFile?: string;
    dictRemoveFileConfirmation?: string | null;
    dictMaxFilesExceeded?: string;
    dictFileSizeUnits?: Record<string, string>;
    init?: (this: Dropzone) => void;
    accept?: (file: DropzoneFile, done: (error?: string) => void) => void;
    addedfile?: (file: DropzoneFile) => void;
    addedfiles?: (files: DropzoneFile[]) => void;
    removedfile?: (file: DropzoneFile) => void;
    thumbnail?: (file: DropzoneFile, dataUrl: string) => void;
    error?: (file: DropzoneFile, message: string | Error, xhr?: XMLHttpRequest) => void;
    errormultiple?: (files: DropzoneFile[], message: string | Error, xhr?: XMLHttpRequest) => void;
    processing?: (file: DropzoneFile) => void;
    processingmultiple?: (files: DropzoneFile[]) => void;
    uploadprogress?: (file: DropzoneFile, progress: number, bytesSent: number, totalBytes: number) => void;
    sending?: (file: DropzoneFile, xhr: XMLHttpRequest, formData: FormData) => void;
    sendingmultiple?: (files: DropzoneFile[], xhr: XMLHttpRequest, formData: FormData) => void;
    success?: (file: DropzoneFile, response: any) => void;
    successmultiple?: (files: DropzoneFile[], response: any) => void;
    canceled?: (file: DropzoneFile) => void;
    canceledmultiple?: (files: DropzoneFile[]) => void;
    complete?: (file: DropzoneFile) => void;
    completemultiple?: (files: DropzoneFile[]) => void;
    maxfilesexceeded?: (file: DropzoneFile) => void;
    maxfilesreached?: (files: DropzoneFile[]) => void;
    queuecomplete?: () => void;
    reset?: () => void;
}

interface DropzoneFile extends File {
    status: 'queued' | 'uploading' | 'success' | 'error' | 'canceled';
    previewElement: HTMLElement;
    previewTemplate: HTMLElement;
    previewsContainer: HTMLElement;
    accepted: boolean;
    xhr?: XMLHttpRequest;
    upload?: {
        progress: number;
        total: number;
        bytesSent: number;
    };

    [key: string]: any; // Для дополнительных свойств, например draftitemid
}

// Dropzone доступен глобально как класс, дополнительное объявление не требуется

