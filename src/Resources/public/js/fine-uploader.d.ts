/**
 * TypeScript definitions for Fine Uploader
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @see https://fineuploader.com/
 */

declare namespace qq {
    interface FineUploaderOptions {
        element?: HTMLElement | string;
        button?: HTMLElement | string;
        multiple?: boolean;
        maxConnections?: number;
        disableCancelForFormUploads?: boolean;
        autoUpload?: boolean;
        request?: FineUploaderRequestOptions;
        validation?: FineUploaderValidationOptions;
        callbacks?: FineUploaderCallbacks;
        retry?: FineUploaderRetryOptions;
        chunking?: FineUploaderChunkingOptions;
        resume?: FineUploaderResumeOptions;
        deleteFile?: FineUploaderDeleteFileOptions;
        paste?: FineUploaderPasteOptions;
        camera?: FineUploaderCameraOptions;
        text?: FineUploaderTextOptions;
        template?: string;
        classes?: FineUploaderClasses;
        failedUploadTextDisplay?: FineUploaderFailedUploadTextDisplayOptions;
        dragAndDrop?: FineUploaderDragAndDropOptions;
        scaling?: FineUploaderScalingOptions;
        cors?: FineUploaderCorsOptions;
        blobs?: FineUploaderBlobsOptions;
        workarounds?: FineUploaderWorkaroundsOptions;
        session?: FineUploaderSessionOptions;
        formatFileName?: (fileName: string) => string;
        showMessage?: (message: string) => void;
        showPrompt?: (message: string, defaultValue: string, callback: (value: string) => void) => void;
        showConfirm?: (message: string, callback: (confirmed: boolean) => void) => void;
    }

    interface FineUploaderRequestOptions {
        endpoint: string;
        inputName?: string;
        params?: Record<string, any> | (() => Record<string, any>);
        customHeaders?: Record<string, string> | (() => Record<string, string>);
        forceMultipart?: boolean;
        uuidName?: string;
        totalFileSizeName?: string;
        filenameParam?: string;
        method?: string;
    }

    interface FineUploaderValidationOptions {
        allowedExtensions?: string[];
        sizeLimit?: number;
        minSizeLimit?: number;
        stopOnFirstInvalidFile?: boolean;
        itemLimit?: number;
        image?: FineUploaderImageValidationOptions;
    }

    interface FineUploaderImageValidationOptions {
        maxSize?: number;
        minSize?: {
            width?: number;
            height?: number;
        };
        maxSize?: {
            width?: number;
            height?: number;
        };
    }

    interface FineUploaderCallbacks {
        onSubmit?: (id: number, name: string) => void | boolean;
        onSubmitted?: (id: number, name: string) => void;
        onComplete?: (id: number, name: string, responseJSON: any, xhr: XMLHttpRequest) => void;
        onAllComplete?: (succeeded: number[], failed: number[]) => void;
        onCancel?: (id: number, name: string) => void;
        onUpload?: (id: number, name: string) => void;
        onUploadChunk?: (id: number, name: string, chunkData: FineUploaderChunkData) => void;
        onUploadChunkSuccess?: (id: number, chunkData: FineUploaderChunkData, response: any, xhr: XMLHttpRequest) => void;
        onProgress?: (id: number, name: string, loaded: number, total: number) => void;
        onError?: (id: number, name: string, errorReason: string, xhr: XMLHttpRequest) => void;
        onValidate?: (data: FineUploaderValidateData, buttonContainer: HTMLElement) => void | boolean;
        onValidateBatch?: (fileNames: string[], buttonContainer: HTMLElement) => void | boolean;
        onSubmittedDelete?: (id: number) => void;
        onDelete?: (id: number) => void;
        onDeleteComplete?: (id: number, xhr: XMLHttpRequest, isError: boolean) => void;
        onPasteReceived?: (blob: Blob) => void | boolean;
        onStatusChange?: (id: number, oldStatus: string, newStatus: string) => void;
        onResume?: (id: number, name: string, chunkData: FineUploaderChunkData) => void;
        onManualRetry?: (id: number, name: string) => void | boolean;
        onAutoRetry?: (id: number, name: string, attemptNumber: number) => void | boolean;
    }

    interface FineUploaderChunkData {
        partIndex: number;
        startByte: number;
        endByte: number;
        totalParts: number;
    }

    interface FineUploaderValidateData {
        name: string;
        size: number;
    }

    interface FineUploaderRetryOptions {
        enableAuto?: boolean;
        maxAutoAttempts?: number;
        autoAttemptDelay?: number;
        preventRetryResponseProperty?: string;
    }

    interface FineUploaderChunkingOptions {
        enabled?: boolean;
        concurrent?: FineUploaderConcurrentOptions;
        mandatory?: boolean;
        paramNames?: {
            partIndex?: string;
            partByteOffset?: string;
            chunkSize?: string;
            totalFileSize?: string;
            totalParts?: string;
        };
        partSize?: number;
        success?: FineUploaderChunkingSuccessOptions;
    }

    interface FineUploaderConcurrentOptions {
        enabled?: boolean;
    }

    interface FineUploaderChunkingSuccessOptions {
        endpoint?: string;
        params?: Record<string, any> | (() => Record<string, any>);
        headers?: Record<string, string> | (() => Record<string, string>);
    }

    interface FineUploaderResumeOptions {
        enabled?: boolean;
        recordsExpireIn?: number;
        paramNames?: {
            resuming?: string;
        };
    }

    interface FineUploaderDeleteFileOptions {
        enabled?: boolean;
        endpoint?: string;
        method?: string;
        customHeaders?: Record<string, string> | (() => Record<string, string>);
        params?: Record<string, any> | (() => Record<string, any>);
    }

    interface FineUploaderPasteOptions {
        defaultName?: string;
        targetElement?: HTMLElement | string;
    }

    interface FineUploaderCameraOptions {
        ios?: boolean;
    }

    interface FineUploaderTextOptions {
        defaultResponseError?: string;
        fileInputTitle?: string;
        sizeSymbols?: string[];
    }

    interface FineUploaderClasses {
        button?: string;
        hideSingleButton?: boolean;
        hideDropzone?: boolean;
        hideTextarea?: boolean;
        hideEditButton?: boolean;
        hideRetryButton?: boolean;
        hideContinueButton?: boolean;
        hideCancelButton?: boolean;
        hidePauseButton?: boolean;
        hideDeleteButton?: boolean;
        hideProgressBar?: boolean;
        hideSuccessMark?: boolean;
        hideFailureMark?: boolean;
        hideSuccessIcon?: boolean;
        hideFailureIcon?: boolean;
        hideFileInput?: boolean;
        hideDragDropHelpText?: boolean;
        hideUploadButton?: boolean;
        hideDropzoneText?: boolean;
        hideFileInputButton?: boolean;
        hideFileInputText?: boolean;
        hideFileInputButtonText?: boolean;
        hideFileInputButtonIcon?: boolean;
        hideFileInputButtonProgress?: boolean;
        hideFileInputButtonSuccess?: boolean;
        hideFileInputButtonFailure?: boolean;
        hideFileInputButtonRetry?: boolean;
        hideFileInputButtonCancel?: boolean;
        hideFileInputButtonPause?: boolean;
        hideFileInputButtonDelete?: boolean;
        hideFileInputButtonEdit?: boolean;
        hideFileInputButtonContinue?: boolean;
        hideFileInputButtonUpload?: boolean;
        hideFileInputButtonDragDrop?: boolean;
        hideFileInputButtonDragDropText?: boolean;
        hideFileInputButtonDragDropIcon?: boolean;
        hideFileInputButtonDragDropProgress?: boolean;
        hideFileInputButtonDragDropSuccess?: boolean;
        hideFileInputButtonDragDropFailure?: boolean;
        hideFileInputButtonDragDropRetry?: boolean;
        hideFileInputButtonDragDropCancel?: boolean;
        hideFileInputButtonDragDropPause?: boolean;
        hideFileInputButtonDragDropDelete?: boolean;
        hideFileInputButtonDragDropEdit?: boolean;
        hideFileInputButtonDragDropContinue?: boolean;
        hideFileInputButtonDragDropUpload?: boolean;
    }

    interface FineUploaderFailedUploadTextDisplayOptions {
        mode?: 'default' | 'custom' | 'none';
        maxChars?: number;
        responseProperty?: string;
        enableTooltip?: boolean;
    }

    interface FineUploaderDragAndDropOptions {
        extraDropzones?: (HTMLElement | string)[];
        disableDefaultDropzone?: boolean;
    }

    interface FineUploaderScalingOptions {
        sendOriginal?: boolean;
        includeResponse?: boolean;
        orient?: boolean;
        defaultType?: string;
        defaultQuality?: number;
        failureText?: string;
        hideScaled?: boolean;
        sizes?: FineUploaderSize[];
    }

    interface FineUploaderSize {
        name: string;
        maxSize?: number;
        type?: string;
        quality?: number;
    }

    interface FineUploaderCorsOptions {
        expected?: boolean;
        sendCredentials?: boolean;
        allowXdr?: boolean;
    }

    interface FineUploaderBlobsOptions {
        defaultName?: string;
    }

    interface FineUploaderWorkaroundsOptions {
        iosEmptyVideos?: boolean;
        ios8SafariUploads?: boolean;
        ios9SafariUploads?: boolean;
        androidEmptyFiles?: boolean;

        [key: string]: any; // Для дополнительных опций
    }

    interface FineUploaderSessionOptions {
        endpoint?: string;
        params?: Record<string, any> | (() => Record<string, any>);
        customHeaders?: Record<string, string> | (() => Record<string, string>);
    }

    class FineUploader {
        constructor(options: FineUploaderOptions);

        // Methods
        addFiles(files: File | FileList | File[]): void;
        addFiles(input: HTMLInputElement): void;
        addFiles(blob: Blob, name?: string): void;
        addFiles(dataTransfer: DataTransfer): void;

        cancel(id: number): void;

        cancelAll(): void;

        clearStoredFiles(): void;

        continueUpload(id: number): void;

        deleteFile(id: number): void;

        drawThumbnail(id: number, container: HTMLElement, maxSize?: number, fromServer?: boolean): void;
        drawThumbnail(id: number, container: HTMLElement, maxSize?: number, fromServer?: boolean, customResize?: (image: HTMLImageElement) => void): void;

        getButton(id: number): HTMLElement | null;

        getFile(id: number): FineUploaderFile | null;

        getFileId(fileOrBlob: File | Blob): number | null;

        getInProgress(): number;

        getNetUploads(): number;

        getRemainingAllowedItems(): number;

        getSize(id: number): number;

        getUploads(): FineUploaderFile[];

        log(message: string, level?: string): void;

        pauseUpload(id: number): void;

        removeFile(id: number): void;

        reset(): void;

        retry(id: number): void;

        scaleImage(id: number, customResize?: (image: HTMLImageElement) => void): Promise<Blob>;

        setEndpoint(endpoint: string, id?: number): void;

        setFormData(formData: Record<string, any>, id?: number): void;

        setHeaders(headers: Record<string, string>, id?: number): void;

        setItemLimit(itemLimit: number): void;

        setParams(params: Record<string, any>, id?: number): void;

        setStatus(id: number, status: string): void;

        setUuid(id: number, uuid: string): void;

        uploadStoredFiles(): void;
    }

    interface FineUploaderFile {
        id: number;
        uuid?: string;
        name: string;
        size: number;
        status: string;

        [key: string]: any;
    }
}

declare var qq: typeof qq;

