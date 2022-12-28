document.getElementById( 'search-query' ).addEventListener( 'keydown', function( event ) {
    if ( event.key === 'Enter' ) {
        event.preventDefault();
        document.getElementById( 'search-form' ).submit();
    }
});

jQuery(function($) {
    $('#convert-images-button').attr('disabled', true);

    $('.image-checkbox').click(function() {
        if ($('.image-checkbox:checked').length > 0) {
            $('#convert-images-button').attr('disabled', false);
        } else {
            $('#convert-images-button').attr('disabled', true);
        }
    });
});


const searchQuery = document.getElementById('search-query');
const clearSearch = document.querySelector('.clear-search');

searchQuery.addEventListener('input', function() {
    if (this.value !== '') {
        clearSearch.style.display = 'block';
    } else {
        clearSearch.style.display = 'none';
    }
});

clearSearch.addEventListener('click', function() {
    searchQuery.value = '';
    this.style.display = 'none';
});