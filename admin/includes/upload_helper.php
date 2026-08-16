<?php
/**
 * Helper utilities for File and Folder Uploads — QPTEO Admin
 */

/**
 * Process folder upload from $_FILES['folder_files']
 *
 * @param array $filesArray $_FILES['folder_files'] array
 * @param string $uploadDir Physical upload directory path (e.g. __DIR__ . '/../uploads/repositories/')
 * @param string $relativePrefix Relative path prefix for database (e.g. 'uploads/repositories/')
 * @return array Result array ['success' => bool, 'filePath' => string, 'fileSize' => int, 'fileType' => string, 'folderName' => string, 'error' => string]
 */
function handleFolderUpload($filesArray, $uploadDir, $relativePrefix = 'uploads/repositories/') {
    if (empty($filesArray['name']) || !is_array($filesArray['name'])) {
        return ['success' => false, 'error' => 'No folder files selected.'];
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileCount = count($filesArray['name']);
    $totalSize = 0;
    $validFiles = [];
    $folderName = 'folder_' . time();

    for ($i = 0; $i < $fileCount; $i++) {
        if (isset($filesArray['error'][$i]) && $filesArray['error'][$i] === UPLOAD_ERR_OK) {
            $relPath = !empty($filesArray['full_path'][$i]) ? $filesArray['full_path'][$i] : $filesArray['name'][$i];
            
            // Normalize slashes
            $relPath = str_replace('\\', '/', $relPath);
            $base = basename($relPath);

            // Skip hidden system files
            if ($base === '.DS_Store' || $base === 'Thumbs.db' || strpos($relPath, '__MACOSX') !== false || strpos($base, '~$') === 0) {
                continue;
            }

            // Extract main folder name
            $parts = explode('/', $relPath);
            if (count($parts) > 1 && !empty($parts[0])) {
                $folderName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $parts[0]);
            }

            $totalSize += $filesArray['size'][$i];
            $validFiles[] = [
                'tmp_name' => $filesArray['tmp_name'][$i],
                'name'     => $filesArray['name'][$i],
                'rel_path' => $relPath
            ];
        }
    }

    if (empty($validFiles)) {
        return ['success' => false, 'error' => 'Folder contains no valid files to upload.'];
    }

    // Maximum folder upload size: 100MB
    $maxFolderSize = 100 * 1024 * 1024;
    if ($totalSize > $maxFolderSize) {
        return ['success' => false, 'error' => 'Total folder size exceeds the maximum limit of 100MB.'];
    }

    $safeFolderName = time() . '_' . $folderName;

    // Use ZipArchive if available to compress the uploaded folder into a downloadable .zip archive
    if (class_exists('ZipArchive')) {
        $zipFilename = $safeFolderName . '.zip';
        $zipFullPath = rtrim($uploadDir, '/\\') . '/' . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($validFiles as $file) {
                $zip->addFile($file['tmp_name'], $file['rel_path']);
            }
            $zip->close();

            $finalSize = @filesize($zipFullPath);
            return [
                'success'    => true,
                'filePath'   => rtrim($relativePrefix, '/\\') . '/' . $zipFilename,
                'fileSize'   => $finalSize ?: $totalSize,
                'fileType'   => 'zip',
                'folderName' => $folderName
            ];
        }
    }

    // Fallback: Save files inside a directory structure
    $folderFullPath = rtrim($uploadDir, '/\\') . '/' . $safeFolderName;
    if (!is_dir($folderFullPath)) {
        mkdir($folderFullPath, 0755, true);
    }

    foreach ($validFiles as $file) {
        $subPath = $folderFullPath . '/' . ltrim($file['rel_path'], '/');
        $subDir  = dirname($subPath);
        if (!is_dir($subDir)) {
            mkdir($subDir, 0755, true);
        }
        move_uploaded_file($file['tmp_name'], $subPath);
    }

    return [
        'success'    => true,
        'filePath'   => rtrim($relativePrefix, '/\\') . '/' . $safeFolderName,
        'fileSize'   => $totalSize,
        'fileType'   => 'folder',
        'folderName' => $folderName
    ];
}
