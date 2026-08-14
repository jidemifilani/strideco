<?php
// Shared file-upload validation/storage helpers for the admin panel —
// used by product-form.php (main photo + gallery) and product-variant-form.php
// (per-color photo).

const UPLOAD_DIR = __DIR__ . '/../../assets/uploads/';
const ALLOWED_IMAGE_EXT = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
const ALLOWED_VIDEO_EXT = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime'];

/** @return array{ok: bool, filename: ?string, error: ?string} */
function validate_and_save_upload(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'Upload failed, please try again.'];
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        return ['ok' => false, 'filename' => null, 'error' => 'Image must be smaller than 4MB.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!isset(ALLOWED_IMAGE_EXT[$ext]) || !$imageInfo || $imageInfo['mime'] !== ALLOWED_IMAGE_EXT[$ext]) {
        return ['ok' => false, 'filename' => null, 'error' => 'Image must be a JPG, PNG, or WEBP file.'];
    }
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!is_dir(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0755, true); }
    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);
    return ['ok' => true, 'filename' => $filename, 'error' => null];
}

/**
 * Like validate_and_save_upload(), but also accepts short product videos
 * (MP4/WEBM/MOV) for the gallery — e.g. a walkthrough or 360° clip.
 * @return array{ok: bool, filename: ?string, type: ?string, error: ?string}
 */
function validate_and_save_media_upload(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'type' => null, 'error' => 'Upload failed, please try again.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (isset(ALLOWED_IMAGE_EXT[$ext])) {
        if ($file['size'] > 4 * 1024 * 1024) {
            return ['ok' => false, 'filename' => null, 'type' => null, 'error' => 'Image must be smaller than 4MB.'];
        }
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo || $imageInfo['mime'] !== ALLOWED_IMAGE_EXT[$ext]) {
            return ['ok' => false, 'filename' => null, 'type' => null, 'error' => 'Image file appears to be invalid.'];
        }
        $type = 'image';
    } elseif (isset(ALLOWED_VIDEO_EXT[$ext])) {
        if ($file['size'] > 20 * 1024 * 1024) {
            return ['ok' => false, 'filename' => null, 'type' => null, 'error' => 'Video must be smaller than 20MB.'];
        }
        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
        $validVideoMimes = ['video/mp4', 'video/webm', 'video/quicktime', 'application/octet-stream'];
        if ($mime !== null && !in_array($mime, $validVideoMimes, true)) {
            return ['ok' => false, 'filename' => null, 'type' => null, 'error' => 'Video file appears to be invalid.'];
        }
        $type = 'video';
    } else {
        return ['ok' => false, 'filename' => null, 'type' => null, 'error' => 'File must be a JPG/PNG/WEBP image or an MP4/WEBM/MOV video.'];
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!is_dir(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0755, true); }
    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);
    return ['ok' => true, 'filename' => $filename, 'type' => $type, 'error' => null];
}
