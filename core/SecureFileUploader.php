<?php
/**
 * Secure File Upload System with malware scanning
 * Phase 2 Implementation
 */

require_once __DIR__ . '/Logger.php';

class SecureFileUploader {
    private $config;
    private $logger;
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;
    
    public function __construct() {
        require_once __DIR__ . '/../config/ConfigLoader.php';
        $this->config = ConfigLoader::get('app.uploads');
        $this->logger = new Logger();
        
        $this->allowedTypes = $this->config['allowed_types'];
        $this->maxSize = $this->config['max_size'];
        $this->uploadPath = $this->config['path'];
        
        $this->ensureUploadDirectoryExists();
    }
    
    /**
     * Upload file with comprehensive security checks
     */
    public function upload($file, $customPath = null) {
        try {
            $this->validateFile($file);
            
            $uploadDir = $customPath ?? $this->generateSecureUploadPath();
            $filename = $this->generateSecureFilename($file['name']);
            $fullPath = $uploadDir . $filename;
            
            // Create quarantine area for scanning
            $tempPath = sys_get_temp_dir() . '/' . uniqid('upload_', true);
            
            if (move_uploaded_file($file['tmp_name'], $tempPath)) {
                // Perform security scans
                $this->scanForMalware($tempPath);
                $this->scanFileContent($tempPath, $file['name']);
                
                // Move to final location if safe
                if (rename($tempPath, $fullPath)) {
                    chmod($fullPath, 0644);
                    
                    $this->logger->info('File uploaded successfully', [
                        'original_name' => $file['name'],
                        'saved_path' => $fullPath,
                        'size' => $file['size'],
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    return [
                        'success' => true,
                        'path' => $fullPath,
                        'url' => $this->getFileUrl($fullPath),
                        'filename' => $filename
                    ];
                }
            }
            
            throw new Exception('Failed to move uploaded file');
            
        } catch (Exception $e) {
            // Clean up temporary files
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            $this->logger->error('File upload failed', [
                'error' => $e->getMessage(),
                'file_info' => [
                    'name' => $file['name'] ?? 'unknown',
                    'size' => $file['size'] ?? 0,
                    'type' => $file['type'] ?? 'unknown'
                ],
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxSizeMB = round($this->maxSize / 1048576, 2);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }
        
        // Check if file is actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('File was not uploaded via HTTP POST');
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes));
        }
        
        // Check filename for dangerous characters
        if (preg_match('/[<>:"\/\\|?*]/', $file['name'])) {
            throw new Exception('Filename contains dangerous characters');
        }
        
        // Check for null bytes in filename
        if (strpos($file['name'], "\0") !== false) {
            throw new Exception('Filename contains null bytes');
        }
        
        // Validate MIME type
        $this->validateMimeType($file['tmp_name'], $extension);
    }
    
    private function validateMimeType($tmpName, $extension) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
        
        if (isset($allowedMimes[$extension])) {
            if (!in_array($mimeType, $allowedMimes[$extension])) {
                throw new Exception("File MIME type ({$mimeType}) does not match extension ({$extension})");
            }
        }
    }
    
    private function scanForMalware($filePath) {
        // Basic malware patterns scan
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new Exception('Cannot read uploaded file for scanning');
        }
        
        $chunkSize = 8192;
        $content = '';
        
        // Read first 64KB for scanning
        for ($i = 0; $i < 8 && !feof($handle); $i++) {
            $content .= fread($handle, $chunkSize);
        }
        fclose($handle);
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/(?:eval|exec|system|shell_exec|passthru|file_get_contents|fopen|fwrite|include|require)\s*\(/i',
            '/<\?php/i',
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript\s*:/i',
            '/data\s*:\s*[^\s;]+\s*;\s*base64/i',
            '/\\x[0-9a-f]{2}/i',
            '/%[0-9a-f]{2}/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->logger->critical('Malware detected in uploaded file', [
                    'pattern' => $pattern,
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                throw new Exception('Potentially malicious content detected in file');
            }
        }
    }
    
    private function scanFileContent($filePath, $originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Additional checks for image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $this->validateImageFile($filePath);
        }
        
        // Check for embedded executables
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            $header = fread($handle, 4);
            fclose($handle);
            
            // Check for PE executable headers
            if ($header === "MZ\x90\x00" || $header === "PK\x03\x04") {
                throw new Exception('Executable content detected in uploaded file');
            }
        }
    }
    
    private function validateImageFile($filePath) {
        // Verify it's actually an image
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            throw new Exception('File is not a valid image');
        }
        
        // Check for reasonable dimensions (prevent memory exhaustion)
        if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
            throw new Exception('Image dimensions are too large');
        }
        
        // Try to create image resource to verify integrity
        $imageType = $imageInfo[2];
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $resource = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $resource = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $resource = @imagecreatefromgif($filePath);
                break;
            default:
                throw new Exception('Unsupported image type');
        }
        
        if ($resource === false) {
            throw new Exception('Corrupted or malicious image file');
        }
        
        imagedestroy($resource);
    }
    
    private function generateSecureUploadPath() {
        $datePath = date('Y-m');
        $fullPath = rtrim($this->uploadPath, '/') . '/' . $datePath . '/';
        
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        return $fullPath;
    }
    
    private function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize filename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $basename = trim($basename, '_-');
        $basename = substr($basename, 0, 50);
        
        if (empty($basename)) {
            $basename = 'file';
        }
        
        // Add timestamp and random string
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        
        return $timestamp . '_' . $randomString . '_' . $basename . '.' . $extension;
    }
    
    private function getFileUrl($filePath) {
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
        $baseUrl = ConfigLoader::get('app.url');
        return rtrim($baseUrl, '/') . '/' . ltrim($relativePath, '/');
    }
    
    private function ensureUploadDirectoryExists() {
        if (!is_dir($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0755, true)) {
                throw new Exception('Cannot create upload directory');
            }
        }
        
        // Create .htaccess file to prevent direct execution
        $htaccessFile = rtrim($this->uploadPath, '/') . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\nOptions -Indexes\n";
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}