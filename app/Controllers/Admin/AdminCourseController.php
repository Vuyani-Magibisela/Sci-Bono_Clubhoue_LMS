<?php
require_once __DIR__ . '/../../Models/Admin/AdminCourseModel.php';

class AdminCourseController {
    private $adminCourseModel;
    
    public function __construct($conn) {
        $this->adminCourseModel = new AdminCourseModel($conn);
    }
    
    public function createCourse($title, $description, $type, $difficulty, $duration, $imageFile, $createdBy) {
        // Handle image upload
        $imagePath = '';
        if (isset($imageFile) && $imageFile['error'] == 0) {
            $targetDir = "../../public/assets/uploads/images/courses/";
            
            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = basename($imageFile["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow only specific file formats
            $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
            if (in_array($fileType, $allowTypes)) {
                // Upload file to server
                if (move_uploaded_file($imageFile["tmp_name"], $targetFilePath)) {
                    $imagePath = $fileName;
                } else {
                    return [
                        'success' => false,
                        'message' => "Sorry, there was an error uploading your file."
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => "Sorry, only JPG, JPEG, PNG, & GIF files are allowed."
                ];
            }
        }
        
        $result = $this->adminCourseModel->createCourse($title, $description, $type, $difficulty, $duration, $imagePath, $createdBy);
        
        if ($result) {
            return [
                'success' => true,
                'message' => "Course created successfully!"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Error creating course."
            ];
        }
    }
    
    // Add other admin controller methods here
}
?>