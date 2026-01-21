<!-- Profile Form -->
<form id="profile-form" method="POST">
    <!-- Personal Information Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-user-circle"></i>
            Personal Information
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" 
                       value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" 
                       value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($userData['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" 
                       value="<?php echo htmlspecialchars($userData['date_of_birth'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($userData['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($userData['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($userData['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    <option value="Prefer not to say" <?php echo ($userData['gender'] === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                </select>
            </div>
        </div>
    </div>

    <!-- School Information Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-graduation-cap"></i>
            School Information
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="school">School Name</label>
                <input type="text" id="school" name="school" 
                       value="<?php echo htmlspecialchars($userData['school'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="grade">Grade</label>
                <select id="grade" name="grade">
                    <option value="">Select Grade</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($userData['grade'] == $i) ? 'selected' : ''; ?>>
                            Grade <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Address Information Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-map-marker-alt"></i>
            Address Information
        </h3>
        
        <div class="form-row single">
            <div class="form-group">
                <label for="address">Street Address</label>
                <textarea id="address" name="address" placeholder="Enter your full address"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" 
                       value="<?php echo htmlspecialchars($userData['city'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="province">Province</label>
                <input type="text" id="province" name="province" 
                       value="<?php echo htmlspecialchars($userData['province'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" 
                       value="<?php echo htmlspecialchars($userData['postal_code'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <!-- Guardian Information Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-users"></i>
            Guardian Information
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="guardian_name">Guardian Name</label>
                <input type="text" id="guardian_name" name="guardian_name" 
                       value="<?php echo htmlspecialchars($userData['guardian_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="guardian_relationship">Relationship</label>
                <input type="text" id="guardian_relationship" name="guardian_relationship" 
                       value="<?php echo htmlspecialchars($userData['guardian_relationship'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="guardian_phone">Guardian Phone</label>
                <input type="tel" id="guardian_phone" name="guardian_phone" 
                       value="<?php echo htmlspecialchars($userData['guardian_phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="guardian_email">Guardian Email</label>
                <input type="email" id="guardian_email" name="guardian_email" 
                       value="<?php echo htmlspecialchars($userData['guardian_email'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <!-- Emergency Contact Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-phone-alt"></i>
            Emergency Contact
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="emergency_contact_name">Emergency Contact Name</label>
                <input type="text" id="emergency_contact_name" name="emergency_contact_name" 
                       value="<?php echo htmlspecialchars($userData['emergency_contact_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="emergency_contact_relationship">Relationship</label>
                <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" 
                       value="<?php echo htmlspecialchars($userData['emergency_contact_relationship'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="emergency_contact_phone">Emergency Contact Phone</label>
                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" 
                       value="<?php echo htmlspecialchars($userData['emergency_contact_phone'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <!-- Program Information Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-calendar-alt"></i>
            Program Information
        </h3>
        
        <div class="form-row single">
            <div class="form-group">
                <label for="why_interested">Why are you interested in this program?</label>
                <textarea id="why_interested" name="why_interested" 
                          placeholder="Tell us about your interest..."><?php echo htmlspecialchars($userData['why_interested'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="experience_level">Experience Level</label>
                <select id="experience_level" name="experience_level">
                    <option value="">Select Experience Level</option>
                    <option value="Beginner" <?php echo ($userData['experience_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                    <option value="Intermediate" <?php echo ($userData['experience_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="Advanced" <?php echo ($userData['experience_level'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                </select>
            </div>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="needs_equipment" name="needs_equipment" 
                   <?php echo ($userData['needs_equipment']) ? 'checked' : ''; ?>>
            <label for="needs_equipment">I need equipment/laptop for the program</label>
        </div>
    </div>

    <!-- Medical Information Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-medkit"></i>
            Medical & Dietary Information
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="medical_conditions">Medical Conditions</label>
                <textarea id="medical_conditions" name="medical_conditions" 
                          placeholder="List any medical conditions we should be aware of..."><?php echo htmlspecialchars($userData['medical_conditions'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="allergies">Allergies</label>
                <textarea id="allergies" name="allergies" 
                          placeholder="List any allergies..."><?php echo htmlspecialchars($userData['allergies'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-row single">
            <div class="form-group">
                <label for="dietary_restrictions">Dietary Restrictions</label>
                <textarea id="dietary_restrictions" name="dietary_restrictions" 
                          placeholder="List any dietary restrictions..."><?php echo htmlspecialchars($userData['dietary_restrictions'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <!-- Permissions Section -->
    <div class="profile-section">
        <h3 class="section-title">
            <i class="fas fa-shield-alt"></i>
            Permissions
        </h3>
        
        <div class="checkbox-group">
            <input type="checkbox" id="photo_permission" name="photo_permission" 
                   <?php echo ($userData['photo_permission']) ? 'checked' : ''; ?>>
            <label for="photo_permission">I give permission for photos to be taken during the program</label>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" id="data_permission" name="data_permission" 
                   <?php echo ($userData['data_permission']) ? 'checked' : ''; ?>>
            <label for="data_permission">I consent to data collection for program improvement</label>
        </div>
    </div>

    <!-- Admin Only Section -->
    <?php if ($isAdmin): ?>
    <div class="profile-section admin-only">
        <h3 class="section-title">
            <i class="fas fa-cog"></i>
            Administrative Controls
            <span class="admin-badge">ADMIN ONLY</span>
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="registration_status">Registration Status</label>
                <select id="registration_status" name="registration_status">
                    <option value="pending" <?php echo ($userData['registration_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo ($userData['registration_status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="canceled" <?php echo ($userData['registration_status'] === 'canceled') ? 'selected' : ''; ?>>Canceled</option>
                    <option value="waitlisted" <?php echo ($userData['registration_status'] === 'waitlisted') ? 'selected' : ''; ?>>Waitlisted</option>
                    <option value="completed" <?php echo ($userData['registration_status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Overall Status</label>
                <select id="status" name="status">
                    <option value="pending" <?php echo ($userData['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo ($userData['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="cancelled" <?php echo ($userData['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="declined" <?php echo ($userData['status'] === 'declined') ? 'selected' : ''; ?>>Declined</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="mentor_status">Mentor Status</label>
                <select id="mentor_status" name="mentor_status">
                    <option value="">N/A (Regular Attendee)</option>
                    <option value="Pending" <?php echo ($userData['mentor_status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Approved" <?php echo ($userData['mentor_status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="Declined" <?php echo ($userData['mentor_status'] === 'Declined') ? 'selected' : ''; ?>>Declined</option>
                </select>
            </div>
            <div class="form-group">
                <label for="is_clubhouse_member">Clubhouse Member</label>
                <select id="is_clubhouse_member" name="is_clubhouse_member">
                    <option value="0" <?php echo (!$userData['is_clubhouse_member']) ? 'selected' : ''; ?>>No</option>
                    <option value="1" <?php echo ($userData['is_clubhouse_member']) ? 'selected' : ''; ?>>Yes</option>
                </select>
            </div>
        </div>

        <div class="form-row single">
            <div class="form-group">
                <label for="additional_notes">Admin Notes</label>
                <textarea id="additional_notes" name="additional_notes" 
                          placeholder="Administrative notes..."><?php echo htmlspecialchars($userData['additional_notes'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <button type="submit" class="btn-save" id="save-btn">
            <i class="fas fa-save"></i>
            Save Changes
        </button>
        <a href="holiday-dashboard.php" class="btn-secondary">
            <i class="fas fa-tachometer-alt"></i>
            Go to Dashboard
        </a>
        <?php if ($isAdmin && $viewingUserId !== $userId): ?>
            <a href="holidayProgramAdminDashboard.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Admin
            </a>
        <?php endif; ?>
    </div>
</form>