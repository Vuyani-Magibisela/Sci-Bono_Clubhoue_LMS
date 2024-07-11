$(document).ready(function() {
    function updateResults() {
        var searchTerm = $('#search').val();
        var genderFilter = $('#gender-filter').val();
        var gradeFilter = $('#grade-filter').val();
        
        $.ajax({
            url: 'filter.php',
            method: 'POST',
            data: {
                search: searchTerm,
                gender: genderFilter,
                grade: gradeFilter
            },
            success: function(response) {
                $('#results-container').html(response);
            }
        });
    }

    // Trigger search on input change
    $('#search, #gender-filter, #grade-filter').on('input change', updateResults);

    // Initial load
    updateResults();
});