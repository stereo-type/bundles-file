/**
 * TypeScript definitions for Plupload
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @see https://www.plupload.com/
 */

declare namespace plupload {
    interface PluploadOptions {
        runtimes?: string;
        url?: string;
        max_file_size?: string | number;
        chunk_size?: string | number;
        unique_names?: boolean;
        rename?: boolean;
        sortable?: boolean;
        sort?: (a: PluploadFile, b: PluploadFile) => number;
        flash_swf_url?: string;
        silverlight_xap_url?: string;
        filters?: PluploadFilters;
        resize?: PluploadResize;
        browse_button?: string | HTMLElement;
        container?: string | HTMLElement;
        drop_element?: string | HTMLElement;
        file_data_name?: string;
        multipart?: boolean;
        multipart_params?: Record<string, any>;
        required_features?: string | Record<string, any>;
        headers?: Record<string, string>;
        preinit?: (uploader: Uploader) => void;
        init?: PluploadInitOptions;
        urlstream_upload?: boolean;
        send_file_name?: boolean;
        send_file_num?: boolean;
        send_file_hash?: boolean;
        max_retries?: number;
        chunk_size?: string | number;
    }

    interface PluploadFilters {
        mime_types?: Array<{
            title: string;
            extensions: string;
        }>;
        max_file_size?: string | number;
        prevent_duplicates?: boolean;
    }

    interface PluploadResize {
        enabled?: boolean;
        width?: number;
        height?: number;
        crop?: boolean;
        quality?: number;
        preserve_headers?: boolean;
    }

    interface PluploadInitOptions {
        FilesAdded?: (uploader: Uploader, files: PluploadFile[]) => void;
        FilesRemoved?: (uploader: Uploader, files: PluploadFile[]) => void;
        BeforeUpload?: (uploader: Uploader, file: PluploadFile) => void;
        UploadFile?: (uploader: Uploader, file: PluploadFile) => void;
        UploadProgress?: (uploader: Uploader, file: PluploadFile) => void;
        FileUploaded?: (uploader: Uploader, file: PluploadFile, response: PluploadResponse) => void;
        ChunkUploaded?: (uploader: Uploader, file: PluploadFile, response: PluploadResponse) => void;
        UploadComplete?: (uploader: Uploader, files: PluploadFile[]) => void;
        Error?: (uploader: Uploader, error: PluploadError) => void;
        Destroy?: (uploader: Uploader) => void;
        PostInit?: (uploader: Uploader) => void;
        Init?: (uploader: Uploader) => void;
        StateChanged?: (uploader: Uploader) => void;
        QueueChanged?: (uploader: Uploader) => void;
    }

    interface PluploadFile {
        id: string;
        name: string;
        size: number;
        type: string;
        origSize: number;
        loaded: number;
        percent: number;
        status: number;
        lastModifiedDate: Date;
        target_name?: string;
        relativePath?: string;
        nativeFile?: File;
    }

    interface PluploadResponse {
        response: string;
        status: number;
        responseHeaders: string;
    }

    interface PluploadError {
        code: number;
        message: string;
        file?: PluploadFile;
        response?: string;
        status?: number;
    }

    class Uploader {
        constructor(settings: PluploadOptions);

        // Properties
        id: string;
        runtime: string;
        features: Record<string, boolean>;
        files: PluploadFile[];
        total: PluploadStats;
        state: number;
        settings: PluploadOptions;

        // Methods
        init(): void;

        setOption(option: string, value: any): void;

        getOption(option: string): any;

        refresh(): void;

        start(): void;

        stop(): void;

        disableBrowse(disable: boolean): void;

        getFile(id: string): PluploadFile | null;

        getFiles(): PluploadFile[];

        addFile(file: File, fileName?: string): void;

        removeFile(file: PluploadFile | string): void;
        removeFile(file: PluploadFile | string, triggerEvent: boolean): void;

        splice(start: number, length: number): PluploadFile[];

        bind(event: string, callback: Function, scope?: any): void;

        unbind(event: string, callback?: Function): void;

        trigger(event: string, ...args: any[]): void;

        hasEventListener(event: string): boolean;

        destroy(): void;

        getRuntime(): string;

        getFeatures(): Record<string, boolean>;

        getStats(): PluploadStats;

        getTotal(): PluploadStats;

        getSize(): number;

        getNativeFile(file: PluploadFile): File | null;
        getNativeFile(file: PluploadFile, callback: (file: File) => void): void;
        getNativeFile(file: PluploadFile, callback: (file: File | null) => void): void;
    }

    interface PluploadStats {
        bytesPerSec: number;
        failed: number;
        loaded: number;
        queued: number;
        size: number;
        total: number;
        uploaded: number;
    }

    // Constants
    const STOPPED: number;
    const STARTED: number;
    const QUEUED: number;
    const UPLOADING: number;
    const FAILED: number;
    const DONE: number;
    const ERROR: {
        GENERIC_ERROR: number;
        HTTP_ERROR: number;
        IO_ERROR: number;
        SECURITY_ERROR: number;
        INIT_ERROR: number;
        FILE_SIZE_ERROR: number;
        FILE_EXTENSION_ERROR: number;
        FILE_DUPLICATE_ERROR: number;
        IMAGE_FORMAT_ERROR: number;
        IMAGE_DIMENSIONS_ERROR: number;
        MEMORY_ERROR: number;
        HTTP_STATUS_ERROR: number;
    };
    const VERSION: string;
}

declare var plupload: typeof plupload;

