<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="<?php echo BASE_URL; ?>admin/courses">Courses</a>
    <span class="breadcrumb-separator">/</span>
    <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>">
        <?php echo htmlspecialchars($course['title']); ?>
    </a>
    <span class="breadcrumb-separator">/</span>
    <span>Edit</span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Course</h1>
        <p style="color: var(--gray-medium); margin-top: 5px;">
            Update course information and settings
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>" class="btn-secondary">
            <i class="fas fa-eye"></i>
            View Course
        </a>
        <a href="<?php echo BASE_URL; ?>admin/courses" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to List
        </a>
    </div>
</div>

<!-- Edit Course Form -->
<div class="form-container">
    <form method="POST" action="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>/update" enctype="multipart/form-data">
        <?php echo CSRF::field(); ?>
        <input type="hidden" name="_method" value="PUT">

        <!-- Basic Information Section -->
        <div class="form-card">
            <div class="form-card-header">
                <h2 class="form-card-title">
                    <i class="fas fa-info-circle"></i>
                    Basic Information
                </h2>
            </div>
            <div class="form-card-body">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="title" class="form-label">Course Title *</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            required
                            placeholder="Enter a descriptive title for your course"
                            value="<?php echo htmlspecialchars($course['title']); ?>">
                        <small class="form-text">
                            Current code: <strong><?php echo htmlspecialchars($course['course_code']); ?></strong>
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="type" class="form-label">Content Type *</label>
                        <select id="type" name="type" class="form-control" required>
                            <?php
                            $types = $courseTypes ?? [
                                'full_course' => 'Full Course',
                                'short_course' => 'Short Course',
                                'lesson' => 'Lesson',
                                'skill_activity' => 'Skill Activity'
                            ];
                            foreach ($types as $value => $label):
                            ?>
                                <option value="<?php echo $value; ?>" <?php echo $course['type'] === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="difficulty_level" class="form-label">Difficulty Level *</label>
                        <select id="difficulty_level" name="difficulty_level" class="form-control" required>
                            <?php
                            $levels = $difficultyLevels ?? ['Beginner', 'Intermediate', 'Advanced'];
                            foreach ($levels as $level):
                            ?>
                                <option value="<?php echo $level; ?>" <?php echo $course['difficulty_level'] === $level ? 'selected' : ''; ?>>
                                    <?php echo $level; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="duration" class="form-label">Estimated Duration</label>
                        <input
                            type="text"
                            id="duration"
                            name="duration"
                            class="form-control"
                            placeholder="e.g., 2 weeks, 5 hours, 30 minutes"
                            value="<?php echo htmlspecialchars($course['duration'] ?? ''); ?>">
                        <small class="form-text">Estimated time to complete this content</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description *</label>
                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="6"
                        required
                        placeholder="Provide a detailed description of what learners will gain from this course..."><?php echo htmlspecialchars($course['description']); ?></textarea>
                    <small class="form-text">Describe the course objectives, outcomes, and key topics covered</small>
                </div>
            </div>
        </div>

        <!-- Cover Image Section -->
        <div class="form-card">
            <div class="form-card-header">
                <h2 class="form-card-title">
                    <i class="fas fa-image"></i>
                    Cover Image
                </h2>
            </div>
            <div class="form-card-body">
                <?php if (!empty($course['image_path'])): ?>
                    <!-- Current Image Display -->
                    <div class="current-image-container" id="currentImageContainer">
                        <label class="form-label">Current Image</label>
                        <div class="current-image-wrapper">
                            <img src="<?php echo BASE_URL; ?>public/assets/uploads/images/courses/<?php echo htmlspecialchars($course['image_path']); ?>"
                                 alt="Current course image"
                                 class="current-image">
                            <div class="image-overlay">
                                <button type="button" class="btn-remove-image" onclick="removeCurrentImage()">
                                    <i class="fas fa-trash"></i>
                                    Remove Image
                                </button>
                            </div>
                        </div>
                        <input type="hidden" id="remove_image" name="remove_image" value="0">
                    </div>
                <?php endif; ?>

                <div class="form-group" style="margin-top: <?php echo !empty($course['image_path']) ? '20px' : '0'; ?>;">
                    <label for="image" class="form-label">
                        <?php echo !empty($course['image_path']) ? 'Replace Cover Image' : 'Upload Cover Image'; ?>
                    </label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        class="form-control"
                        accept="image/jpeg,image/png,image/gif,image/webp">
                    <small class="form-text">
                        <i class="fas fa-info-circle"></i>
                        Recommended size: 1200x600 pixels. Max file size: 2MB. Supported formats: JPEG, PNG, GIF, WebP.
                    </small>
                </div>

                <!-- New Image Preview -->
                <div id="imagePreview" style="display: none; margin-top: 15px;">
                    <label class="form-label">New Image Preview</label>
                    <div style="border: 2px dashed var(--gray-light); border-radius: var(--border-radius-md); padding: 10px; background: var(--light);">
                        <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 300px; border-radius: var(--border-radius-sm);">
                    </div>
                </div>
            </div>
        </div>

        <!-- Publication Settings Section -->
        <div class="form-card">
            <div class="form-card-header">
                <h2 class="form-card-title">
                    <i class="fas fa-cog"></i>
                    Publication Settings
                </h2>
            </div>
            <div class="form-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control">
                            <?php
                            $statuses = ['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'];
                            foreach ($statuses as $value => $label):
                            ?>
                                <option value="<?php echo $value; ?>" <?php echo $course['status'] === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">
                            <strong>Draft:</strong> Not visible to members<br>
                            <strong>Active:</strong> Visible and enrollable<br>
                            <strong>Archived:</strong> Read-only, no new enrollments
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            id="is_published"
                            name="is_published"
                            class="form-check-input"
                            value="1"
                            <?php echo !empty($course['is_published']) ? 'checked' : ''; ?>>
                        <label for="is_published" class="form-check-label">
                            <strong>Published</strong>
                        </label>
                    </div>
                    <small class="form-text" style="margin-left: 28px;">
                        When checked, this content will be visible to members (sets status to Active)
                    </small>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            id="is_featured"
                            name="is_featured"
                            class="form-check-input"
                            value="1"
                            <?php echo !empty($course['is_featured']) ? 'checked' : ''; ?>>
                        <label for="is_featured" class="form-check-label">
                            <strong>Feature This Content</strong>
                        </label>
                    </div>
                    <small class="form-text" style="margin-left: 28px;">
                        Featured content appears prominently on the learning homepage with a special badge
                    </small>
                </div>
            </div>
        </div>

        <!-- Course Metadata Section -->
        <div class="form-card">
            <div class="form-card-header">
                <h2 class="form-card-title">
                    <i class="fas fa-info"></i>
                    Course Metadata
                </h2>
            </div>
            <div class="form-card-body">
                <div class="metadata-grid">
                    <div class="metadata-item">
                        <span class="metadata-label">Created By:</span>
                        <span class="metadata-value">
                            <?php echo htmlspecialchars($course['creator_name'] ?? 'Unknown'); ?>
                        </span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label">Created On:</span>
                        <span class="metadata-value">
                            <?php echo date('M d, Y', strtotime($course['created_at'])); ?>
                        </span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label">Last Updated:</span>
                        <span class="metadata-value">
                            <?php echo !empty($course['updated_at']) ? date('M d, Y', strtotime($course['updated_at'])) : 'Never'; ?>
                        </span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label">Total Enrollments:</span>
                        <span class="metadata-value">
                            <strong><?php echo intval($course['enrollment_count'] ?? 0); ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn-primary btn-lg">
                <i class="fas fa-save"></i>
                Update Course
            </button>
            <a href="<?php echo BASE_URL; ?>admin/courses/<?php echo $course['id']; ?>" class="btn-secondary btn-lg">
                <i class="fas fa-times"></i>
                Cancel
            </a>
        </div>
    </form>
</div>

<!-- Additional Styles and Scripts -->
<style>
    .form-container {
        max-width: 900px;
    }

    .form-card {
        background: white;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .form-card-header {
        background: linear-gradient(135deg, var(--primary) 0%, #6c5ce7 100%);
        padding: 20px 24px;
        border-bottom: 1px solid var(--gray-light);
    }

    .form-card-title {
        margin: 0;
        color: white;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-card-body {
        padding: 24px;
    }

    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 0;
    }

    .form-row .form-group {
        flex: 1;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark);
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--gray-light);
        border-radius: var(--border-radius-sm);
        font-size: 0.95rem;
        font-family: inherit;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(81, 70, 230, 0.1);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }

    .form-text {
        display: block;
        margin-top: 6px;
        font-size: 0.85rem;
        color: var(--gray-medium);
        line-height: 1.5;
    }

    .form-check {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .form-check-input {
        margin-top: 3px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .form-check-label {
        cursor: pointer;
        user-select: none;
        line-height: 1.5;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        padding: 24px 0;
    }

    .btn-lg {
        padding: 12px 28px;
        font-size: 1rem;
    }

    /* Current Image Styles */
    .current-image-container {
        margin-bottom: 20px;
    }

    .current-image-wrapper {
        position: relative;
        display: inline-block;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .current-image {
        display: block;
        max-width: 100%;
        max-height: 300px;
        border-radius: var(--border-radius-sm);
    }

    .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .current-image-wrapper:hover .image-overlay {
        opacity: 1;
    }

    .btn-remove-image {
        background: var(--danger);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .btn-remove-image:hover {
        background: #c0392b;
        transform: scale(1.05);
    }

    /* Metadata Grid */
    .metadata-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .metadata-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .metadata-label {
        font-size: 0.85rem;
        color: var(--gray-medium);
        font-weight: 500;
    }

    .metadata-value {
        font-size: 0.95rem;
        color: var(--dark);
    }

    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }

        .form-actions {
            flex-direction: column;
        }

        .form-actions .btn-lg {
            width: 100%;
        }

        .metadata-grid {
            grid-template-columns: 1fr;
        }

        .page-header > div:last-child {
            flex-direction: column;
            width: 100%;
        }

        .page-header > div:last-child a {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const currentImageContainer = document.getElementById('currentImageContainer');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
                    imageInput.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                // Validate file size (2MB)
                if (file.size > 2097152) {
                    alert('Image file is too large. Maximum size is 2MB.');
                    imageInput.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });

        // Toggle status based on is_published checkbox
        const publishedCheckbox = document.getElementById('is_published');
        const statusSelect = document.getElementById('status');

        publishedCheckbox.addEventListener('change', function() {
            if (this.checked) {
                statusSelect.value = 'active';
                statusSelect.disabled = true;
            } else {
                statusSelect.disabled = false;
            }
        });

        // Initial state
        if (publishedCheckbox.checked) {
            statusSelect.value = 'active';
            statusSelect.disabled = true;
        }

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();

            if (title.length < 3) {
                e.preventDefault();
                alert('Course title must be at least 3 characters long.');
                document.getElementById('title').focus();
                return false;
            }

            if (description.length < 10) {
                e.preventDefault();
                alert('Please provide a more detailed description (at least 10 characters).');
                document.getElementById('description').focus();
                return false;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        });
    });

    // Remove current image function
    function removeCurrentImage() {
        if (confirm('Are you sure you want to remove the current image?')) {
            document.getElementById('remove_image').value = '1';
            document.getElementById('currentImageContainer').style.display = 'none';
        }
    }
</script>
