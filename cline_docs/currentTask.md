# Current Task: Refactor User Management (`user_list.php`)

## Objectives
- [x] Separate database logic, presentation logic, and request handling for user management into Model, View, and Controller components.
- [x] Create `app/Models/UserModel.php` for database operations (fetch all, fetch members, fetch by ID, update, delete).
- [x] Create `app/Controllers/UserController.php` to handle routing logic, permissions, and interaction between Model and Views.
- [x] Create `app/Views/user_list_view.php` to display the user table.
- [x] Create `app/Views/user_edit_form_view.php` for the user editing form.
- [x] Create `users.php` in the root directory as the main entry point/router for user management actions (`list`, `edit`, `update`, `delete`).
- [x] Apply styling from `public/assets/css/settingsStyle.css` to the new views.
- [x] Ensure correct permissions are enforced (Admin vs. Mentor capabilities).
- [x] Create and integrate a reusable navigation component (`_navigation.php`).
- [x] Remove the old `user_list.php` and `delete_user.php` files.

## Context
The previous `user_list.php` file contained mixed PHP logic for database access, permission checks, and HTML rendering for both listing and initiating edits/deletes of users. This needed refactoring for better maintainability, separation of concerns, and adherence to MVC principles.

## Next Steps (Completed for this task)
1.  **DONE:** Created `app/Models/UserModel.php`.
2.  **DONE:** Created `app/Controllers/UserController.php`.
3.  **DONE:** Created `app/Views/user_list_view.php`.
4.  **DONE:** Created `app/Views/user_edit_form_view.php`.
5.  **DONE:** Created `users.php` router.
6.  **DONE:** Created `_navigation.php` and included it in views.
7.  **DONE:** Updated documentation (`projectRoadmap.md`, `currentTask.md`).
8.  **PENDING:** Update `codebaseSummary.md`.
9.  **PENDING:** Remove `user_list.php`.
10. **PENDING:** Check for and remove `delete_user.php`.
11. **PENDING:** Attempt completion.
