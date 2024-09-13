<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Clubhouse Program</title>
    <link rel="stylesheet" href="../../public/assets/css/statsDashboardStyle.css">
</head>
<body id="addPrograms">
    <h1>Add New Clubhouse Program</h1>
    <form action="../Controllers/addPrograms.php" method="post">
        <label for="title">Program Title:</label>
        <input type="text" id="title" name="title" required>

        <label for="description">Program Description:</label>
        <textarea id="description" name="description" rows="4" required></textarea>

        <label for="learning_outcomes">Learning Outcomes:</label>
        <textarea id="learning_outcomes" name="learning_outcomes" rows="4" required></textarea>

        <label for="target_age_group">Target Age Group:</label>
        <input type="text" id="target_age_group" name="target_age_group" required>

        <label for="duration">Duration:</label>
        <input type="text" id="duration" name="duration" required>

        <label for="max_participants">Maximum Participants:</label>
        <input type="number" id="max_participants" name="max_participants" required>

        <label for="materials_needed">Materials Needed:</label>
        <textarea id="materials_needed" name="materials_needed" rows="4"></textarea>

        <label for="difficulty_level">Difficulty Level:</label>
        <select id="difficulty_level" name="difficulty_level" required>
            <option value="Beginner">Beginner</option>
            <option value="Intermediate">Intermediate</option>
            <option value="Advanced">Advanced</option>
        </select>

        <button type="submit">Add Program</button>
    </form>
</body>
</html>