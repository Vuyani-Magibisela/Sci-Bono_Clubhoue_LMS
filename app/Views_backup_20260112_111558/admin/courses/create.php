<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>admin">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <a href="<?php echo BASE_URL; ?>admin/courses">Courses</a>
    <span class="breadcrumb-separator">/</span>
    <span>Create</span>
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Create New Course</h1>
        <p style="color: var(--gray-medium); margin-top: 5px;">
            Add a new learning content to the platform
        </p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/courses" class="btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to Courses
        </a>
    </div>
</div>

<!-- Create Course Form -->
<div class="form-container">
    <form method="POST" action="<?php echo BASE_URL; ?>admin/courses" enctype="multipart/form-data">
        <?php echo CSRF::field(); ?>

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
                            value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>">
                        <small class="form-text">The course code will be automatically generated from this title.</small>
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
                            $selectedType = $formData['type'] ?? 'full_course';
                            foreach ($types as $value => $label):
                            ?>
                                <option value="<?php echo $value; ?>" <?php echo $selectedType === $value ? 'selected' : ''; ?>>
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
                            $selectedLevel = $formData['difficulty_level'] ?? 'Beginner';
                            foreach ($levels as $level):
                            ?>
                                <option value="<?php echo $level; ?>" <?php echo $selectedLevel === $level ? 'selected' : ''; ?>>
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
                            value="<?php echo htmlspecialchars($formData['duration'] ?? ''); ?>">
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
                        placeholder="Provide a detailed description of what learners will gain from this course..."><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
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
                <div class="form-group">
                    <label for="image" class="form-label">Upload Cover Image</label>
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

                <!-- Image Preview -->
                <div id="imagePreview" style="display: none; margin-top: 15px;">
                    <label class="form-label">Preview</label>
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
                            $selectedStatus = $formData['status'] ?? 'draft';
                            foreach ($statuses as $value => $label):
                            ?>
                                <option value="<?php echo $value; ?>" <?php echo $selectedStatus === $value ? 'selected' : ''; ?>>
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
                            <?php echo !empty($formData['is_published']) ? 'checked' : ''; ?>>
                        <label for="is_published" class="form-check-label">
                            <strong>Publish Immediately</strong>
                        </label>
                    </div>
                    <small class="form-text" style="margin-left: 28px;">
                        When checked, this content will be immediately visible to members (sets status to Active)
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
                            <?php echo !empty($formData['is_featured']) ? 'checked' : ''; ?>>
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

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn-primary btn-lg">
                <i class="fas fa-save"></i>
                Create Course
            </button>
            <a href="<?php echo BASE_URL; ?>admin/courses" class="btn-secondary btn-lg">
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
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');

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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        });
    });
</script>
